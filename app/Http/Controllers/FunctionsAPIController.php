<?php

namespace App\Http\Controllers;

use DB;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\{Clients,Extract,Transactions,DataInvoice,Api,Notifications,Banks,UserDataMP,UserCreditCardMP,Quote,IndexTransaction,LimitDetailUser,RegisterUserMerchant};
use App\Http\Controllers\ItaucriptoController;
use App\Http\Controllers\BBBoletoWebService;

class FunctionsAPIController extends Controller
{
    //
    // Create deposit Cielo Credit Card
    public function createCieloCC($params = array(), $merchantId, $merchantKey){
        $curl = curl_init();

        $date = date("Y-m-d H:i:s");

        $client = Clients::where("id","=",$params['client_id'])->first();
        $bank = $client->bankCreditCard;
        $days_safe_credit_card = $client->days_safe_credit_card;

        $user_data = array(
            "name" => $params['user_name'],
            "document" => $params['user_document'],
            "bank_name" => $bank->name,
            "holder" => $bank->holder,
            "agency" => $bank->agency,
            "account_number" => $bank->account,
            "operation_bank" => $bank->type_account,
            "user_id" => $params['user_id'],
            "client_id" => $params['client_id'],
            "address" => $this->trata_unicode($params['user_address']),
            "district" => $this->trata_unicode($params['user_address_district']),
            "city" => $this->trata_unicode($params['user_address_city']),
            "uf" => $params['user_address_state'],
            "cep" => $params['user_address_zipcode']
        );

        $user_account_data = base64_encode(json_encode($user_data));

        // Calulo Taxas //
        $cot_ar = $this->get_cotacao_dolar($params['client_id'],"deposit");
        $cotacao_dolar_markup = $cot_ar['markup'];
        $cotacao_dolar = $cot_ar['quote'];
        $spread_deposit = $cot_ar['spread'];

        // Taxas
        $tax = $client->tax;

        if($client->currency == "brl"){

            $cotacao_dolar_markup = "1";
            $cotacao_dolar = "1";
            $spread_deposit = "0";

            $final_amount = $params['amount'];
            $percent_fee = ($final_amount * ($tax->credit_card_percent / 100));
            $fixed_fee = $tax->credit_card_absolute;
            if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
            $comission = ($percent_fee + $fixed_fee);
            if($comission < $tax->min_fee_credit_card){ $comission = $tax->min_fee_credit_card; $min_fee = $tax->min_fee_credit_card; }else{ $min_fee = "NULL"; }

        }elseif($client->currency == "usd"){

            $final_amount = number_format(($params['amount'] / $cotacao_dolar_markup),6,".","");
            $fixed_fee = number_format(($tax->credit_card_absolute),6,".","");
            $percent_fee = number_format(($final_amount * ($tax->credit_card_percent / 100)),6,".","");
            if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
            $comission = ($percent_fee + $fixed_fee);
            if($comission < $tax->min_fee_credit_card){ $comission = $tax->min_fee_credit_card; $min_fee = $tax->min_fee_credit_card; }else{ $min_fee = "NULL"; }

        }

        $number_card_bd = substr($params['card_number'],0,6)."******".substr($params['card_number'],12,4);
        $brand = strtolower($params['card_brand']);

        $exp_date = substr($params['card_expired'],0,2)."/".substr($params['card_expired'],2,4);
        $amount_clear = str_replace(",","",$params['amount']);
        $amount_clear = str_replace(".","",$amount_clear);

        $autenticate = "false";
        /* if($params['amount'] > "5.00"){
            $autenticate = "true";
        } */

        DB::beginTransaction();
        try {

            // Insert Transaction
            $transaction = Transactions::create([
                "solicitation_date" => $date,
                "final_date" => $date,
                "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_credit_card." days")),
                "code" => $params['pedido'],
                "client_id" => $params['client_id'],
                "order_id" => $params['order_id'],
                "user_id" => $params['user_id'],
                "user_document" => $params['user_document'],
                "user_account_data" => $user_account_data,
                "user_name" => $params['user_name'],
                "id_bank" => $bank->id,
                "type_transaction" => 'deposit',
                "method_transaction" => 'credit_card',
                "amount_solicitation" => $params['amount'],
                "final_amount" => $final_amount,
                // "percent_fee" => $percent_fee,
                // "fixed_fee" => $fixed_fee,
                // "comission" => $comission,
                // "min_fee" => $min_fee,
                "status" => 'pending',
                "code_bank" => $bank->code,
                "number_card" => $number_card_bd,
                "brand" => $brand,
                "quote" => $cotacao_dolar,
                "percent_markup" => $spread_deposit,
                "quote_markup" => $cotacao_dolar_markup,
            ]);

            DB::commit();

            $paramters = "
            {
            'MerchantOrderId':'".$params['order_id']."',
            'Customer':
            {
                'Identity':'".$params['user_document']."',
                'Name':'".$params['user_name']."',
                'Email':'".$params['user_email']."',
                'Phone':'".$params['user_phone']."'
            },
            'Payment':
            {
                'Type':'CreditCard',
                'Amount':".$amount_clear.",
                'Provider':'Cielo',
                'Authenticate': ".$autenticate.",
                'ReturnUrl':'".$params['return_url']."',
                'Installments':1,
                'CreditCard':
                {
                    'CardNumber':'".$params['card_number']."',
                    'Holder':'".$params['card_holder']."',
                    'ExpirationDate':'".$exp_date."',
                    'SecurityCode':'".$params['card_cvv']."',
                    'Brand':'".$params['card_brand']."'
                }
            },
            'Options':
            {
                'AntifraudEnabled':true
            }
            }
            ";

            curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.cieloecommerce.cielo.com.br/1/sales", // PROD
            //CURLOPT_URL => "https://apisandbox.cieloecommerce.cielo.com.br/1/sales", // SANDBOX
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $paramters,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "MerchantId: ".$merchantId."",
                "MerchantKey: ".$merchantKey."",
                "Postman-Token: 6bac7494-f187-4a72-ad1a-941034fa7d50",
                "cache-control: no-cache"
            ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if($err){
                return $err;
            } else {
                $retornado = json_decode($response,true);

                if(!isset($retornado['Payment'])){
                    $json_return = array("message" => "Error On Create Credit Card Transaction", "reason" => "Illegal Conditions", "return" => $retornado);
                    // $request_body = array("request_body" => $params);
                    $error_log_api = $this->error_log_api("deposit","credit_card",$params,$json_return,"422");

                    while($error_log_api != "success"){
                        $error_log_api = $this->error_log_api("deposit","credit_card",$params,$json_return,"422");
                    }

                    // set post fields
                    $post = [
                        "order_id" => $params['order_id'],
                        "user_id" => $params['user_id'],
                        "solicitation_date" => $date,
                        "cancel_date" => $date,
                        "fixed_fee" => "0.00",
                        "code_identify" => $params['code'],
                        "amount_solicitation" => $params['amount'],
                        "status" => "canceled",
                    ];

                    return response()->json($post,422);
                    exit();
                }

                if(isset($retornado['Payment']['ReturnMessage'])){
                    if($retornado['Payment']['ReturnMessage'] != "Transacao autorizada"){

                        $json_return = array("message" => $retornado['Payment']['ReturnMessage'], "code" => $retornado['Payment']['ReturnCode'], "reason" => "Illegal Conditions", "return" => $retornado);
                        // $request_body = array("request_body" => $params);
                        $error_log_api = $this->error_log_api("deposit","credit_card",$params,$json_return,"422");

                        while($error_log_api != "success"){
                            $error_log_api = $this->error_log_api("deposit","credit_card",$params,$json_return,"422");
                        }

                        // set post fields
                        $post = [
                            "order_id" => $params['order_id'],
                            "user_id" => $params['user_id'],
                            "solicitation_date" => $date,
                            "cancel_date" => $date,
                            "fixed_fee" => "0.00",
                            "code_identify" => $params['pedido'],
                            "amount_solicitation" => $params['amount'],
                            "status" => "canceled",
                        ];

                        return response()->json($post,422);
                        exit();

                    }
                }

                if(isset($rr[0]['Code'])){

                    $json_return = array("message" => $rr[0]['Message'], "code" => $rr[0]['Code'], "reason" => "Illegal Conditions");
                    // $request_body = array("request_body" => $params);
                    $error_log_api = $this->error_log_api("deposit","credit_card",$params,$json_return,"422");

                    while($error_log_api != "success"){
                        $error_log_api = $this->error_log_api("deposit","credit_card",$params,$json_return,"422");
                    }

                    // set post fields
                    $post = [
                        "order_id" => $params['order_id'],
                        "user_id" => $params['user_id'],
                        "solicitation_date" => $date,
                        "cancel_date" => $date,
                        "fixed_fee" => "0.00",
                        "code_identify" => $params['code'],
                        "amount_solicitation" => $params['amount'],
                        "status" => "canceled",
                    ];

                    return response()->json($post,422);
                    exit();

                }

                if(isset($retornado['Payment']['AuthenticationUrl'])){
                    if($retornado['Payment']['AuthenticationUrl'] != ""){
                        $link_callback_bank = $retornado['Payment']['AuthenticationUrl'];
                    }else{
                        $link_callback_bank = $params['return_url'];
                    }
                }else{
                    $link_callback_bank = $params['return_url'];
                }

                $dados = "";
                $payment_id = $retornado['Payment']['PaymentId'];

                $transaction->update([
                    "payment_id" => $payment_id,
                    "dados" => $dados,
                    "link_callback_bank" => $link_callback_bank,
                    "url_retorna" => $link_callback_bank
                ]);

                DB::commit();

                $json_return = array("order_id" => $params['order_id'],
                "solicitation_date" => $date,
                "due_date" => $this->datetostr(date("Y-m-d",strtotime($date."+ ".$days_safe_credit_card." days"))),
                "code_identify" => $params['pedido'],
                "amount" => $params['amount'],
                "fees" => $comission,
                "status" => "pending",
                "link_shop" => $params['return_url']
                );

                // Success
                return response()->json($json_return,200);

            }

        }catch(exception $e){
            DB::roolback();
        }

    }

    // Create deposit Cielo Debit Card
    public function createCieloDC($params = array(), $merchantId, $merchantKey){
        $curl = curl_init();

        $exp_date = substr($params['card_expired'],0,2)."/".substr($params['card_expired'],2,4);
        $amount_clear = str_replace(",","",$params['amount']);
        $amount_clear = str_replace(".","",$amount_clear);

        $paramters = "
        {
            'MerchantOrderId':'".$params['order_id']."',
            'Customer':
            {
                'Identity':'".$params['user_document']."',
                'Name':'".$params['user_name']."'
            },
            'Payment':
            {
                'Type':'DebitCard',
                'Amount':".$amount_clear.",
                'Provider':'Cielo',
                'Authenticate': false,
                'ReturnUrl':'".$params['return_url']."',
                'DebitCard':
                {
                    'CardNumber':'".$params['card_number']."',
                    'Holder':'".$params['card_holder']."',
                    'ExpirationDate':'".$exp_date."',
                    'SecurityCode':'".$params['card_cvv']."',
                    'Brand':'".$params['card_brand']."'
                }
            }
        }
        ";

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://api.cieloecommerce.cielo.com.br/1/sales", // PROD
          //CURLOPT_URL => "https://apisandbox.cieloecommerce.cielo.com.br/1/sales", // SANDBOX
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $paramters,
          CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "MerchantId: ".$merchantId."",
            "MerchantKey: ".$merchantKey."",
            "Postman-Token: 6bac7494-f187-4a72-ad1a-941034fa7d50",
            "cache-control: no-cache"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if($err){
            return $err;
        } else {
            $retornado = json_decode($response,true);

            if(isset($retornado[0]['Code'])){

                $json_return = array("message" => $retornado[0]['Message'], "reason" => "Illegal Conditions", "code" => $retornado[0]['Code']);
                return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
                exit();

            }

            if(isset($retornado['Payment']['ReturnMessage'])){
                if($retornado['Payment']['ReturnMessage'] != "Transacao capturada com sucesso"){

                    $json_return = array("message" => $retornado['Payment']['ReturnMessage'], "code" => $retornado['Payment']['ReturnCode'], "reason" => "Illegal Conditions");
                    // $request_body = array("request_body" => $params);
                    $error_log_api = $this->error_log_api("deposit","debit_card",$params,$json_return,"422");

                    while($error_log_api != "success"){
                        $error_log_api = $this->error_log_api("deposit","debit_card",$params,$json_return,"422");
                    }

                    return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
                    exit();

                }
            }

            if(isset($retornado['Payment']['AuthenticationUrl'])){
                if($retornado['Payment']['AuthenticationUrl'] != ""){
                    $link_callback_bank = $retornado['Payment']['AuthenticationUrl'];
                }else{
                    $link_callback_bank = $params['return_url'];
                }
            }else{
                $link_callback_bank = $params['return_url'];
            }

            $dados = "";
            $payment_id = $retornado['Payment']['PaymentId'];
            $date = date("Y-m-d H:i:s");

            $client = Clients::where("id","=",$params['client_id'])->first();
            $bank = $client->bankCreditCard;
            $days_safe_credit_card = $client->days_safe_credit_card;

            $user_data = array(
                "name" => $params['user_name'],
                "document" => $params['user_document'],
                "bank_name" => $bank->name,
                "holder" => $bank->holder,
                "agency" => $bank->agency,
                "account_number" => $bank->account,
                "operation_bank" => $bank->type_account,
                "user_id" => $params['user_id'],
                "client_id" => $params['client_id'],
                "address" => $this->trata_unicode($params['user_address']),
                "district" => $this->trata_unicode($params['user_address_district']),
                "city" => $this->trata_unicode($params['user_address_city']),
                "uf" => $params['user_address_state'],
                "cep" => $params['user_address_zipcode']
            );

            $user_account_data = base64_encode(json_encode($user_data));

            // Calulo Taxas //
            $cot_ar = $this->get_cotacao_dolar($row['client_id'],"deposit");
            $cotacao_dolar_markup = $cot_ar['markup'];
            $cotacao_dolar = $cot_ar['quote'];
            $spread_deposit = $cot_ar['spread'];

            // Taxas
            $tax = $client->tax;

            if($client->currency == "brl"){

                $cotacao_dolar_markup = "1";
                $cotacao_dolar = "1";
                $spread_deposit = "0";

                $final_amount = $params['amount'];
                $percent_fee = ($amount_fiat * ($tax->debit_card_percent / 100));
                $fixed_fee = $tax->debit_card_absolute;
                if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                $comission = ($percent_fee + $fixed_fee);
                if($comission < $tax->min_fee_debit_card){ $comission = $tax->min_fee_debit_card; $min_fee = $tax->min_fee_debit_card; }else{ $min_fee = "NULL"; }

            }elseif($client->currency == "usd"){

                $final_amount = number_format(($params['amount'] / $cotacao_dolar_markup),6,".","");
                $fixed_fee = number_format(($tax->debit_card_absolute),6,".","");
                $percent_fee = number_format(($final_amount * ($tax->debit_card_percent / 100)),6,".","");
                if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                $comission = ($percent_fee + $fixed_fee);
                if($comission < $tax->min_fee_debit_card){ $comission = $tax->min_fee_debit_card; $min_fee = $tax->min_fee_debit_card; }else{ $min_fee = "NULL"; }

            }

            DB::beginTransaction();
            try {

                // Insert Transaction
                $transaction = Transactions::create([
                    "solicitation_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_credit_card." days")),
                    "code" => $params['pedido'],
                    "client_id" => $params['client_id'],
                    "order_id" => $params['order_id'],
                    "user_id" => $params['user_id'],
                    "user_document" => $params['user_document'],
                    "user_account_data" => $user_account_data,
                    "user_name" => $params['user_name'],
                    "id_bank" => $bank->id,
                    "type_transaction" => 'deposit',
                    "method_transaction" => 'debit_card',
                    "amount_solicitation" => $params['amount'],
                    "final_amount" => $final_amount,
                    // "percent_fee" => $percent_fee,
                    // "fixed_fee" => $fixed_fee,
                    // "comission" => $comission,
                    // "min_fee" => $min_fee,
                    "status" => 'pending',
                    "data_bank" => $dados,
                    "payment_id" => $payment_id,
                    "code_bank" => $bank->code,
                    "link_callback_bank" => $link_callback_bank,
                    "url_retorna" => $link_callback_bank,
                    "quote" => $cotacao_dolar,
                    "percent_markup" => $spread_deposit,
                    "quote_markup" => $cotacao_dolar_markup,
                ]);

                DB::commit();

                $json_return = array("order_id" => $params['order_id'],
                "solicitation_date" => $date,
                "due_date" => $this->datetostr(date("Y-m-d",strtotime($date."+ ".$days_safe_credit_card." days"))),
                "code_identify" => $params['pedido'],
                "amount" => $params['amount'],
                "fees" => $comission,
                "status" => "pending",
                "link_shop" => $params['return_url']
                );

                // Success
                return response()->json($json_return,200);

            }catch(exception $e){
                DB::roolback();
            }


        }

    }

    public function createTransactionMP($checkUser = array(),$params = array(),$accessTokenMP){

        $post = array(
            "token" => $checkUser['token_credit_card'],
            "transaction_amount" => floatval($params['amount']),
            "description" => "Crédito em FastPayments",
            "external_reference" => $params['order_id'],
            "payment_method_id" => strtolower($params['card_brand']),
            "installments" => floatval(1),
            "binary_mode" => true,
            "payer" => array(
                "email" => $params['user_email'],
                "first_name" => $checkUser['first_name'],
                "last_name" => $checkUser['last_name'],
                "identification" => array(
                    "type" => "CPF",
                    "number" => $params['user_document']
                ),
                "address" => array(
                    "zip_code" => $params['user_address_zipcode'],
                    "street_name" => $params['user_address'],
                    "street_number" => strval($params['user_address_number']),
                    "neighborhood" => $params['user_address_district'],
                    "city" => $params['user_address_city'],
                    "federal_unit" => $params['user_address_state']
                )
            ),
            "notification_url" => "https://mp.FastPayments.com/webhook_mp",
            "additional_info" => array(
                "items" => [
                    array(
                        "id" => "CREDA4P",
                        "title" => "Crédito em FastPayments",
                        "description" => "Crédito em FastPayments",
                        "picture_url" => "https://http2.mlstatic.com/resources/frontend/statics/growth-sellers-landings/device-mlb-point-i_medium@2x.png",
                        // "category_id" => "fashion",
                        "quantity" => floatval(1),
                        "unit_price" => floatval($params['amount'])
                    )
                ],
                "payer" => array(
                    "first_name" => $checkUser['first_name'],
                    "last_name" => $checkUser['last_name'],
                    "phone" => array(
                        "area_code" => $checkUser['area_code'],
                        "number" => $checkUser['number']
                    ),
                    "address" => array(
                        "zip_code" => $checkUser['zip_code'],
                        "street_name" => $checkUser['street_name'],
                        "street_number" => $checkUser['street_number']
                    ),
                    "registration_date" => date("Y-m-d")."T".date("H:i:s")."000-03:00",
                ),
                "shipments" => array(
                    "receiver_address" => array(
                        "zip_code" => "01419100",
                        "street_name" => "Alameda Santos - Cerqueira Cesar",
                        "street_number" => "1767",
                    )
                )
            )
        );

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.mercadopago.com/v1/payments?access_token=".$accessTokenMP,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($post),
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "Content-length: ".strlen(json_encode($post))
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    // Registro Mercado Pago Credit Card
    public function createMPCC($params = array(), $publicKeyMP, $accessTokenMP){

        $date = date("Y-m-d H:i:s");

        $pedido = "";
        $link_callback_bank = $params['return_url'];
        $dados = "";
        $amount_fiat = $params['amount'];

        $client = Clients::where("id","=",$params['client_id'])->first();
        $bank = $client->bankCreditCard;
        $days_safe_credit_card = $client->days_safe_credit_card;

        $user_data = array(
            "name" => $params['user_name'],
            "document" => $params['user_document'],
            "bank_name" => $bank->name,
            "holder" => $bank->holder,
            "agency" => $bank->agency,
            "account_number" => $bank->account,
            "operation_bank" => $bank->type_account,
            "user_id" => $params['user_id'],
            "client_id" => $params['client_id'],
            "address" => $this->trata_unicode($params['user_address']),
            "district" => $this->trata_unicode($params['user_address_district']),
            "city" => $this->trata_unicode($params['user_address_city']),
            "uf" => $params['user_address_state'],
            "cep" => $params['user_address_zipcode']
        );

        $user_account_data = base64_encode(json_encode($user_data));

        // Calulo Taxas //
        $cot_ar = $this->get_cotacao_dolar($params['client_id'],"deposit");
        $cotacao_dolar_markup = $cot_ar['markup'];
        $cotacao_dolar = $cot_ar['quote'];
        $spread_deposit = $cot_ar['spread'];

        // Taxas
        $tax = $client->tax;

        if($client->currency == "brl"){

            $final_amount = $params['amount'];
            $percent_fee = ($final_amount * ($tax->credit_card_percent / 100));
            $fixed_fee = $tax->credit_card_absolute;
            if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
            $comission = ($percent_fee + $fixed_fee);
            if($comission < $tax->min_fee_credit_card){ $comission = $tax->min_fee_credit_card; $min_fee = $tax->min_fee_credit_card; }else{ $min_fee = "NULL"; }

        }elseif($client->currency == "usd"){

            $final_amount = number_format(($params['amount'] / $cotacao_dolar_markup),6,".","");
            $fixed_fee = number_format(($tax->credit_card_absolute),6,".","");
            $percent_fee = number_format(($final_amount * ($tax->credit_card_percent / 100)),6,".","");
            if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
            $comission = ($percent_fee + $fixed_fee);
            if($comission < $tax->min_fee_credit_card){ $comission = $tax->min_fee_credit_card; $min_fee = $tax->min_fee_credit_card; }else{ $min_fee = "NULL"; }

        }

        $credit_card_modify = substr($params['card_number'],0,6);
        $credit_card_modify = $credit_card_modify."******".substr($params['card_number'],12,4);
        $brand = strtolower($params['card_brand']);

        $days_safe_credit_card = $client->days_safe_credit_card;

        switch($FunctionsAPIController->dia_semana(date("Y-m-d"))){
            case"sex": $days_safe_credit_card = ($days_safe_credit_card + 2); break;
            case"sab": $days_safe_credit_card = ($days_safe_credit_card + 1); break;
            case"dom": $days_safe_credit_card = ($days_safe_credit_card + 1); break;
        }

        $date_confirmed_bank = date("Y-m-d",strtotime("+".$days_safe_credit_card." days"))." 00:00:00";

        DB::beginTransaction();
        try{

            // Insert Transaction
            $transaction = Transactions::create([
                "solicitation_date" => $date,
                "final_date" => $date,
                "disponibilization_date" => $date_confirmed_bank,
                "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_credit_card." days")),
                "client_id" => $params['client_id'],
                "order_id" => $params['order_id'],
                "user_id" => $params['user_id'],
                "user_document" => $params['user_document'],
                "user_account_data" => $user_account_data,
                "user_name" => $params['user_name'],
                "id_bank" => $bank->id,
                "type_transaction" => 'deposit',
                "method_transaction" => 'credit_card',
                "amount_solicitation" => $params['amount'],
                "final_amount" => $final_amount,
                // "percent_fee" => $percent_fee,
                // "fixed_fee" => $fixed_fee,
                // "comission" => $comission,
                // "min_fee" => $min_fee,
                "status" => "pending",
                "data_bank" => $dados,
                "code_bank" => $bank->code,
                "link_callback_bank" => $link_callback_bank,
                "url_retorna" => $link_callback_bank,
                "card_number" => $credit_card_modify,
                "card_brand" => $brand,
                "quote" => $cotacao_dolar,
                "percent_markup" => $spread_deposit,
                "quote_markup" => $cotacao_dolar_markup
            ]);

            DB::commit();

            $checkUser = $this->checkDataCreditCardMP($params,$publicKeyMP,$accessTokenMP);

            $this->registerRecivedsRequests("/var/www/html/nexapay/logs/create-new-user-mp.txt",json_encode($checkUser));

            $createTransactionMP = json_decode($this->createTransactionMP($checkUser,$params,$accessTokenMP),true);

            $this->registerRecivedsRequests("/var/www/html/nexapay/logs/create-transaction-mp.txt",json_encode($createTransactionMP));

            if(isset($createTransactionMP['error'])){

                $json_return = array("message" => "Error on create transactions credit card", "reason" => "Illegal Conditions", "return" => $createTransactionMP);
                return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
                exit();

            }

            switch($createTransactionMP['status']){
                case"approved": $status = "confirmed"; break;
                case"in_process": $status = "await"; break;
                // case"rejected": $status = "cancel"; break;
                default: $status = "await";
            }

            $pedido = $createTransactionMP['id'];
            $dados = json_encode($createTransactionMP);

            if($status == "confirmed"){
                // Update Transactions

                $transaction->update([
                    "final_date" => $date,
                    "amount_confirmed" => $params['amount'],
                    "final_amount" => $final_amount,
                    "code" => $pedido,
                    "dados" => $dados,
                    "status" => $status,
                ]);

                DB::commit();

                // set post fields
                $post = [
                    "order_id" => $params['order_id'],
                    "user_id" => $params['user_id'],
                    "solicitation_date" => $date,
                    "paid_date" => $date,
                    "code_identify" => $params['pedido'],
                    "amount_solicitation" => $params['amount'],
                    "amount_confirmed" => $params['amount'],
                    "status" => $status
                ];

                $post_field = json_encode($post);

                $ch = curl_init($client->key->url_callback_shop);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'Token:'.$client->key->authorization));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

                // execute!
                $response = curl_exec($ch);
                $http_status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                // close the connection, release resources used
                curl_close($ch);

                if($http_status == "200"){

                    $transaction->update([
                        "confirmation_callback" => "1"
                    ]);
                    DB::commit();

                }

                $ch2 = curl_init("https://webhook.site/8585f53e-f752-46f0-a3b2-2a636f950f8d");
                curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch2, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'Token:'.$client->key->authorization));
                curl_setopt($ch2, CURLOPT_POSTFIELDS, $post_field);

                // execute!
                $response2 = curl_exec($ch2);
                $http_status2  = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

                // close the connection, release resources used
                curl_close($ch2);

                $post_var = [
                    "date_send_callback" => date("Y-m-d H:i:s",strtotime("-3 hours")),
                    "type" => "credit_card_mp",
                    "order_id" => $params['order_id'],
                    "user_id" => $params['user_id'],
                    "solicitation_date" => $date,
                    "paid_date" => $date,
                    "code_identify" => $params['pedido'],
                    "amount_solicitation" => $params['amount'],
                    "amount_confirmed" => $params['amount'],
                    "status" => $status,
                    "response_http_server" => $http_status,
                    "response_server" => $response
                ];

                $post_var = json_encode($post_var);

                $fp = fopen('send-callback-f1boleto.txt', 'a');
                fwrite($fp, $post_var."\n");
                fclose($fp);

                $json_return = array(
                    "order_id" => $params['order_id'],
                    "solicitation_date" => $date,
                    "due_date" => $this->datetostr(date("Y-m-d",strtotime($date."+ ".$days_safe_credit_card." days"))),
                    "code_identify" => $params['pedido'],
                    "amount" => $params['amount'],
                    "fees" => $comission,
                    "status" => $status,
                    "link_shop" => $params['return_url']
                );

                // Success
                return response()->json($json_return,200);

            }elseif($status == "await"){

                $cancel_transaction_mp = json_decode($this->cancelTransactionCreditCardMP($pedido),true);

                if(isset($cancel_transaction_mp['status'])){

                    $date_cancel = date("Y-m-d H:i:s");

                    if($cancel_transaction_mp['status'] == "cancelled"){

                        $transaction->update([
                            "status" => "canceled",
                            "cancel_date" => $date_cancel,
                            "final_date" => $date_cancel
                        ]);

                        DB::commit();

                        // $json_return = array(
                        //     "order_id" => $params['order_id'],
                        //     "solicitation_date" => $date,
                        //     "due_date" => $this->datetostr(date("Y-m-d",strtotime($date."+ ".$days_safe_credit_card." days"))),
                        //     "code_identify" => $params['pedido'],
                        //     "amount" => $params['amount'],
                        //     "fees" => $comission,
                        //     "status" => "canceled",
                        //     "link_shop" => $params['return_url']
                        // );

                        // // Success
                        // return response()->json($json_return,200);

                        $json_return = array("message" => "Transaction failed", "reason" => "Illegal Conditions", "code" => "5541");
                        return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
                        exit();

                    }

                }else{
                    // $json_return = array(
                    //     "order_id" => $params['order_id'],
                    //     "solicitation_date" => $date,
                    //     "due_date" => $this->datetostr(date("Y-m-d",strtotime($date."+ ".$days_safe_credit_card." days"))),
                    //     "code_identify" => $params['pedido'],
                    //     "amount" => $params['amount'],
                    //     "fees" => $comission,
                    //     "status" => "canceled",
                    //     "link_shop" => $params['return_url']
                    // );

                    // // Success
                    // return response()->json($json_return,200);

                    $json_return = array("message" => "Transaction failed", "reason" => "Illegal Conditions", "code" => "5541");
                    return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
                    exit();
                }

            }

            // Update Transactions

        }catch(exception $e){
            DB::rollback();
        }


    }

    public function cancelTransactionCreditCardMP($payment_id){

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.mercadopago.com/v1/payments/".$payment_id."?access_token=APP_USR-551779752251017-063014-7ee044a42bbf39bd20e5f908352e3c5d-568800563",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_POSTFIELDS => json_encode(array("status" => "cancelled")),
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json"
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function createCreditcard($params = array(),$publicKeyMP,$accessTokenMP){

        $post = array(
            "card_number" => $params['card_number'],
            "expiration_month" => substr($params['card_expired'],0,2),
            "expiration_year" => substr($params['card_expired'],2,4),
            "security_code" => $params['security_code'],
            "cardholder" => array(
                "identification" => array(
                    "type" => "CPF",
                    "number" => $params['user_document']
                ),
                "name" => $params['card_holder']
            ),
        );

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.mercadopago.com/v1/card_tokens?public_key=".$publicKeyMP,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($post,true),
        CURLOPT_HTTPHEADER => array(
        "Content-Type: application/json"
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function associationClientCard($costumer,$token,$publicKeyMP,$accessTokenMP){

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.mercadopago.com/v1/customers/".$costumer."/cards?access_token=".$accessTokenMP,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode(array("token" => $token),true),
        CURLOPT_HTTPHEADER => array(
        "Content-Type: application/json"
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function createClientMP($params = array(),$publicKeyMP,$accessTokenMP){

        $user_address = $params['user_address'];
        $user_district = $params['user_address_district'];
        $user_city = $params['user_address_city'];
        $user_uf = $params['user_address_state'];
        $user_cep = $params['user_address_zipcode'];
        $user_number = $params['user_address_number'];

        $first_name = explode(" ",$params['user_name'])[0];
        $last_name = explode(" ",$params['user_name'])[1];
        $full_name = $first_name." ".$last_name;

        $paramters = array(
            "email" => $params['user_email'],
            "first_name" => $first_name,
            "last_name" => $last_name,
            "phone" => array(
                "area_code" => "011",
                "number" => $params['user_phone']
            ),
            "identification" => array(
                "type" => "CPF",
                "number" => $params['user_document']
            ),
            "address" => array(
                "zip_code" => $user_cep,
                "street_name" => $user_address,
                "street_number" => $user_number,
            ),
            "description" => "",
        );

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.mercadopago.com/v1/customers?access_token=".$accessTokenMP,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($paramters,true),
        CURLOPT_HTTPHEADER => array(
        "Content-Type: application/json"
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        if(!isset($response)){

            $json_return = array("message" => "response empty", "reason" => "Illegal Conditions", "error" => json_decode($response,true));
            return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
            exit();

        }else{

            $rp = json_decode($response,true);
            if(!isset($rp['id'])){
                return $response;
                exit();
            }
            $user_id_mp = $rp['id'];

            if(isset($rp['status'])){

                $json_return = array("message" => "There was a problem registering the user", "reason" => "Illegal Conditions", "error" => $rp);
                return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
                exit();

            }

            $credit_card_modify = substr($params['card_number'],0,6);
            $credit_card_modify = $credit_card_modify."******".substr($params['card_number'],12,4);

            DB::beginTransaction();
            try{

                $insert_client = UserDataMP::create([
                    "client_id" => $params['client_id'],
                    "user_id" => $params['user_id'],
                    "user_document" => $params['user_document'],
                    "user_name" => $params['user_name'],
                    "user_zipcode" => $params['user_address_zipcode'],
                    "user_street" => $params['user_address'],
                    "user_number" => $params['user_address_number'],
                    "user_id_mp" => $user_id_mp,
                ]);

                DB::commit();

                $paramters_credit_card = array(
                    "card_number" => $params['card_number'],
                    "card_expired" => $params['card_expired'],
                    "security_code" => $params['card_cvv'],
                    "user_document" => $params['user_document'],
                    "card_holder" => $params['card_holder']
                );

                $credit_card = $this->createCreditcard($paramters_credit_card,$publicKeyMP,$accessTokenMP);

                if(!isset($credit_card)){

                    $json_return = array("message" => "response empty", "reason" => "Illegal Conditions", "error" => json_decode($credit_card,true));
                    return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
                    exit();

                }else{

                    $cred = json_decode($credit_card,true);
                    $id_credit_card = $cred['id'];

                    $credit_card_modify = substr($params['card_number'],0,6);
                    $credit_card_modify = $credit_card_modify."******".substr($params['card_number'],12,4);

                    DB::beginTransaction();
                    try {

                        // Insert Transaction
                        $insert_credit_card = UserCreditCardMP::create([
                            "user_id" => $params['user_id'],
                            "card_number" => $credit_card_modify,
                            "card_expired" => $params['card_expired'],
                            "token_card_mp" => $id_credit_card,
                            "card_brand" => $params['card_brand'],
                        ]);

                        DB::commit();

                        // Associate Credit Card
                        $associate = $this->associationClientCard($user_id_mp,$id_credit_card,$publicKeyMP,$accessTokenMP);

                        if(isset($associate)){

                            $as = json_decode($associate,true);
                            $id_associate = $as['id'];

                            // Insert Transaction
                            $insert_credit_card->update([
                                "association_id" => $id_associate
                            ]);

                            DB::commit();

                        }

                    }catch(exception $e){
                        DB::roolback();
                    }

                }

            }catch(exception $e){
                DB::roolback();
            }

        }

        // return data user transaction

        $response = array(
            "id_client_mp" => $user_id_mp,
            "token_credit_card" => $id_credit_card,
            "user_email" => $params['user_email'],
            "user_brand" => $params['card_brand'],
            "first_name" => $first_name,
            "last_name" => $last_name,
            "zip_code" => $user_cep,
            "street_name" => $user_address,
            "street_number" => $user_number,
            "area_code" => "011",
            "number" => $params['user_phone'],
        );

        return $response;

    }

    public function checkDataCreditCardMP($params = array(),$publicKeyMP,$accessTokenMP){

        $user_document = $params['user_document'];

        $sql_user = UserDataMP::where("user_document","=",$user_document)->first();
        if(!empty($sql_user)){

            if($sql_user->user_document != $params['user_document']){
                $json_return = array("message" => "Incorrect user data", "reason" => "Illegal Conditions", "error" => "The user's CPF number does not match previous recorded data");
                return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
                exit();
            }

            $paramters_credit_card = array(
				"card_number" => $params['card_number'],
				"card_expired" => $params['card_expired'],
				"security_code" => $params['card_cvv'],
				"user_document" => $params['user_document'],
				"card_holder" => $params['card_holder']
			);

            $credit_card = $this->createCreditcard($paramters_credit_card,$publicKeyMP,$accessTokenMP);

            if(!isset($credit_card)){

                $json_return = array("message" => "response empty", "reason" => "Illegal Conditions", "error" => json_decode($credit_card,true));
                return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
                exit();

			}else{

                $cred = json_decode($credit_card,true);
                $id_credit_card = $cred['id'];

                $credit_card_modify = substr($params['card_number'],0,6);
				$credit_card_modify = $credit_card_modify."******".substr($params['card_number'],12,4);

                DB::beginTransaction();
                try {

                    // Insert Transaction
                    $insert_credit_card = UserCreditCardMP::create([
                        "user_id" => $params['user_id'],
                        "card_number" => $credit_card_modify,
                        "card_expired" => $params['card_expired'],
                        "token_card_mp" => $id_credit_card,
                        "card_brand" => $params['card_brand'],
                    ]);

                    DB::commit();

                    // Associate Credit Card
                    $associate = $this->associationClientCard($sql_user->user_id_mp,$id_credit_card,$publicKeyMP,$accessTokenMP);

                    if(isset($associate)){

						$as = json_decode($associate,true);
                        $id_associate = $as['id'];

                        // Insert Transaction
                        $insert_credit_card->update([
                            "association_id" => $id_associate
                        ]);

                    }

                    DB::commit();

                }catch(exception $e){
                    DB::roolback();
                }

            }

            // return data user transaction
            $client_data = array(
                "user_id_mp" => $sql_user->user_id_mp,
                "id_client_mp" => $sql_user->user_id_mp,
                "token_credit_card" => $id_credit_card,
                "user_email" => $params['user_email'],
                "user_brand" => $params['card_brand'],
                "first_name" => explode(" ",$sql_user->user_name)[0],
                "last_name" => explode(" ",$sql_user->user_name)[1],
                "zip_code" => $sql_user->user_zipcode,
                "street_name" => $sql_user->user_street,
                "street_number" => $sql_user->user_number,
                "area_code" => "011",
                "number" => $params['user_phone'],
            );

        }else{
            $client_data = $this->createClientMP($params,$publicKeyMP,$accessTokenMP);
        }

        return $client_data;

    }

    // Get Token BS2
    public function getTokenBS2($username_bs2,$password_bs2,$client_id_bs2,$client_secret_bs2,$type_token,$bank_id){

        // switch($type_token){
        //     case"prod": $url_token = "https://api.bs2.com/auth/oauth/v2/token"; break;
        //     case"homolog": $url_token = "https://apihmz.bancobonsucesso.com.br/auth/oauth/v2/token"; break;
        // }

        // $curl = curl_init();

        // curl_setopt_array($curl, array(
        //   CURLOPT_URL => $url_token,
        //   CURLOPT_RETURNTRANSFER => true,
        //   CURLOPT_ENCODING => "",
        //   CURLOPT_SSL_VERIFYHOST => 0,
        //   CURLOPT_SSL_VERIFYPEER => 0,
        //   CURLOPT_MAXREDIRS => 10,
        //   CURLOPT_TIMEOUT => 0,
        //   CURLOPT_FOLLOWLOCATION => true,
        //   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //   CURLOPT_CUSTOMREQUEST => "POST",
        //   CURLOPT_POSTFIELDS => "grant_type=password&scope=forintegration&username=".$username_bs2."&password=".$password_bs2,
        //   CURLOPT_HTTPHEADER => array(
        //     "Content-Type: application/x-www-form-urlencoded",
        //     "Authorization: Basic ".base64_encode($client_id_bs2.":".$client_secret_bs2)
        //   ),
        // ));

        // $response = curl_exec($curl);

        // curl_close($curl);

        // return $response;

        // switch($type_token){
        //     case"prod": $url_token = "https://api.bs2.com/auth/oauth/v2/token"; break;
        //     case"homolog": $url_token = "https://apihmz.bancobonsucesso.com.br/auth/oauth/v2/token"; break;
        // }

        // $curl = curl_init();

        $bank = Banks::where("id",$bank_id)->first();
        $refresh_token = $bank->refresh_token_bs2;

        curl_setopt_array($curl, array(
          CURLOPT_URL => $url_token,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => "grant_type=refresh_token&scope=extrato&refresh_token=".$refresh_token,
          CURLOPT_HTTPHEADER => array(
            "Content-Type: application/x-www-form-urlencoded",
            "Authorization: Basic ".base64_encode($client_id_bs2.":".$client_secret_bs2)
          ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $dec = json_decode($response,true);

        DB::beginTransaction();
        try{
            $bank->update([
                "token_bs2" => $dec['access_token'],
                "refresh_token_bs2" => $dec['refresh_token']
            ]);

            DB::commit();
        }catch(Exception $e){
            DB::rollback();
        }

        return $response;

    }

    public function getTokenBS2NEW($bank_id){

        $bank = Banks::where("id",$bank_id)->first();


        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.bs2.com/pj/forintegration/banking/v1/contascorrentes/principal/saldo',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$bank->token_bs2,
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        if($response == "Invalid token"){

            \App\Jobs\GetTokenBS2::dispatch($bank_id)->delay(now()->addSeconds('1'));
            sleep(5);
            $getNew = $this->getTokenBS2NEW($bank_id);

            return $getNew;

        }else{
            return $bank->token_bs2;
        }

    }

    // Get Token BS2 PIX
    public function getTokenBS2PIX($client_id_bs2,$client_secret_bs2,$type_token){

        switch($type_token){
            case"prod": $url_token = "https://api.bs2.com/auth/oauth/v2/token"; break;
            case"homolog": $url_token = "https://apihmz.bancobonsucesso.com.br/auth/oauth/v2/token"; break;
        }

        $authentication = base64_encode($client_id_bs2.":".$client_secret_bs2);

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $url_token,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_SSL_VERIFYHOST => 0,
          CURLOPT_SSL_VERIFYPEER => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => "scope=cob.write%20cob.read%20pix.write%20pix.read%20webhook.read%20webhook.write&grant_type=client_credentials",
          CURLOPT_HTTPHEADER => array(
            "Content-Type: application/x-www-form-urlencoded",
            "Authorization: Basic ".$authentication
          ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function register_boleto_asaas($params = array()){

        $access_token_asaas = $params['access_token_asaas'];

        $register = json_decode($this->create_invoice_asaas($params,$access_token_asaas),true);

        if(isset($register['id'])){

            $get_payload = json_decode($this->getPayloadBoletoAsaas($register['id'],$access_token_asaas),true);

            $bar_code_number = $get_payload['identificationField'];
            $dados = $get_payload['nossoNumero'];

            return $this->createBoletoAsaas($params,$bar_code_number,$dados);

        }else{

            // $request_body = array("request_body" => $params);
            $json_return = array("message" => "Error on create Invoice", "reason" => "IllegalConditions", "return" => $register);

            $error_log_api = $this->error_log_api("deposit","invoice",$params,$json_return,"422");

            while($error_log_api != "success"){
                $error_log_api = $this->error_log_api("deposit","invoice",$params,$json_return,"422");
            }

            header('HTTP/1.0 422 Unauthorized');
            $json = json_encode($json_return);
            die($json);
            exit();
        }

    }

    public function create_invoice_asaas($params = array(),$access_token_asaas){

        $data = [
            "customer" => $params['customer_id'],
            "billingType" => "BOLETO",
            "dueDate" => $params['data_vencimento'],
            "value" => floatval($params['amount']),
            "description" => $params['pedido'],
            "externalReference" => $params['pedido'],
            "discount" => array(
                "value" => 0,
                "dueDateLimitDays" => 0
            ),
            "fine" => array(
                "value" => 0
            ),
            "interest" => array(
                "value" => 0
            ),
            "postalService" => false
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://www.asaas.com/api/v3/payments',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data,true),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'access_token: '.$access_token_asaas,
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function createBoletoAsaas($params = array(),$bar_code_number,$dados){

        $dados = $dados;
        $date = date("Y-m-d H:i:s");
        $barcode = $bar_code_number;

        // GET ALL CLIENT
        $clients = Clients::where("id","=",$params['client_id'])->first();
        $days_safe_boleto = $clients->days_safe_boleto;

        // GET BANK DATA
        $bank = $clients->bankInvoice;
        $link_callback_bank = "";

        $user_data = array(
            "name" => $params['nome_usuario'],
            "document" => $params['documento_usuario'],
            "bank_name" => $bank->name,
            "holder" => $bank->holder,
            "agency" => $bank->agency,
            "account_number" => $bank->account,
            "operation_bank" => $bank->type_account,
            "user_id" => $params['user_id'],
            "client_id" => $params['client_id'],
            "address" => $this->trata_unicode($params['endereco_usuario']),
            "district" => $this->trata_unicode($params['bairro_usuario']),
            "city" => $this->trata_unicode($params['cidade_usuario']),
            "uf" => $params['uf_usuario'],
            "cep" => $params['cep_usuario']
        );

        $user_account_data = base64_encode(json_encode($user_data));

        // Taxas
        $tax = $clients->tax;

        $cotacao_dolar_markup = "1";
        $cotacao_dolar = "1";
        $spread_deposit = "0";

        $final_amount = $params['amount'];
        $percent_fee = ($final_amount * ($tax->pix_percent / 100));
        $fixed_fee = $tax->pix_absolute;
        if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
        $comission = ($percent_fee + $fixed_fee);
        if($comission < $tax->min_fee_pix){ $comission = $tax->min_fee_pix; $min_fee = $tax->min_fee_pix; }else{ $min_fee = "NULL"; }

        $data_invoice_id = NULL;

        DB::beginTransaction();
        try {

            // Insert Transaction
            $transaction = Transactions::create([
                "solicitation_date" => $date,
                "final_date" => $date,
                "due_date" => $params['data_vencimento']." 00:00:00",
                "code" => $params['pedido'],
                "client_id" => $params['client_id'],
                "order_id" => $params['order_id'],
                "user_id" => $params['user_id'],
                "user_document" => $params['documento_usuario'],
                "user_account_data" => $user_account_data,
                "user_name" => $params['nome_usuario'],
                "id_bank" => $clients->bankInvoice->id,
                "type_transaction" => 'deposit',
                "method_transaction" => 'invoice',
                "amount_solicitation" => $params['amount'],
                "final_amount" => $final_amount,
                "status" => 'pending',
                "data_bank" => $dados,
                "payment_id" => "",
                "code_bank" => "100",
                "link_callback_bank" => $link_callback_bank,
                "url_retorna" => "",
                "data_invoice_id" => $data_invoice_id,
                "quote" => $cotacao_dolar,
                "percent_markup" => $spread_deposit,
                "quote_markup" => $cotacao_dolar_markup,
            ]);

            DB::commit();

            if($barcode != ""){

                // Insert Data Invoice
                $datainvoice = DataInvoice::create([
                    "transaction_id" => $transaction->id,
                    "client_id" => $params['client_id'],
                    "order_id" => $params['order_id'],
                    "barcode" => $barcode,
                    "date_time" => $date
                ]);

            }

            $link_invoice = "https://invoice.fastpayments.com.br/invfastpayments/".$params['authorization']."/".$params['order_id'];

            if($barcode == ""){
                $json_return = array(
                    "order_id" => $params['order_id'],
                    "solicitation_date" => $date,
                    "due_date" => $params['data_vencimento'],
                    "code_identify" => $params['pedido'],
                    "amount" => $params['amount'],
                    "bank_name" => $bank->name,
                    "holder" => $bank->holder,
                    "agency" => $bank->agency,
                    "type_account" => $bank->type_account,
                    "account" => $bank->account,
                    "status" => "pending",
                    "link_invoice" => $link_invoice
                );

            }else{
                $json_return = array(
                    "order_id" => $params['order_id'],
                    "solicitation_date" => $date,
                    "due_date" => $params['data_vencimento'],
                    "code_identify" => $params['pedido'],
                    "amount" => $params['amount'],
                    "bank_name" => $bank->name,
                    "holder" => $bank->holder,
                    "agency" => $bank->agency,
                    "type_account" => $bank->type_account,
                    "account" => $bank->account,
                    "status" => "pending",
                    "link_invoice" => $link_invoice,
                    "bar_code" => $barcode
                );
            }

            // Success
            return response()->json($json_return,200);

        }catch(exception $e){
            DB::roolback();
        }

    }

    public function getPayloadBoletoAsaas($id,$access_token_asaas){

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://www.asaas.com/api/v3/payments/'.$id.'/identificationField',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'access_token: '.$access_token_asaas,
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function register_boleto_bs2($params = array()){

        $token_bs2 = $this->getTokenBS2NEW($params['bank_id']);

        // $tk = json_decode($this->getTokenBS2($params['username_bs2'],$params['password_bs2'],$params['client_id_bs2'],$params['client_secret_bs2'],"prod",$params['bank_id']),true);

        // if(isset($tk)){

            // $token_bs2 = $tk['access_token'];

            $register = json_decode($this->create_invoice_bs2($params,$token_bs2),true);

            if(isset($register)){

                if(!isset($register['linhaDigitavel'])){

                    // $request_body = array("request_body" => $params);
                    $json_return = array("message" => "Error on create Invoice", "reason" => "IllegalConditions", "return" => $register);

                    $error_log_api = $this->error_log_api("deposit","invoice",$params,$json_return,"422");

                    while($error_log_api != "success"){
                        $error_log_api = $this->error_log_api("deposit","invoice",$params,$json_return,"422");
                    }

                    return response()->json($json_return,422);
                    exit();

                }

                $bar_code_number = $register['linhaDigitavel'];
                $dados = $register['nossoNumero'];

                return $this->createBoletoBS2($params,$bar_code_number,$dados);

            }else{

                // $request_body = array("request_body" => $params);
                $json_return = array("message" => "Error on create Invoice", "reason" => "IllegalConditions", "return" => $register);

                $error_log_api = $this->error_log_api("deposit","invoice",$params,$json_return,"422");

                while($error_log_api != "success"){
                    $error_log_api = $this->error_log_api("deposit","invoice",$params,$json_return,"422");
                }

                header('HTTP/1.0 422 Unauthorized');
                $json = json_encode($json_return);
                die($json);
                exit();
            }

        // }else{

        // }

    }

    public function createBoletoBS2($params = array(),$barCode,$dados){

        $dados = $dados;
        $date = date("Y-m-d H:i:s");
        $barcode = $barCode;

        // GET ALL CLIENT
        $clients = Clients::where("id","=",$params['client_id'])->first();
        $days_safe_boleto = $clients->days_safe_boleto;

        // GET BANK DATA
        $bank = $clients->bankInvoice;
        $link_callback_bank = "";

        $user_data = array(
            "name" => $params['nome_usuario'],
            "document" => $params['documento_usuario'],
            "bank_name" => $bank->name,
            "holder" => $bank->holder,
            "agency" => $bank->agency,
            "account_number" => $bank->account,
            "operation_bank" => $bank->type_account,
            "user_id" => $params['user_id'],
            "client_id" => $params['client_id'],
            "address" => $this->trata_unicode($params['endereco_usuario']),
            "district" => $this->trata_unicode($params['bairro_usuario']),
            "city" => $this->trata_unicode($params['cidade_usuario']),
            "uf" => $params['uf_usuario'],
            "cep" => $params['cep_usuario']
        );

        $user_account_data = base64_encode(json_encode($user_data));

        // Calulo Taxas //
        $cot_ar = $this->get_cotacao_dolar($params['client_id'],"deposit");
        $cotacao_dolar_markup = $cot_ar['markup'];
        $cotacao_dolar = $cot_ar['quote'];
        $spread_deposit = $cot_ar['spread'];

        // Taxas
        $tax = $clients->tax;

        if($clients->currency == "brl"){

            $cotacao_dolar_markup = "1";
            $cotacao_dolar = "1";
            $spread_deposit = "0";

            $final_amount = $params['amount'];
            $percent_fee = ($final_amount * ($tax->boleto_percent / 100));
            $fixed_fee = $tax->boleto_absolute;
            if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
            $comission = ($percent_fee + $fixed_fee);
            if($comission < $tax->min_fee_boleto){ $comission = $tax->min_fee_boleto; $min_fee = $tax->min_fee_boleto; }else{ $min_fee = "NULL"; }

        }elseif($clients->currency == "usd"){

            $final_amount = number_format(($params['amount'] / $cotacao_dolar_markup),6,".","");
            $fixed_fee = number_format(($tax->boleto_absolute),6,".","");
            $percent_fee = number_format(($final_amount * ($tax->boleto_percent / 100)),6,".","");
            if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
            $comission = ($percent_fee + $fixed_fee);
            if($comission < $tax->min_fee_boleto){ $comission = $tax->min_fee_boleto; $min_fee = $tax->min_fee_boleto; }else{ $min_fee = "NULL"; }

        }

        $data_invoice_id = NULL;

        DB::beginTransaction();
        try {

            if($barcode != ""){

                if(is_numeric($clients->key->minamount_boletofirst)){
                    $minamount = $clients->key->minamount_boletofirst;
                }else{
                    $minamount = 0;
                }

                if($clients->key->boletofirst_method == "enable"){
                    if($params['amount'] >= $minamount){
                        // Insert Data Invoice
                        $datainvoice = DataInvoice::create([
                            "client_id" => $params['client_id'],
                            "order_id" => $params['order_id'],
                            "barcode" => $barcode,
                            "status_boletofirst" => "pending",
                            "registered_boletofirst" => 1,
                            // "done_at" => date("Y-m-d",strtotime($date."+ ".$days_safe_boleto." days")),
                            "error_boletofirst" => NULL
                        ]);

                        $data_invoice_id = $datainvoice->id;
                    }else{
                        // Insert Data Invoice
                        $datainvoice = DataInvoice::create([
                            "client_id" => $params['client_id'],
                            "order_id" => $params['order_id'],
                            "barcode" => $barcode,
                            "status_boletofirst" => "pending",
                            "registered_boletofirst" => 0,
                            // "done_at" => date("Y-m-d",strtotime($date."+ ".$days_safe_boleto." days")),
                            "error_boletofirst" => NULL
                        ]);

                        $data_invoice_id = $datainvoice->id;
                    }
                }else{
                    // Insert Data Invoice
                    $datainvoice = DataInvoice::create([
                        "client_id" => $params['client_id'],
                        "order_id" => $params['order_id'],
                        "barcode" => $barcode,
                        "status_boletofirst" => "pending",
                        "registered_boletofirst" => 0,
                        // "done_at" => date("Y-m-d",strtotime($date."+ ".$days_safe_boleto." days")),
                        "error_boletofirst" => NULL
                    ]);

                    $data_invoice_id = $datainvoice->id;
                }
            }

            // Insert Transaction
            $transaction = Transactions::create([
                "solicitation_date" => $date,
                "final_date" => $date,
                "due_date" => $params['data_vencimento']." 00:00:00",
                "code" => $params['pedido'],
                "client_id" => $params['client_id'],
                "order_id" => $params['order_id'],
                "user_id" => $params['user_id'],
                "user_document" => $params['documento_usuario'],
                "user_account_data" => $user_account_data,
                "user_name" => $params['nome_usuario'],
                "id_bank" => $clients->bankInvoice->id,
                "type_transaction" => 'deposit',
                "method_transaction" => 'invoice',
                "amount_solicitation" => $params['amount'],
                "final_amount" => $final_amount,
                // "percent_fee" => $percent_fee,
                // "fixed_fee" => $fixed_fee,
                // "comission" => $comission,
                // "min_fee" => $min_fee,
                "status" => 'pending',
                "data_bank" => $dados,
                "payment_id" => "",
                "code_bank" => "100",
                "link_callback_bank" => $link_callback_bank,
                "url_retorna" => "",
                "data_invoice_id" => $data_invoice_id,
                "quote" => $cotacao_dolar,
                "percent_markup" => $spread_deposit,
                "quote_markup" => $cotacao_dolar_markup,
            ]);

            DB::commit();

            $link_invoice = "https://admin.fastpayments.com.br/get-invoice-FastPayments/".$params['authorization']."/".$params['order_id'];

            if($barcode == ""){
                $json_return = array(
                    "order_id" => $params['order_id'],
                    "solicitation_date" => $date,
                    "due_date" => $params['data_vencimento'],
                    "code_identify" => $params['pedido'],
                    "amount" => $params['amount'],
                    "bank_name" => $bank->name,
                    "holder" => $bank->holder,
                    "agency" => $bank->agency,
                    "type_account" => $bank->type_account,
                    "account" => $bank->account,
                    "status" => "pending",
                    "link_invoice" => $link_invoice
                );

            }else{
                $json_return = array(
                    "order_id" => $params['order_id'],
                    "solicitation_date" => $date,
                    "due_date" => $params['data_vencimento'],
                    "code_identify" => $params['pedido'],
                    "amount" => $params['amount'],
                    "bank_name" => $bank->name,
                    "holder" => $bank->holder,
                    "agency" => $bank->agency,
                    "type_account" => $bank->type_account,
                    "account" => $bank->account,
                    "status" => "pending",
                    "link_invoice" => $link_invoice,
                    "bar_code" => $barcode
                );
            }

            // Success
            return response()->json($json_return,200);

        }catch(exception $e){
            DB::roolback();
        }

    }

    public function create_invoice_bs2($params = array(),$token){

        $client = Clients::where("id","=",$params['client_id'])->first();
        $data_bank = $client->bankInvoice;

        $document_holder = str_replace("/","",str_replace(".","",str_replace("-","",$data_bank->document)));

        $cep = "01419100";
        $estado = "SP";
        $cidade = "São Paulo";
        $bairro = "Cerqueira Cesar";
        $logradouro = "Al Santos";
        $numero = "1767";
        $complemento = "";

        if($document_holder == "38173728000198"){
            $cep = "05311000";
            $estado = "SP";
            $cidade = "São Paulo";
            $bairro = "Vila Leopoldina";
            $logradouro = "AV MOFARREJ";
            $numero = "348";
            $complemento = "CONJ 1308 COND UPPER OFFICE";
        }elseif($document_holder == "33219698000190"){
            $cep = "01419100";
            $estado = "SP";
            $cidade = "São Paulo";
            $bairro = "Cerqueira Cesar";
            $logradouro = "Al Santos";
            $numero = "1767";
            $complemento = "";
        }

        $post = array(
            "seuNumero" => $params['pedido'],
            "cliente" => array(
                "tipo" => "fisica",
                "documento" => $params['cpf'],
                "nome" => $params['nome_usuario'],
                "endereco" => array(
                    "cep" => $params['cep_usuario'],
                    "estado" => $params['uf_usuario'],
                    "cidade" => $params['cidade_usuario'],
                    "bairro" => $params['bairro_usuario'],
                    "logradouro" => $params['endereco_usuario'],
                    "numero" => $params['numero_endereco'],
                    "complemento" => ""
                )
            ),
            "sacadorAvalista" => array(
                "tipo" => "juridica",
                "documento" => $document_holder,
                "nome" => $data_bank->holder,
                "endereco" => array(
                    "cep" => $cep,
                    "estado" => $estado,
                    "cidade" => $cidade,
                    "bairro" => $bairro,
                    "logradouro" => $logradouro,
                    "numero" => $numero,
                    "complemento" => $complemento
                )
            ),
            "vencimento" => $params['data_vencimento_bs2'],
            "valor" => floatval($params['amount']),
            "canal" => "NULL",
            "multa" => array(
                "valor" => floatval(0),
                "data" => "",
                "juros" => floatval(0)
            ),
            "desconto" => array(
                "percentual" => floatval(0),
                "valorFixo" => floatval(0),
                "valorDiario" => floatval(0),
                "limite" => ""
            ),
            "mensagem" => array(
                "linha1" => "Sr. Caixa, não aceitar pagamentos após o vencimento",
                "linha2" => "Sr. Caixa, não aceitar pagamentos em cheque",
                "linha3" => "",
                "linha4" => ""
            )
        );

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://api.bs2.com/pj/forintegration/cobranca/v1/boletos/simplificado",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_SSL_VERIFYHOST => 0,
          CURLOPT_SSL_VERIFYPEER => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => json_encode($post,true),
          CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "Content-Length: ".strlen(json_encode($post)),
            "Authorization: Bearer ".$token,
          ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    // Create Bradesco deposit shop
    public function createShopBradesco($params = array(), $merchantId, $merchantKey){
        $curl = curl_init();

        $amount_clear = str_replace(",","",$params['amount']);
        $amount_clear = str_replace(".","",$amount_clear);

        $paramters = "
        {
        'MerchantOrderId':'".$params['pedido']."',
        'Customer':
        {
            'Identity':'".$params['user_document']."',
            'Name':'".$params['user_name']."',
            'Address':
            {
            'Street':'".$params['user_address']."',
            'Number':'".$params['user_address_number']."',
            'Complement':'".$params['user_address_complement']."',
            'ZipCode':'".$params['user_address_zipcode']."',
            'District':'".$params['user_address_district']."',
            'City':'".$params['user_address_city']."',
            'State':'".$params['user_address_state']."',
            'Country':'BRA'
            }
        },
        'Payment':
        {
            'Type':'EletronicTransfer',
            'Amount':".$amount_clear.",
            'Provider':'Bradesco',
            'ReturnUrl':'".$params['return_url']."'
        }
        }
        ";

        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.cieloecommerce.cielo.com.br/1/sales",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $paramters,
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "MerchantId: ".$merchantId."",
            "MerchantKey: ".$merchantKey."",
            "Postman-Token: 6bac7494-f187-4a72-ad1a-941034fa7d50",
            "cache-control: no-cache"
        ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        $payment_id = "";
        $link_callback_bank = "";

        if($err){
            return $err;
        } else {
            $retornado = json_decode($response,true);

            if(isset($retornado[0]['Code'])){
                // Error
                $json_return = array("message" => $retornado[0]['Message'], "code" => $retornado[0]['Code'], "return" => $retornado, "reason" => "Illegal Conditions");
                return response()->json($json_return,422);
            }

            if(isset($retornado['Payment']['ReturnMessage'])){
                if(strtolower($retornado['Payment']['ReturnMessage']) != "operacao realizada com sucesso"){

                    // Error, UNAUTHORIZED
                    $json_return = array("message" => "UNAUTHORIZED TRANSACTION ON BRADESCO", "reason" => "Illegal Conditions", "return" => $retornado);
                    return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);

                }else{
                    $payment_id = $retornado['Payment']['PaymentId'];
                    $link_callback_bank = $retornado['Payment']['Url'];
                    $dados = "";
                    $return_url = $params['return_url'];
                }
            }else{
                $payment_id = $retornado['Payment']['PaymentId'];
                $link_callback_bank = $retornado['Payment']['Url'];
                $dados = "";
                $return_url = $params['return_url'];
            }

            $date = date("Y-m-d H:i:s");

            // GET ALL CLIENT
            $clients = Clients::where("id","=",$params['client_id'])->first();
            $days_safe_shop = $clients->days_safe_shop;

            // GET BANK DATA
            $bank = $clients->bankBradesco;

            $user_data = array(
                "name" => $params['user_name'],
                "document" => $params['user_document'],
                "bank_name" => $bank->name,
                "holder" => $bank->holder,
                "agency" => $bank->agency,
                "account_number" => $bank->account,
                "operation_bank" => $bank->type_account,
                "user_id" => $params['user_id'],
                "client_id" => $params['client_id'],
                "address" => $this->trata_unicode($params['user_address']),
                "district" => $this->trata_unicode($params['user_address_district']),
                "city" => $this->trata_unicode($params['user_address_city']),
                "uf" => $params['user_address_state'],
                "cep" => $params['user_address_zipcode']
            );

            $user_account_data = base64_encode(json_encode($user_data));

            // Calulo Taxas //
            $cot_ar = $this->get_cotacao_dolar($params['client_id'],"deposit");
            $cotacao_dolar_markup = $cot_ar['markup'];
            $cotacao_dolar = $cot_ar['quote'];
            $spread_deposit = $cot_ar['spread'];

            // Taxas
            $tax = $clients->tax;

            if($clients->currency == "brl"){

                $cotacao_dolar_markup = "1";
                $cotacao_dolar = "1";
                $spread_deposit = "0";

                $final_amount = $params['amount'];
                $percent_fee = ($final_amount * ($tax->shop_percent / 100));
                $fixed_fee = $tax->shop_absolute;
                if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                $comission = ($percent_fee + $fixed_fee);
                if($comission < $tax->min_fee_shop){ $comission = $tax->min_fee_shop; $min_fee = $tax->min_fee_shop; }else{ $min_fee = "NULL"; }

            }elseif($clients->currency == "usd"){

                $final_amount = number_format(($params['amount'] / $cotacao_dolar_markup),6,".","");
                $fixed_fee = number_format(($tax->shop_absolute),6,".","");
                $percent_fee = number_format(($final_amount * ($tax->shop_percent / 100)),6,".","");
                if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                $comission = ($percent_fee + $fixed_fee);
                if($comission < $tax->min_fee_shop){ $comission = $tax->min_fee_shop; $min_fee = $tax->min_fee_shop; }else{ $min_fee = "NULL"; }

            }

            DB::beginTransaction();
            try {

                // Insert Transaction
                $transaction = Transactions::create([
                    "solicitation_date" => $date,
                    "final_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_shop." days")),
                    "code" => $params['pedido'],
                    "client_id" => $params['client_id'],
                    "order_id" => $params['order_id'],
                    "user_id" => $params['user_id'],
                    "user_document" => $params['user_document'],
                    "user_account_data" => $user_account_data,
                    "user_name" => $params['user_name'],
                    "id_bank" => $clients->bankBradesco->id,
                    "type_transaction" => 'deposit',
                    "method_transaction" => 'automatic_checking',
                    "amount_solicitation" => $params['amount'],
                    "final_amount" => $final_amount,
                    // "percent_fee" => $percent_fee,
                    // "fixed_fee" => $fixed_fee,
                    // "comission" => $comission,
                    // "min_fee" => $min_fee,
                    "status" => 'pending',
                    "data_bank" => $dados,
                    "payment_id" => $payment_id,
                    "code_bank" => "237",
                    "link_callback_bank" => $link_callback_bank,
                    "url_retorna" => $return_url,
                    "quote" => $cotacao_dolar,
                    "percent_markup" => $spread_deposit,
                    "quote_markup" => $cotacao_dolar_markup,
                ]);

                DB::commit();

                $json_return = array("order_id" => $params['order_id'],
                "solicitation_date" => $date,
                "due_date" => $this->datetostr(date("Y-m-d",strtotime($date."+ ".$days_safe_shop." days"))),
                "code_identify" => $params['pedido'],
                "amount" => $params['amount'],
                "fees" => $comission,
                "status" => "pending",
                "link_shop" => "https://admin.fastpayments.com.br/get-shop-bradesco/".$params['authorization']."/".$params['order_id']
                );

                // Success
                return response()->json($json_return,200);

            }catch(exception $e){
                DB::roolback();
            }



        }
    }

    // Create Itau deposit shop
    public function createShopItau($params = array(),$codEmp,$chave){
        $cripto = new ItaucriptoController();

        // GET ALL CLIENT
        $clients = Clients::where("id","=",$params['client_id'])->first();
        $days_safe_shop = $clients->days_safe_shop;
        $date = date("Y-m-d H:i:s");

        // GET BANK DATA
        $bank = $clients->bankItau;
        $link_callback_bank = "";

        // Gera dados Itau
        $pedido = $params['pedido'];
        $user_id = $params['user_id'];
        $client_id = $params['client_id'];
        $order_id = $params['order_id'];
        $authorization = $params['authorization'];

        $valor = $params['amount_itau'];
        $observacao = "";
        $nomeSacado = $this->trata_unicode($params['user_name']);
        $codigoInscricao = "01";
        $numeroInscricao = $this->limpaCPF_CNPJ($params['user_document']);
        $enderecoSacado = $this->trata_unicode($params['user_address']);
        $bairroSacado = $this->trata_unicode($params['user_address_district']);
        $cepSacado = str_replace("-","",$params['user_address_zipcode']);
        $cidadeSacado = $this->trata_unicode($params['user_address_city']);
        $estadoSacado = $params['user_address_state'];
        $dataVencimento = str_replace("/","",$this->SomarData(date("d/m/Y"),$days_safe_shop,0,0));
        $urlRetorna = $params['return_url'];

        $obsAd1 = "";
        $obsAd2 = "";
        $obsAd3 = "";

        $dados = $cripto->geraDados($codEmp,$pedido,$valor,$observacao,$chave,$nomeSacado,$codigoInscricao,$numeroInscricao,$enderecoSacado,$bairroSacado,$cepSacado,$cidadeSacado,$estadoSacado,$dataVencimento,$urlRetorna,$obsAd1,$obsAd2,$obsAd3);

        if($dados == "Erro: número do pedido inválido."){
            $array_return = array("message" => "Invalid order number on Itaú", "reason" => "Illegal Conditions");
            return response()->json($array_return,422,["HTTP/1.0" => "Unauthorized"]);
        }elseif($dados == "Erro: tamanho do codigo da empresa diferente de 26 posições."){
            $array_return = array("message" => "Company code size other than 26 positions", "reason" => "Illegal Conditions");
            return response()->json($array_return,422,["HTTP/1.0" => "Unauthorized"]);
        }elseif($dados == "Erro: data de vencimento inválida."){
            $array_return = array("message" => "Invalid due date", "reason" => "Illegal Conditions");
            return response()->json($array_return,422,["HTTP/1.0" => "Unauthorized"]);
        }else{

            $user_data = array(
                "name" => $params['user_name'],
                "document" => $params['user_document'],
                "bank_name" => $bank->name,
                "holder" => $bank->holder,
                "agency" => $bank->agency,
                "account_number" => $bank->account,
                "operation_bank" => $bank->type_account,
                "user_id" => $params['user_id'],
                "client_id" => $params['client_id'],
                "address" => $this->trata_unicode($params['user_address']),
                "district" => $this->trata_unicode($params['user_address_district']),
                "city" => $this->trata_unicode($params['user_address_city']),
                "uf" => $params['user_address_state'],
                "cep" => $params['user_address_zipcode']
            );

            $user_account_data = base64_encode(json_encode($user_data));

            // Calulo Taxas //
            $cot_ar = $this->get_cotacao_dolar($params['client_id'],"deposit");
            $cotacao_dolar_markup = $cot_ar['markup'];
            $cotacao_dolar = $cot_ar['quote'];
            $spread_deposit = $cot_ar['spread'];

            // Taxas
            $tax = $clients->tax;

            if($clients->currency == "brl"){

                $cotacao_dolar_markup = "1";
                $cotacao_dolar = "1";
                $spread_deposit = "0";

                $final_amount = $params['amount'];
                $percent_fee = ($final_amount * ($tax->shop_percent / 100));
                $fixed_fee = $tax->shop_absolute;
                if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                $comission = ($percent_fee + $fixed_fee);
                if($comission < $tax->min_fee_shop){ $comission = $tax->min_fee_shop; $min_fee = $tax->min_fee_shop; }else{ $min_fee = "NULL"; }

            }elseif($clients->currency == "usd"){

                $final_amount = number_format(($params['amount'] / $cotacao_dolar_markup),6,".","");
                $fixed_fee = number_format(($tax->shop_absolute),6,".","");
                $percent_fee = number_format(($final_amount * ($tax->shop_percent / 100)),6,".","");
                if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                $comission = ($percent_fee + $fixed_fee);
                if($comission < $tax->min_fee_shop){ $comission = $tax->min_fee_shop; $min_fee = $tax->min_fee_shop; }else{ $min_fee = "NULL"; }

            }

            DB::beginTransaction();
            try {

                // Insert Transaction
                $transaction = Transactions::create([
                    "solicitation_date" => $date,
                    "final_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_shop." days")),
                    "code" => $params['pedido'],
                    "client_id" => $params['client_id'],
                    "order_id" => $params['order_id'],
                    "user_id" => $params['user_id'],
                    "user_document" => $params['user_document'],
                    "user_account_data" => $user_account_data,
                    "user_name" => $params['user_name'],
                    "id_bank" => $clients->bankItau->id,
                    "type_transaction" => 'deposit',
                    "method_transaction" => 'automatic_checking',
                    "amount_solicitation" => $params['amount'],
                    "final_amount" => $final_amount,
                    // "percent_fee" => $percent_fee,
                    // "fixed_fee" => $fixed_fee,
                    // "comission" => $comission,
                    // "min_fee" => $min_fee,
                    "status" => 'pending',
                    "data_bank" => $dados,
                    "payment_id" => "",
                    "code_bank" => "341",
                    "link_callback_bank" => $link_callback_bank,
                    "url_retorna" => $params['return_url'],
                    "quote" => $cotacao_dolar,
                    "percent_markup" => $spread_deposit,
                    "quote_markup" => $cotacao_dolar_markup,
                ]);

                DB::commit();

                $json_return = array("order_id" => $params['order_id'],
                "solicitation_date" => $date,
                "due_date" => $this->datetostr(date("Y-m-d",strtotime($date."+ ".$days_safe_shop." days"))),
                "code_identify" => $params['pedido'],
                "amount" => $params['amount'],
                "fees" => $comission,
                "status" => "pending",
                "link_shop" => "https://admin.fastpayments.com.br/get-shop-itau/".$params['authorization']."/".$params['order_id']
                );

                // Success
                return response()->json($json_return,200);

            }catch(exception $e){
                DB::roolback();
            }

        }

    }

    // Create BB deposit shop
    public function createShopBB($params = array()){

        $dados = "";
        $payment_id = "";
        $date = date("Y-m-d H:i:s");

        // GET ALL CLIENT
        $clients = Clients::where("id","=",$params['client_id'])->first();
        $days_safe_shop = $clients->days_safe_shop;

        // GET BANK DATA
        $bank = $clients->bankBB;
        $link_callback_bank = "";

        $user_data = array(
            "name" => $params['user_name'],
            "document" => $params['user_document'],
            "bank_name" => $bank->name,
            "holder" => $bank->holder,
            "agency" => $bank->agency,
            "account_number" => $bank->account,
            "operation_bank" => $bank->type_account,
            "user_id" => $params['user_id'],
            "client_id" => $params['client_id'],
            "address" => $this->trata_unicode($params['user_address']),
            "district" => $this->trata_unicode($params['user_address_district']),
            "city" => $this->trata_unicode($params['user_address_city']),
            "uf" => $params['user_address_state'],
            "cep" => $params['user_address_zipcode']
        );

        $user_account_data = base64_encode(json_encode($user_data));

        // Calulo Taxas //
        $cot_ar = $this->get_cotacao_dolar($params['client_id'],"deposit");
        $cotacao_dolar_markup = $cot_ar['markup'];
        $cotacao_dolar = $cot_ar['quote'];
        $spread_deposit = $cot_ar['spread'];

        // Taxas
        $tax = $clients->tax;

        if($clients->currency == "brl"){

            $cotacao_dolar_markup = "1";
            $cotacao_dolar = "1";
            $spread_deposit = "0";

            $final_amount = $params['amount'];
            $percent_fee = ($final_amount * ($tax->shop_percent / 100));
            $fixed_fee = $tax->shop_absolute;
            if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
            $comission = ($percent_fee + $fixed_fee);
            if($comission < $tax->min_fee_shop){ $comission = $tax->min_fee_shop; $min_fee = $tax->min_fee_shop; }else{ $min_fee = "NULL"; }

        }elseif($clients->currency == "usd"){

            $final_amount = number_format(($params['amount'] / $cotacao_dolar_markup),6,".","");
            $fixed_fee = number_format(($tax->shop_absolute),6,".","");
            $percent_fee = number_format(($final_amount * ($tax->shop_percent / 100)),6,".","");
            if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
            $comission = ($percent_fee + $fixed_fee);
            if($comission < $tax->min_fee_shop){ $comission = $tax->min_fee_shop; $min_fee = $tax->min_fee_shop; }else{ $min_fee = "NULL"; }

        }

        DB::beginTransaction();
        try {

            // Insert Transaction
            $transaction = Transactions::create([
                "solicitation_date" => $date,
                "final_date" => $date,
                "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_shop." days")),
                "code" => $params['pedido'],
                "client_id" => $params['client_id'],
                "order_id" => $params['order_id'],
                "user_id" => $params['user_id'],
                "user_document" => $params['user_document'],
                "user_account_data" => $user_account_data,
                "user_name" => $params['user_name'],
                "id_bank" => $clients->bankBB->id,
                "type_transaction" => 'deposit',
                "method_transaction" => 'automatic_checking',
                "amount_solicitation" => $params['amount'],
                "final_amount" => $final_amount,
                // "percent_fee" => $percent_fee,
                // "fixed_fee" => $fixed_fee,
                // "comission" => $comission,
                // "min_fee" => $min_fee,
                "status" => 'pending',
                "data_bank" => $dados,
                "payment_id" => $payment_id,
                "code_bank" => "001",
                "link_callback_bank" => $link_callback_bank,
                "url_retorna" => $params['return_url'],
                "quote" => $cotacao_dolar,
                "percent_markup" => $spread_deposit,
                "quote_markup" => $cotacao_dolar_markup,
            ]);

            DB::commit();

            $json_return = array("order_id" => $params['order_id'],
            "solicitation_date" => $date,
            "due_date" => $this->datetostr(date("Y-m-d",strtotime($date."+ ".$days_safe_shop." days"))),
            "code_identify" => $params['pedido'],
            "amount" => $params['amount'],
            "fees" => $comission,
            "status" => "pending",
            "link_shop" => "https://admin.fastpayments.com.br/get-shop-bb/".$params['authorization']."/".$params['order_id']
            );

            // Success
            return response()->json($json_return,200);

        }catch(exception $e){
            DB::roolback();
        }

    }

    public function register_boleto_bb($params = array()){
        // Cria objeto de BBBoletoWebService para consumo de serviço
        $bb = new BBBoletoWebService('eyJpZCI6IiIsImNvZGlnb1B1YmxpY2Fkb3IiOjAsImNvZGlnb1NvZnR3YXJlIjoxMTA5OCwic2VxdWVuY2lhbEluc3RhbGFjYW8iOjF9', 'eyJpZCI6ImRmNzNkYjEtMWJjNC00ZTRjLTgwNWUtNGE0NTVkYzY0NjRjOTJkYzg2NyIsImNvZGlnb1B1YmxpY2Fkb3IiOjAsImNvZGlnb1NvZnR3YXJlIjoxMTA5OCwic2VxdWVuY2lhbEluc3RhbGFjYW8iOjEsInNlcXVlbmNpYWxDcmVkZW5jaWFsIjoxLCJhbWJpZW50ZSI6InByb2R1Y2FvIiwiaWF0IjoxNTgxOTc0ODE5OTc4fQ');

        // Exemplo de preenchimento e uso abaixo.
        $convenio = $params['number_convenio']; // convenio com 7 posicoes
        $numerodacarteira = '17'; // numero da carteira
        $variacaodacarteira = '19'; // variacao da carteira
        $numerodoboleto = $params['pedido'];
        $datadaemissao = $params['data_emissao']; // Segundo a especificação, deve ser no formato DD.MM.AAAA
        $datadovencimento = $params['data_vencimento']; // Segundo a especificação, deve ser no formato DD.MM.AAAA
        $valor = str_replace(",",".",$params['valor_boleto']);	// No formato inglês (sem separador de milhar)
        $tipodedocumentodocliente = 1; // 1 para CPF e 2 para CNPJ
        $numerodedocumentodocliente = $params['cpf'];	// CPF ou CNPJ, sem pontos ou traços
        $nomedocliente = $params['nome_usuario'];
        $enderecodocliente = $params['endereco_usuario'];
        $bairrodocliente = $params['bairro_usuario'];
        $municipiodocliente = $params['cidade_usuario'];
        $sigladoestadodocliente = $params['uf_usuario'];
        $cepdocliente = $params['cep_usuario']; // Sem pontos ou traços
        $telefonedocliente = '';

        // O diretório de cache pode ser alterado pelo método "trocarCaminhoDaPastaDeCache"
        // $bb->trocarCaminhoDaPastaDeCache('./cache'); // exemplo

        // Parâmetros que serão passados para o Banco do Brasil
        $parametros = array(
            'numeroConvenio' => $convenio,
            'numeroCarteira' => $numerodacarteira,
            'numeroVariacaoCarteira' => $variacaodacarteira,
            'codigoModalidadeTitulo' => 1,
            'dataEmissaoTitulo' => $datadaemissao,
            'dataVencimentoTitulo' => $datadovencimento,
            'valorOriginalTitulo' => $valor,
            'codigoTipoDesconto' => 0,
            'codigoTipoJuroMora' => 0,
            'codigoTipoMulta' => 0,
            'codigoAceiteTitulo' => 'N',
            'codigoTipoTitulo' => 17,
            'textoDescricaoTipoTitulo' => 'Recibo',
            'indicadorPermissaoRecebimentoParcial' => 'N',
            'textoNumeroTituloBeneficiario' => '1',
            'textoNumeroTituloCliente' => '000' . $convenio . sprintf('%010d', $numerodoboleto),
            'textoMensagemBloquetoOcorrencia' => 'Sr.(a) Caixa, não aceitar pagamentos após a data de vencimento | Sr.(a) Caixa, não aceitar pagamentos em cheque',
            'codigoTipoInscricaoPagador' => $tipodedocumentodocliente,
            'numeroInscricaoPagador' => $numerodedocumentodocliente,
            'nomePagador' => $nomedocliente,
            'textoEnderecoPagador' => $enderecodocliente,
            'numeroCepPagador' => $cepdocliente,
            'nomeMunicipioPagador' => $municipiodocliente,
            'nomeBairroPagador' => $bairrodocliente,
            'siglaUfPagador' => $sigladoestadodocliente,
            'textoNumeroTelefonePagador' => $telefonedocliente,
            'codigoChaveUsuario' => 1,
            'codigoTipoCanalSolicitacao' => 5
        );

        // return response()->json($parametros);
        // exit();

        // Passa para o ambiente de testes. Por padrão, o construtor usa o ambiente de produção.
        // Para retornar para o ambiente de produção a qualquer momento, basta chamar o método
        // alterarParaAmbienteDeProducao() (ex.: $bb->alterarParaAmbienteDeProducao();)
        // $bb->alterarParaAmbienteDeTestes();
        $bb->alterarParaAmbienteDeProducao();

        // Exemplo de chamada passando os parâmetros com a token.
        // Retorna um array com a resposta do Banco do Brasil, se ocorreu tudo bem. Caso contrário, retorna "false".
        // A descrição do erro pode ser obtida pelo método "obterErro()".
        $resultado = $bb->registrarBoleto($parametros);

        // As linhas abaixo apenas testam o resultado

        if ($resultado) {
            return $this->createBoletoBB($params,$resultado);
        }else{

            $return = $bb->obterErro();
            $code_return = "0001";

            if($return == "CPF do pagador nao encontrado na base."){
                $code_return = "4028";
            }

            $json_return = array("message" => "UNAUTHORIZED TRANSACTION ON BB", "reason" => "Illegal Conditions", "return" => $return, "code" => $code_return);
            return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
            exit();
        }

    }

    // Register User Client Asaas
    public function registerUserAsaas($user_data = array(),$access_token_asaas){

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://www.asaas.com/api/v3/customers',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($user_data,true),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'access_token: '.$access_token_asaas,
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $get_response = json_decode($response,true);

        if(isset($get_response['id'])){

            DB::beginTransaction();

            try{

                $registerMerchantUser = RegisterUserMerchant::create([
                    "customer_id" => $get_response['id'],
                    "name" => $user_data['name'],
                    "email" => $user_data['email'],
                    "phone" => $user_data['phone'],
                    "mobilePhone" => $user_data['mobilePhone'],
                    "cpfCnpj" => $user_data['cpfCnpj'],
                    "postalCode" => $user_data['postalCode'],
                    "address" => $user_data['address'],
                    "addressNumber" => $user_data['addressNumber'],
                    "complement" => $user_data['complement'],
                    "province" => $user_data['province'],
                    "externalReference" => $user_data['externalReference'],
                    "notificationDisabled" => true,
                    "additionalEmails" => $user_data['additionalEmails'],
                    "municipalInscription" => $user_data['municipalInscription'],
                    "stateInscription" => $user_data['stateInscription'],
                    "observations" => $user_data['observations']
                ]);

                DB::commit();

                return $registerMerchantUser->customer_id;

            }catch(Exception $e){
                DB::rollback();
            }

        }

    }

    public function createTransactionPIXVOLUTI($params = array()){
        $clients = Clients::where("id","=",$params['client_id'])->first();
        $pixVoluti = json_decode($this->registerPIXVoluti($params),true);

        if(isset($pixVoluti['qrCode'])){

            $dados = $pixVoluti['qrCode'];
            $payment_id = $pixVoluti['conciliationId'];
            $date = date("Y-m-d H:i:s");
            $barcode = "";

            $check_count = strlen($dados);

            if($check_count < 10){

                $path_name = "fastlogs-pix-voluti-".date("Y-m-d");

                if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                    mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                }

                $payload = [
                    "date" => $date,
                    "client" => $clients->name,
                    "params" => $params,
                    "return_celcoin" => $pixVoluti
                ];

                $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/pixlog.txt",json_encode($payload));

                $ar = array(
                    "code" => "558",
                    "message" => "Erro on create QrCode PIX ".$params['pedido'],
                    "return" => $pixVoluti
                );

                return response()->json($ar,422);

            }

            // GET DAYS SAFE
            $days_safe_pix = $clients->days_safe_pix;

            // GET BANK DATA
            $bank = $clients->bankPix;
            $link_callback_bank = "";

            $user_data = array(
                "name" => $params['nome_usuario'],
                "document" => $params['documento_usuario'],
                "bank_name" => $bank->name,
                "holder" => $bank->holder,
                "agency" => $bank->agency,
                "account_number" => $bank->account,
                "operation_bank" => $bank->type_account,
                "user_id" => $params['user_id'],
                "client_id" => $params['client_id'],
                "address" => $this->trata_unicode($params['endereco_usuario']),
                "district" => $this->trata_unicode($params['bairro_usuario']),
                "city" => $this->trata_unicode($params['cidade_usuario']),
                "uf" => $params['uf_usuario'],
                "cep" => $params['cep_usuario']
            );

            $user_account_data = base64_encode(json_encode($user_data));

            // Taxas
            $tax = $clients->tax;

            $cotacao_dolar_markup = "1";
            $cotacao_dolar = "1";
            $spread_deposit = "0";

            $final_amount = $params['amount'];
            $percent_fee = ($final_amount * ($tax->pix_percent / 100));
            $fixed_fee = $tax->pix_absolute;
            if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
            $comission = ($percent_fee + $fixed_fee);
            if($comission < $tax->min_fee_pix){ $comission = $tax->min_fee_pix; $min_fee = $tax->min_fee_pix; }else{ $min_fee = "NULL"; }


            $data_invoice_id = NULL;

            DB::beginTransaction();
            try {

                // Insert Transaction
                $transaction = Transactions::create([
                    "solicitation_date" => $date,
                    "final_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                    "code" => $params['pedido'],
                    "client_id" => $params['client_id'],
                    "order_id" => $params['order_id'],
                    "user_id" => $params['user_id'],
                    "user_document" => $params['documento_usuario'],
                    "user_account_data" => $user_account_data,
                    "user_name" => $params['nome_usuario'],
                    "id_bank" => $clients->bankPix->id,
                    "type_transaction" => 'deposit',
                    "method_transaction" => 'pix',
                    "amount_solicitation" => $params['amount'],
                    "final_amount" => $final_amount,
                    "status" => 'pending',
                    "bank_data" => $dados,
                    "payment_id" => $payment_id,
                    "code_bank" => "221",
                    "link_callback_bank" => $link_callback_bank,
                    "url_retorna" => "",
                    "data_invoice_id" => $data_invoice_id,
                    "quote" => $cotacao_dolar,
                    "percent_markup" => $spread_deposit,
                    "quote_markup" => $cotacao_dolar_markup,
                ]);

                DB::commit();

                $link_qr = "https://image-charts.com/chart?chs=350x350&cht=qr&chl=".$dados;

                $json_return = array(
                    "order_id" => $params['order_id'],
                    "solicitation_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                    "code_identify" => $params['pedido'],
                    "amount" => $params['amount'],
                    "status" => "pending",
                    "link_qr" => $link_qr,
                    "content_qr" => $dados
                );

                $path_name = "fastlogs-pix-voluti-success-".date("Y-m-d");

                if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                    mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                }

                $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode(["json_return" => $json_return, "return_voluti" => $pixVoluti]));

                // Success
                return response()->json($json_return,200);

            }catch(exception $e){
                DB::roolback();
            }

        }else{

            return response()->json($pixVoluti);

        }

    }

    public function createTransactionPIXHUBAPI($params = array()){
        $clients = Clients::where("id","=",$params['client_id'])->first();
        $pixVoluti = json_decode($this->registerPIXHUBAPI($params),true);

        if(isset($pixVoluti['qrCode'])){

            $dados = $pixVoluti['qrCode'];
            $payment_id = $pixVoluti['conciliationId'];
            $date = date("Y-m-d H:i:s");
            $barcode = "";

            $check_count = strlen($dados);

            if($check_count < 10){

                $path_name = "fastlogs-pix-hubapi-".date("Y-m-d");

                if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                    mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                }

                $payload = [
                    "date" => $date,
                    "client" => $clients->name,
                    "params" => $params,
                    "return_celcoin" => $pixVoluti
                ];

                $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/pixlog.txt",json_encode($payload));

                $ar = array(
                    "code" => "558",
                    "message" => "Erro on create QrCode PIX ".$params['pedido'],
                    "return" => $pixVoluti
                );

                return response()->json($ar,422);

            }

            // GET DAYS SAFE
            $days_safe_pix = $clients->days_safe_pix;

            // GET BANK DATA
            $bank = $clients->bankPix;
            $link_callback_bank = "";

            $user_data = array(
                "name" => $params['nome_usuario'],
                "document" => $params['documento_usuario'],
                "bank_name" => $bank->name,
                "holder" => $bank->holder,
                "agency" => $bank->agency,
                "account_number" => $bank->account,
                "operation_bank" => $bank->type_account,
                "user_id" => $params['user_id'],
                "client_id" => $params['client_id'],
                "address" => $this->trata_unicode($params['endereco_usuario']),
                "district" => $this->trata_unicode($params['bairro_usuario']),
                "city" => $this->trata_unicode($params['cidade_usuario']),
                "uf" => $params['uf_usuario'],
                "cep" => $params['cep_usuario']
            );

            $user_account_data = base64_encode(json_encode($user_data));

            // Taxas
            $tax = $clients->tax;

            $cotacao_dolar_markup = "1";
            $cotacao_dolar = "1";
            $spread_deposit = "0";

            $final_amount = $params['amount'];
            $percent_fee = ($final_amount * ($tax->pix_percent / 100));
            $fixed_fee = $tax->pix_absolute;
            if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
            $comission = ($percent_fee + $fixed_fee);
            if($comission < $tax->min_fee_pix){ $comission = $tax->min_fee_pix; $min_fee = $tax->min_fee_pix; }else{ $min_fee = "NULL"; }


            $data_invoice_id = NULL;

            DB::beginTransaction();
            try {

                // Insert Transaction
                $transaction = Transactions::create([
                    "solicitation_date" => $date,
                    "final_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                    "code" => $params['pedido'],
                    "client_id" => $params['client_id'],
                    "order_id" => $params['order_id'],
                    "user_id" => $params['user_id'],
                    "user_document" => $params['documento_usuario'],
                    "user_account_data" => $user_account_data,
                    "user_name" => $params['nome_usuario'],
                    "id_bank" => $clients->bankPix->id,
                    "type_transaction" => 'deposit',
                    "method_transaction" => 'pix',
                    "amount_solicitation" => $params['amount'],
                    "final_amount" => $final_amount,
                    "status" => 'pending',
                    "bank_data" => $dados,
                    "payment_id" => $payment_id,
                    "code_bank" => "221",
                    "link_callback_bank" => $link_callback_bank,
                    "url_retorna" => "",
                    "data_invoice_id" => $data_invoice_id,
                    "quote" => $cotacao_dolar,
                    "percent_markup" => $spread_deposit,
                    "quote_markup" => $cotacao_dolar_markup,
                ]);

                DB::commit();

                $link_qr = "https://image-charts.com/chart?chs=350x350&cht=qr&chl=".$dados;

                $json_return = array(
                    "order_id" => $params['order_id'],
                    "solicitation_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                    "code_identify" => $params['pedido'],
                    "amount" => $params['amount'],
                    "status" => "pending",
                    "link_qr" => $link_qr,
                    "content_qr" => $dados
                );

                $path_name = "fastlogs-pix-voluti-success-".date("Y-m-d");

                if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                    mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                }

                $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode(["json_return" => $json_return, "return_voluti" => $pixVoluti]));

                // Success
                return response()->json($json_return,200);

            }catch(exception $e){
                DB::roolback();
            }

        }else{

            return response()->json($pixVoluti);

        }

    }

    public function registerPIXVoluti($params = array()){

        $expiration = date("Y-m-d H:i:s",strtotime("+ ".$params['expiration']." seconds"));

        $data = [
            "pix" => [
                "description" => "Crédito na plataforma",
                "expiresAt" => $expiration,
            ],
            "amount" => intval(str_replace(".","",$params['amount'])),
            "paymentMethod" => "pix",
            "postbackUrl" => "https://volutihook.fastpayments.com.br/api/volutihook"
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://v-api.volutipay.com.br/v1/transactions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Authorization: '.$params['voluti_basic'],
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;

    }

    public function registerPIXHUBAPI($params = array()){

        $authorization = $params['hubauth'];

        $data = [
            "amount" => ($params['amount'] * 100),
            "paymentMethod" => "pix",
            "pix" => array(
                "description" => "Crédito na plataforma",
                "expiresAt" => "",
                "conciliationId" => $params['pedido'],
                "senderDocument" => $params['documento_usuario']
            ),
            "postbackUrl" => "https://hook.fastpayments.com.br/api/hubapihook"
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.production.volutiservices.com/v1/transactions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Authorization: Basic '.$authorization,
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;

    }

    public function createTransactionPIXLUXTAX($params = array()){
        $clients = Clients::where("id","=",$params['client_id'])->first();
        $pixLuxTax = json_decode($this->registerPIXLUXTAX($params),true);

        if(isset($pixLuxTax['qr_code'])){

            $dados = $pixLuxTax['qr_code'];
            $payment_id = $pixLuxTax['trade_no'];
            $date = date("Y-m-d H:i:s");
            $barcode = "";

            $check_count = strlen($dados);

            if($check_count < 10){

                $path_name = "fastlogs-pix-luxtax-".date("Y-m-d");

                if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                    mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                }

                $payload = [
                    "date" => $date,
                    "client" => $clients->name,
                    "params" => $params,
                    "return_luxtax" => $pixLuxTax
                ];

                $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/pixlog.txt",json_encode($payload));

                $ar = array(
                    "code" => "546",
                    "message" => "Erro on create QrCode PIX ".$params['pedido'],
                    "return" => $pixLuxTax
                );

                return response()->json($ar,422);

            }

            // GET DAYS SAFE
            $days_safe_pix = $clients->days_safe_pix;

            // GET BANK DATA
            $bank = $clients->bankPix;
            $link_callback_bank = "";

            $user_data = array(
                "name" => $params['nome_usuario'],
                "document" => $params['documento_usuario'],
                "bank_name" => $bank->name,
                "holder" => $bank->holder,
                "agency" => $bank->agency,
                "account_number" => $bank->account,
                "operation_bank" => $bank->type_account,
                "user_id" => $params['user_id'],
                "client_id" => $params['client_id'],
                "address" => $this->trata_unicode($params['endereco_usuario']),
                "district" => $this->trata_unicode($params['bairro_usuario']),
                "city" => $this->trata_unicode($params['cidade_usuario']),
                "uf" => $params['uf_usuario'],
                "cep" => $params['cep_usuario']
            );

            $user_account_data = base64_encode(json_encode($user_data));

            // Taxas
            $tax = $clients->tax;

            $cotacao_dolar_markup = "1";
            $cotacao_dolar = "1";
            $spread_deposit = "0";

            $final_amount = $params['amount'];
            $percent_fee = ($final_amount * ($tax->pix_percent / 100));
            $fixed_fee = $tax->pix_absolute;
            if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
            $comission = ($percent_fee + $fixed_fee);
            if($comission < $tax->min_fee_pix){ $comission = $tax->min_fee_pix; $min_fee = $tax->min_fee_pix; }else{ $min_fee = "NULL"; }


            $data_invoice_id = NULL;

            DB::beginTransaction();
            try {

                // Insert Transaction
                $transaction = Transactions::create([
                    "solicitation_date" => $date,
                    "final_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                    "code" => $params['pedido'],
                    "client_id" => $params['client_id'],
                    "order_id" => $params['order_id'],
                    "user_id" => $params['user_id'],
                    "user_document" => $params['documento_usuario'],
                    "user_account_data" => $user_account_data,
                    "user_name" => $params['nome_usuario'],
                    "id_bank" => $clients->bankPix->id,
                    "type_transaction" => 'deposit',
                    "method_transaction" => 'pix',
                    "amount_solicitation" => $params['amount'],
                    "final_amount" => $final_amount,
                    "status" => 'pending',
                    "bank_data" => $dados,
                    "payment_id" => $payment_id,
                    "code_bank" => "221",
                    "link_callback_bank" => $link_callback_bank,
                    "url_retorna" => "",
                    "data_invoice_id" => $data_invoice_id,
                    "quote" => $cotacao_dolar,
                    "percent_markup" => $spread_deposit,
                    "quote_markup" => $cotacao_dolar_markup,
                ]);

                DB::commit();

                $link_qr = "https://image-charts.com/chart?chs=350x350&cht=qr&chl=".$dados;

                $json_return = array(
                    "order_id" => $params['order_id'],
                    "solicitation_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                    "code_identify" => $params['pedido'],
                    "amount" => $params['amount'],
                    "status" => "pending",
                    "link_qr" => $link_qr,
                    "content_qr" => $dados
                );

                $path_name = "fastlogs-pix-luxtax-success-".date("Y-m-d");

                if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                    mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                }

                $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode(["json_return" => $json_return, "return_voluti" => $pixLuxTax]));

                // Success
                return response()->json($json_return,200);

            }catch(exception $e){
                DB::roolback();
            }

        }else{

            return response()->json($pixLuxTax);

        }

    }

    public function createTransactionPIXSUITPAY($params = array()){

        $clients = Clients::where("id","=",$params['client_id'])->first();

        $pixSuitPay = json_decode($this->registerPIXSUITPAY($params),true);

        if(isset($pixSuitPay['paymentCode'])){

            $dados = $pixSuitPay['paymentCode'];
            $payment_id = $pixSuitPay['idTransaction'];
            $date = date("Y-m-d H:i:s");
            $barcode = "";

            $check_count = strlen($dados);

            if($check_count < 10){

                $path_name = "nexalogs-pix-suitpay-".date("Y-m-d");

                if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                    mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                }

                $payload = [
                    "date" => $date,
                    "client" => $clients->name,
                    "params" => $params,
                    "return_luxtax" => $pixSuitPay
                ];

                $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/pixlog.txt",json_encode($payload));

                $ar = array(
                    "code" => "546",
                    "message" => "Erro on create QrCode PIX ".$params['pedido'],
                    "return" => $pixSuitPay
                );

                return response()->json($ar,422);

            }

            // GET DAYS SAFE
            $days_safe_pix = $clients->days_safe_pix;

            // GET BANK DATA
            $bank = $clients->bankPix;
            $link_callback_bank = "";

            $user_data = array(
                "name" => $params['nome_usuario'],
                "document" => $params['documento_usuario'],
                "bank_name" => $bank->name,
                "holder" => $bank->holder,
                "agency" => $bank->agency,
                "account_number" => $bank->account,
                "operation_bank" => $bank->type_account,
                "user_id" => $params['user_id'],
                "client_id" => $params['client_id'],
                "address" => $this->trata_unicode($params['endereco_usuario']),
                "district" => $this->trata_unicode($params['bairro_usuario']),
                "city" => $this->trata_unicode($params['cidade_usuario']),
                "uf" => $params['uf_usuario'],
                "cep" => $params['cep_usuario']
            );

            $user_account_data = base64_encode(json_encode($user_data));

            // Taxas
            $tax = $clients->tax;

            $cotacao_dolar_markup = "1";
            $cotacao_dolar = "1";
            $spread_deposit = "0";

            $final_amount = $params['amount'];
            $percent_fee = ($final_amount * ($tax->pix_percent / 100));
            $fixed_fee = $tax->pix_absolute;
            if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
            $comission = ($percent_fee + $fixed_fee);
            if($comission < $tax->min_fee_pix){ $comission = $tax->min_fee_pix; $min_fee = $tax->min_fee_pix; }else{ $min_fee = "NULL"; }


            $data_invoice_id = NULL;

            DB::beginTransaction();
            try {

                // Insert Transaction
                $transaction = Transactions::create([
                    "solicitation_date" => $date,
                    "final_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                    "code" => $params['pedido'],
                    "client_id" => $params['client_id'],
                    "order_id" => $params['order_id'],
                    "user_id" => $params['user_id'],
                    "user_document" => $params['documento_usuario'],
                    "user_account_data" => $user_account_data,
                    "user_name" => $params['nome_usuario'],
                    "id_bank" => $clients->bankPix->id,
                    "type_transaction" => 'deposit',
                    "method_transaction" => 'pix',
                    "amount_solicitation" => $params['amount'],
                    "final_amount" => $final_amount,
                    "status" => 'pending',
                    "bank_data" => $dados,
                    "payment_id" => $payment_id,
                    "code_bank" => $bank->code,
                    "link_callback_bank" => $link_callback_bank,
                    "url_retorna" => "",
                    "data_invoice_id" => $data_invoice_id,
                    "quote" => $cotacao_dolar,
                    "percent_markup" => $spread_deposit,
                    "quote_markup" => $cotacao_dolar_markup,
                ]);

                DB::commit();

                $link_qr = "https://image-charts.com/chart?chs=350x350&cht=qr&chl=".$dados;

                $json_return = array(
                    "order_id" => $params['order_id'],
                    "solicitation_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                    "code_identify" => $params['pedido'],
                    "amount" => $params['amount'],
                    "status" => "pending",
                    "link_qr" => $link_qr,
                    "content_qr" => $dados
                );

                $path_name = "nexalogs-pix-suitpay-success-".date("Y-m-d");

                if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                    mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                }

                $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode(["json_return" => $json_return, "return_suitpay" => $pixSuitPay]));

                // Success
                return response()->json($json_return,200);

            }catch(exception $e){
                DB::roolback();
            }

        }else{

            return response()->json($pixSuitPay);

        }

    }

    // Create deposit PIX Celcoin
    public function createTransactionPIXCELCOIN($params = array()){

        $clients = Clients::where("id","=",$params['client_id'])->first();
        $checkKey = $clients->bankPix->pixkey;

        if(isset($checkKey)){
            $params['pixkey'] = $checkKey;
        }else{
            $params['pixkey'] = "";
        }

        $token_celcoin = $this->getAccessTokenCELCOIN($params);

        if(isset($token_celcoin)){

            if($params['pixkey'] != ""){

                $getLocationId = json_decode($this->getLocationIdDinamyc($token_celcoin),true);

                if(!isset($getLocationId['locationId'])){

                    $ar = array(
                        "message" => "Error on get LocationId",
                        "return" => $getLocationId
                    );

                    return response()->json($ar,422);

                }

                $pixCelcoin = json_decode($this->registerPIXCelcoinDinamic($params,$token_celcoin,$getLocationId['locationId']),true);
            }else{
                $getLocationId = json_decode($this->getLocationId($token_celcoin),true);

                if(!isset($getLocationId['locationId'])){

                    $ar = array(
                        "message" => "Error on get LocationId",
                        "return" => $getLocationId
                    );

                    return response()->json($ar,422);

                }

                $pixCelcoin = json_decode($this->registerPIXCelcoin($params,$token_celcoin,$getLocationId['locationId']),true);
            }

            if(isset($pixCelcoin['status'])){

                if($pixCelcoin['status'] == "ACTIVE"){

                    $dados = $pixCelcoin['location']['emv'];
                    $payment_id = $pixCelcoin['transactionId'];
                    $date = date("Y-m-d H:i:s");
                    $barcode = "";

                    $check_count = strlen($dados);

                    if($check_count < 10){

                        $path_name = "fastlogs-pix-celcoin-".date("Y-m-d");

                        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                        }

                        $payload = [
                            "date" => $date,
                            "client" => $clients->name,
                            "params" => $params,
                            "return_celcoin" => $pixCelcoin
                        ];

                        $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/pixlog.txt",json_encode($payload));

                        $ar = array(
                            "code" => "558",
                            "message" => "Erro on create QrCode PIX ".$params['pedido'],
                            "token" => $token,
                            "return" => $pixCelcoin
                        );

                        return response()->json($ar,422);

                    }

                    // GET DAYS SAFE
                    $days_safe_pix = $clients->days_safe_pix;

                    // GET BANK DATA
                    $bank = $clients->bankPix;
                    $link_callback_bank = "";

                    $user_data = array(
                        "name" => $params['nome_usuario'],
                        "document" => $params['documento_usuario'],
                        "bank_name" => $bank->name,
                        "holder" => $bank->holder,
                        "agency" => $bank->agency,
                        "account_number" => $bank->account,
                        "operation_bank" => $bank->type_account,
                        "user_id" => $params['user_id'],
                        "client_id" => $params['client_id'],
                        "address" => $this->trata_unicode($params['endereco_usuario']),
                        "district" => $this->trata_unicode($params['bairro_usuario']),
                        "city" => $this->trata_unicode($params['cidade_usuario']),
                        "uf" => $params['uf_usuario'],
                        "cep" => $params['cep_usuario']
                    );

                    $user_account_data = base64_encode(json_encode($user_data));

                    // Taxas
                    $tax = $clients->tax;

                    $cotacao_dolar_markup = "1";
                    $cotacao_dolar = "1";
                    $spread_deposit = "0";

                    $final_amount = $params['amount'];
                    $percent_fee = ($final_amount * ($tax->pix_percent / 100));
                    $fixed_fee = $tax->pix_absolute;
                    if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                    $comission = ($percent_fee + $fixed_fee);
                    if($comission < $tax->min_fee_pix){ $comission = $tax->min_fee_pix; $min_fee = $tax->min_fee_pix; }else{ $min_fee = "NULL"; }


                    $data_invoice_id = NULL;

                    DB::beginTransaction();
                    try {

                        // Insert Transaction
                        $transaction = Transactions::create([
                            "solicitation_date" => $date,
                            "final_date" => $date,
                            "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                            "code" => $params['pedido'],
                            "client_id" => $params['client_id'],
                            "order_id" => $params['order_id'],
                            "user_id" => $params['user_id'],
                            "user_document" => $params['documento_usuario'],
                            "user_account_data" => $user_account_data,
                            "user_name" => $params['nome_usuario'],
                            "id_bank" => $clients->bankPix->id,
                            "type_transaction" => 'deposit',
                            "method_transaction" => 'pix',
                            "amount_solicitation" => $params['amount'],
                            "final_amount" => $final_amount,
                            // "percent_fee" => $percent_fee,
                            // "fixed_fee" => $fixed_fee,
                            // "comission" => $comission,
                            // "min_fee" => $min_fee,
                            "provider_reference" => $params['provider_reference'],
                            "status" => 'pending',
                            "bank_data" => $dados,
                            "payment_id" => $payment_id,
                            "code_bank" => "221",
                            "link_callback_bank" => $link_callback_bank,
                            "url_retorna" => "",
                            "data_invoice_id" => $data_invoice_id,
                            "quote" => $cotacao_dolar,
                            "percent_markup" => $spread_deposit,
                            "quote_markup" => $cotacao_dolar_markup,
                        ]);

                        DB::commit();

                        $link_qr = "https://image-charts.com/chart?chs=350x350&cht=qr&chl=".$dados;

                        if(in_array($clients->id,[11,27,28])){
                            $json_return = array(
                                "fast_id" => $transaction->id,
                                "order_id" => $params['order_id'],
                                "solicitation_date" => $date,
                                "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                                "code_identify" => $params['pedido'],
                                "provider_reference" => $transaction->id,
                                "amount" => $params['amount'],
                                "status" => "pending",
                                "link_qr" => $link_qr,
                                "content_qr" => $dados
                            );
                        }else{
                            $json_return = array(
                                "order_id" => $params['order_id'],
                                "solicitation_date" => $date,
                                "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                                "code_identify" => $params['pedido'],
                                "amount" => $params['amount'],
                                "status" => "pending",
                                "link_qr" => $link_qr,
                                "content_qr" => $dados
                            );
                        }

                        $check_count = strlen($dados);

                        if($check_count < 10){

                            $path_name = "fastlogs-pix-".date("Y-m-d");

                            if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                                mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                            }

                            $payload = [
                                "date" => $date,
                                "client" => $clients->name,
                                "params" => $params,
                                "return_celcoin" => $pixCelcoin
                            ];

                            $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/pixlog.txt",json_encode($payload));

                            $ar = array(
                                "code" => "558",
                                "message" => "Erro on create QrCode PIX ".$params['pedido'],
                                "token" => $token,
                                "return" => $pixCelcoin
                            );

                            return response()->json($ar,422);

                        }

                        $path_name = "fastlogs-pix-success-".date("Y-m-d");

                        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                        }

                        $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode(["json_return" => $json_return, "return_celcoin" => $pixCelcoin]));

                        // Success
                        return response()->json($json_return,200);

                    }catch(exception $e){
                        DB::roolback();
                    }

                }

            }else{

                $ar = array(
                    "message" => "Error on create bank transaction",
                    "request" => $params,
                    "return" => $pixCelcoin
                );

                return response()->json($ar,422);

            }


        }else{

            $path_name = "fastlogs-pix-".date("Y-m-d");

            if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
            }

            $payload = [
                "date" => date("Y-m-d H:i:s"),
                "client" => $clients->name,
                "params" => $params,
                "return_openpix" => $register
            ];

            $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/pixlog.txt",json_encode($payload));

            $ar = array(
                "code" => "558",
                "message" => "Erro on create QrCode PIX ".$params['pedido'],
                "token" => $token,
                "return" => $register
            );

            return json_encode($ar);
        }

    }

    public function getLocationId($access_token_celcoin){

        $data = [
            "clientRequestId" => "9b26edb7cf254db09f5449c94bf13abba",
            "type" => "COBV",
            "merchant" => [
                "postalCode" => env('COMPANY_POSTALCODE'),
                "city" => env('COMPANY_CITY'),
                "merchantCategoryCode" => "0000",
                "name" => env('COMPANY_SOCIAL_NAME')
            ]
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apicorp.celcoin.com.br/pix/v1/location',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'accept: application/json',
            'Content-Type: application/json-patch+json',
            'Authorization: Bearer '.$access_token_celcoin
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function getLocationIdDinamyc($access_token_celcoin){

        $data = [
            "clientRequestId" => "9b26edb7cf254db09f5449c94bf13abba",
            "type" => "COB",
            "merchant" => [
                "postalCode" => env('COMPANY_POSTALCODE'),
                "city" => env('COMPANY_CITY'),
                "merchantCategoryCode" => "0000",
                "name" => env('COMPANY_SOCIAL_NAME')
            ]
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apicorp.celcoin.com.br/pix/v1/location',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'accept: application/json',
            'Content-Type: application/json-patch+json',
            'Authorization: Bearer '.$access_token_celcoin
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    // Register deposit PIX Celcoin
    public function registerPIXCelcoin($params = array(),$access_token_celcoin,$locationId){

        $data = [
            "clientRequestId" => $params['pedido'],
            "expirationAfterPayment" => 10,
            "duedate" => date("Y-m-d 00:00:00",strtotime("+1 day")),
            "debtor" => [
                "name" => $params['nome_usuario'],
                "cpf" => $params['cpf'],
                "city" => $params['cidade_usuario'],
                "publicArea" => $params['endereco_usuario'],
                "state" => $params['uf_usuario'],
                "postalCode" => $params['cep_usuario'],
            ],
            "receiver" => [
                "name" => env('COMPANY_SOCIAL_NAME'),
                "cnpj" => env('COMPANY_DOCUMENT'),
                "postalCode" => env('COMPANY_POSTALCODE'),
                "city" => env('COMPANY_CITY'),
                "publicArea" => env('COMPANY_ADDRESS'),
                "state" => env('COMPANY_UF'),
                "fantasyName" => env('COMPANY_FANTASY_NAME')
            ],
            "locationId" => $locationId,
            "amount" => $params['amount'],
            "amountDicount" => [
                "discoun'tDateFixed" => [
                    array(
                        "date" => date("Y-m-d"),
                        "amountPerc" => "0.00"
                    )
                ],
                "hasDicount" => false,
                "moda'lity" => "FIXED_VALUE_UNTIL_THE_DATES_INFORMED"
            ],
            "amountAbatement" => [
                "hasAbatement" => false,
                "amountPerc" => "0.00",
                "modality" => "FIXED_VALUE"
            ],
            "amountFine" => [
                "hasFine" => false,
                "amountPerc" => "0.00",
                "modality" => "FIXED_VALUE"
            ],
            "amountInterest" => [
                "hasInterest" => false,
                "amountPerc" => "0.00",
                "modality" => "VALUE_CALENDAR_DAYS"
            ],
            "additionalInformation" => [
                array(
                "value" => "Crédito em FastPayments",
                "key" => "Transação PIX"
                )
            ],
            "payerQuestion" => "Crédito em FastPayments",
            "key" => env('COMPANY_PIX_KEY_CELCOIN')
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apicorp.celcoin.com.br/pix/v1/collection/duedate',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data,true),
        CURLOPT_HTTPHEADER => array(
            'accept: application/json',
            'Content-Type: application/json-patch+json',
            'Authorization: Bearer '.$access_token_celcoin
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    // Register deposit PIX Celcoin Dinamic
    public function registerPIXCelcoinDinamic($params = array(),$access_token_celcoin,$locationId){

        $data = [
            "clientRequestId" => $params['pedido'],
            "payerQuestion" => "Crédito em FastPayments",
            "key" => $params['pixkey'],
            "locationId" => $locationId,
            "debtor" => array(
                "name" => $params['nome_usuario'],
                "cpf" => $params['cpf'],
            ),
            "amount" => array(
                "original" => $params['amount'],
                "changeType" => 0
            ),
            "calendar" => array(
                "expiration" => floatval($params['expiration'])
            ),
            "additionalInformation" => [
                array(
                    "value" => "FastPayments",
                    "key" => "Crédito em"
                )
            ]
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apicorp.celcoin.com.br/pix/v1/collection/immediate',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data,true),
        CURLOPT_HTTPHEADER => array(
            'accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer '.$access_token_celcoin
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    // Get access_token PIX Celcoin
    public function getAccessTokenCELCOIN($params = array()){

        $tokenDB = $params['access_token_celcoin'];
        // $client = Clients::where("id",$params['client_id'])->first();
        $bank = Banks::where("id","6")->first();

        if($tokenDB != ""){

            $checkToken = $this->checkTokenCELCOIN($tokenDB);

            if($checkToken === true){
                return $tokenDB;
            }else{

                $curl = curl_init();

                curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://apicorp.celcoin.com.br/v5/token',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array('client_id' => $params['client_id_celcoin'],'grant_type' => 'client_credentials','client_secret' => $params['client_secret_celcoin']),
                ));

                $response = curl_exec($curl);

                $path_name = "get-token-celcoin-".date("Y-m-d");

                if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                    mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                }

                $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",$response);

                curl_close($curl);

                if (curl_errno($curl)) {
                    print_r(curl_error($curl));

                    $path_name = "get-token-celcoin-".date("Y-m-d");

                    if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                        mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                    }

                    $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode(["error" => print_r(curl_error($curl))]));

                    curl_close($curl);
                    exit();
                }

                $getReturn = json_decode($response,true);

                DB::beginTransaction();
                try{
                    $bank->update([
                        "access_token_celcoin" => $getReturn['access_token']
                    ]);

                    DB::commit();

                    return $getReturn['access_token'];
                }catch(exception $e){
                    DB::rollBack();
                }

            }

        }else{

            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://apicorp.celcoin.com.br/v5/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array('client_id' => $params['client_id_celcoin'],'grant_type' => 'client_credentials','client_secret' => $params['client_secret_celcoin']),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            $getReturn = json_decode($response,true);

            DB::beginTransaction();
            try{

                $bank->update([
                    "access_token_celcoin" => $getReturn['access_token']
                ]);

                DB::commit();

                return $getReturn['access_token'];
            }catch(exception $e){
                DB::rollBack();
            }
        }

    }

    public function getTokenCELCOIN($params = array()){

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apicorp.celcoin.com.br/v5/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array('client_id' => $params['client_id_celcoin'],'grant_type' => 'client_credentials','client_secret' => $params['client_secret_celcoin']),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function getTokenCELCOINSandbox($params = array()){

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://sandbox.openfinance.celcoin.dev/v5/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array('client_id' => "c9265c8019.fast.celcoinapi.v5",'grant_type' => 'client_credentials','client_secret' => "72ce90fd0f1c4582bf9e338db4718646af4a0ac92ba5426d93d2da619001855a"),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function checkTokenCELCOIN($tokenDB){

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apicorp.celcoin.com.br/v5/merchant/balance',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
        'Authorization: Bearer '.$tokenDB
        ),
        ));

        $response = curl_exec($curl);
        $http_status  = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if($http_status != "200"){
            return false;
        }else{
            return true;
        }

    }

    // Create deposit Credit Card Asaas
    public function createTransactionCCAsaas($params = array()){

        $access_token_asaas = $params['access_token_asaas'];

        // GET CLIENT
        $clients = Clients::where("id","=",$params['client_id'])->first();

        $register = json_decode($this->registerTransactionCCAsaas($params,$access_token_asaas),true);

        if(isset($register['id'])){

            $dados = $register['invoiceNumber'];
            $payment_id = $register['id'];
            $date = date("Y-m-d H:i:s");
            $barcode = "";

            // GET DAYS SAFE
            $days_safe_cc = $clients->days_safe_cc;

            // GET BANK DATA
            $bank = $clients->bankCC;
            $link_callback_bank = "";

            $user_data = array(
                "name" => $params['nome_usuario'],
                "document" => $params['documento_usuario'],
                "email" => $params['email_usuario'],
                "telefone" => $params['telefone_usuario'],
                "bank_name" => $bank->name,
                "holder" => $bank->holder,
                "agency" => $bank->agency,
                "account_number" => $bank->account,
                "operation_bank" => $bank->type_account,
                "user_id" => $params['user_id'],
                "client_id" => $params['client_id'],
                "address" => $this->trata_unicode($params['endereco_usuario']),
                "district" => $this->trata_unicode($params['bairro_usuario']),
                "city" => $this->trata_unicode($params['cidade_usuario']),
                "uf" => $params['uf_usuario'],
                "cep" => $params['cep_usuario']
            );

            $user_account_data = base64_encode(json_encode($user_data));

            // Taxas
            $tax = $clients->tax;

            $cotacao_dolar_markup = "1";
            $cotacao_dolar = "1";
            $spread_deposit = "0";

            $final_amount = $params['amount'];
            $percent_fee = ($final_amount * ($tax->cc_percent / 100));
            $fixed_fee = $tax->cc_absolute;
            if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
            $comission = ($percent_fee + $fixed_fee);
            if($comission < $tax->min_fee_cc){ $comission = $tax->min_fee_cc; $min_fee = $tax->min_fee_cc; }else{ $min_fee = "NULL"; }

            $data_invoice_id = NULL;

            if($register['status'] == "CONFIRMED"){
                $status = "confirmed";
            }else{
                $status = "pending";
            }

            DB::beginTransaction();
            try {

                // Insert Transaction
                $transaction = Transactions::create([
                    "solicitation_date" => $date,
                    "final_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_cc." days")),
                    "code" => $params['pedido'],
                    "client_id" => $params['client_id'],
                    "order_id" => $params['order_id'],
                    "user_id" => $params['user_id'],
                    "user_document" => $params['documento_usuario'],
                    "user_account_data" => $user_account_data,
                    "id_bank" => $clients->bankCC->id,
                    "type_transaction" => 'deposit',
                    "method_transaction" => 'creditcard',
                    "amount_solicitation" => $params['amount'],
                    "final_amount" => $final_amount,
                    "status" => $status,
                    "data_bank" => $dados,
                    "payment_id" => $payment_id,
                    "code_bank" => "461",
                    "link_callback_bank" => $link_callback_bank,
                    "url_retorna" => "",
                    "data_invoice_id" => $data_invoice_id,
                    "quote" => $cotacao_dolar,
                    "percent_markup" => $spread_deposit,
                    "quote_markup" => $cotacao_dolar_markup,
                ]);

                DB::commit();

                $json_return = array(
                    "order_id" => $params['order_id'],
                    "solicitation_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_cc." days")),
                    "code_identify" => $params['pedido'],
                    "amount" => $params['amount'],
                    "status" => $status
                );

                if($register['status'] == "CONFIRMED"){

                    \App\Jobs\ApproveCCAsaas::dispatch($transaction->id)->delay(now());

                }

                // Success
                return response()->json($json_return,200);

            }catch(exception $e){
                DB::roolback();
            }

        }else{

            $ar = array(
                "message" => "internal error ".$params['pedido'],
                "access_token_asaas" => $access_token_asaas,
                "return" => $register
            );

            return response()->json($ar,422);
        }

    }

    public function registerTransactionCCAsaas($params = array(),$access_token_asaas){

        $expiry_month = substr($params['card_expired'],"0","2");
        $expiry_year = substr($params['card_expired'],"3","4");

        $data = [
            "customer" => $params['customer_id'],
            "billingType" => "CREDIT_CARD",
            "dueDate" => $params['data_vencimento'],
            "value" => floatval($params['amount']),
            "description" => $params['pedido'],
            "externalReference" => $params['pedido'],
            "creditCard" => array(
                "holderName" => $params['nome_usuario'],
                "number" => $params['credit_card_number'],
                "expiryMonth" => $expiry_month,
                "expiryYear" => $expiry_year,
                "ccv" => $params['card_cvv']
            ),
            "creditCardHolderInfo" => array(
                "name" => $params['nome_usuario'],
                "email" => $params['email_usuario'],
                "cpfCnpj" => $params['cpf'],
                "postalCode" => $params['cep_usuario'],
                "addressNumber" => $params['numero_endereco'],
                "addressComplement" => "",
                "phone" => "",
                "mobilePhone" => $params['telefone_usuario'],
            ),
            // "creditCardToken" =>
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://www.asaas.com/api/v3/payments',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data,true),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'access_token: '.$access_token_asaas,
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    // Create deposit PIX Asaas
    public function createTransactionPIXAsaas($params = array()){

        $access_token_asaas = $params['access_token_asaas'];

        // GET CLIENT
        $clients = Clients::where("id","=",$params['client_id'])->first();

        $register = json_decode($this->registerTransactionPIXAsaas($params,$access_token_asaas),true);

        if(isset($register['id'])){

            $get_payload = json_decode($this->getPayloadPixAsaas($register['id'],$access_token_asaas),true);

            $dados = $get_payload['payload'];
            $base64_image = $get_payload['encodedImage'];
            $payment_id = $register['id'];
            $date = date("Y-m-d H:i:s");
            $barcode = "";

            // GET DAYS SAFE
            $days_safe_pix = $clients->days_safe_pix;

            // GET BANK DATA
            $bank = $clients->bankPix;
            $link_callback_bank = "";

            $user_data = array(
                "name" => $params['nome_usuario'],
                "document" => $params['documento_usuario'],
                "bank_name" => $bank->name,
                "holder" => $bank->holder,
                "agency" => $bank->agency,
                "account_number" => $bank->account,
                "operation_bank" => $bank->type_account,
                "user_id" => $params['user_id'],
                "client_id" => $params['client_id'],
                "address" => $this->trata_unicode($params['endereco_usuario']),
                "district" => $this->trata_unicode($params['bairro_usuario']),
                "city" => $this->trata_unicode($params['cidade_usuario']),
                "uf" => $params['uf_usuario'],
                "cep" => $params['cep_usuario']
            );

            $user_account_data = base64_encode(json_encode($user_data));


            // Taxas
            $tax = $clients->tax;

            $cotacao_dolar_markup = "1";
            $cotacao_dolar = "1";
            $spread_deposit = "0";

            $final_amount = $params['amount'];
            $percent_fee = ($final_amount * ($tax->pix_percent / 100));
            $fixed_fee = $tax->pix_absolute;
            if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
            $comission = ($percent_fee + $fixed_fee);
            if($comission < $tax->min_fee_pix){ $comission = $tax->min_fee_pix; $min_fee = $tax->min_fee_pix; }else{ $min_fee = "NULL"; }

            $data_invoice_id = NULL;

            DB::beginTransaction();
            try {

                // Insert Transaction
                $transaction = Transactions::create([
                    "solicitation_date" => $date,
                    "final_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                    "code" => $params['pedido'],
                    "client_id" => $params['client_id'],
                    "order_id" => $params['order_id'],
                    "user_id" => $params['user_id'],
                    "user_document" => $params['documento_usuario'],
                    "user_account_data" => $user_account_data,
                    "user_name" => $params['nome_usuario'],
                    "id_bank" => $clients->bankPix->id,
                    "type_transaction" => 'deposit',
                    "method_transaction" => 'pix',
                    "amount_solicitation" => $params['amount'],
                    "final_amount" => $final_amount,
                    "status" => 'pending',
                    "data_bank" => $dados,
                    "payment_id" => $payment_id,
                    "code_bank" => "461",
                    "link_callback_bank" => $link_callback_bank,
                    "url_retorna" => "",
                    "data_invoice_id" => $data_invoice_id,
                    "quote" => $cotacao_dolar,
                    "percent_markup" => $spread_deposit,
                    "quote_markup" => $cotacao_dolar_markup,
                ]);

                DB::commit();

                $link_qr = "https://image-charts.com/chart?chs=350x350&cht=qr&chl=".$dados;

                $json_return = array(
                    "order_id" => $params['order_id'],
                    "solicitation_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                    "code_identify" => $params['pedido'],
                    "amount" => $params['amount'],
                    "status" => "pending",
                    "link_qr" => $link_qr,
                    "content_qr" => $dados
                );

                // Success
                return response()->json($json_return,200);

            }catch(exception $e){
                DB::roolback();
            }

        }else{

            $ar = array(
                "message" => "internal error ".$params['pedido'],
                "access_token_asaas" => $access_token_asaas,
                "return" => $register
            );

            return json_encode($ar);
        }

    }

    public function registerTransactionPIXAsaas($params = array(),$access_token_asaas){

        $data = [
            "customer" => $params['customer_id'],
            "billingType" => "PIX",
            "dueDate" => $params['data_vencimento'],
            "value" => floatval($params['amount']),
            "description" => $params['pedido'],
            "externalReference" => $params['pedido'],
            "discount" => array(
                "value" => 0,
                "dueDateLimitDays" => 0
            ),
            "fine" => array(
                "value" => 0
            ),
            "interest" => array(
                "value" => 0
            ),
            "postalService" => false
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://www.asaas.com/api/v3/payments',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data,true),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'access_token: '.$access_token_asaas,
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function getStatusPIXAsaas($access_token_asaas,$payment_id){

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://www.asaas.com/api/v3/payments/'.$payment_id,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'access_token: '.$access_token_asaas
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function getPayloadPixAsaas($id,$access_token_asaas){

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://www.asaas.com/api/v3/payments/'.$id.'/pixQrCode',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'access_token: '.$access_token_asaas,
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    //Create deposit INVOICE PagHiper
    public function createTransactionINVOICEPagHiper($params = array()){

        $paghiper_api = $params['paghiper_api'];

        // GET CLIENT
        $clients = Clients::where("id","=",$params['client_id'])->first();

        $register = json_decode($this->registerTransactionINVOICEPagHiper($params,$paghiper_api),true);

    }

    // Create deposit PIX PagHiper
    public function createTransactionPIXPagHiper($params = array()){

        $paghiper_api = $params['paghiper_api'];

        // GET CLIENT
        $clients = Clients::where("id","=",$params['client_id'])->first();

        $register = json_decode($this->registerTransactionPIXPagHiper($params,$paghiper_api),true);

        return $register;

        if(isset($register['pix_create_request']['transaction_id'])){

            $dados = $register['pix_create_request']['pix_code']['emv'];
            $payment_id = $register['pix_create_request']['transaction_id'];
            $date = date("Y-m-d H:i:s");
            $barcode = "";

            // GET DAYS SAFE
            $days_safe_pix = $clients->days_safe_pix;

            // GET BANK DATA
            $bank = $clients->bankPix;
            $link_callback_bank = "";

            $user_data = array(
                "name" => $params['nome_usuario'],
                "document" => $params['documento_usuario'],
                "bank_name" => $bank->name,
                "holder" => $bank->holder,
                "agency" => $bank->agency,
                "account_number" => $bank->account,
                "operation_bank" => $bank->type_account,
                "user_id" => $params['user_id'],
                "client_id" => $params['client_id'],
                "address" => $this->trata_unicode($params['endereco_usuario']),
                "district" => $this->trata_unicode($params['bairro_usuario']),
                "city" => $this->trata_unicode($params['cidade_usuario']),
                "uf" => $params['uf_usuario'],
                "cep" => $params['cep_usuario']
            );

            $user_account_data = base64_encode(json_encode($user_data));


            // Taxas
            $tax = $clients->tax;

            $cotacao_dolar_markup = "1";
            $cotacao_dolar = "1";
            $spread_deposit = "0";

            $final_amount = $params['amount'];
            $percent_fee = ($final_amount * ($tax->pix_percent / 100));
            $fixed_fee = $tax->pix_absolute;
            if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
            $comission = ($percent_fee + $fixed_fee);
            if($comission < $tax->min_fee_pix){ $comission = $tax->min_fee_pix; $min_fee = $tax->min_fee_pix; }else{ $min_fee = "NULL"; }

            $data_invoice_id = NULL;

            DB::beginTransaction();
            try {

                // Insert Transaction
                $transaction = Transactions::create([
                    "solicitation_date" => $date,
                    "final_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                    "code" => $params['pedido'],
                    "client_id" => $params['client_id'],
                    "order_id" => $params['order_id'],
                    "user_id" => $params['user_id'],
                    "user_document" => $params['documento_usuario'],
                    "user_account_data" => $user_account_data,
                    "user_name" => $params['nome_usuario'],
                    "id_bank" => $clients->bankPix->id,
                    "type_transaction" => 'deposit',
                    "method_transaction" => 'pix',
                    "amount_solicitation" => $params['amount'],
                    "final_amount" => $final_amount,
                    "status" => 'pending',
                    "data_bank" => $dados,
                    "payment_id" => "",
                    "code_bank" => "855",
                    "link_callback_bank" => $link_callback_bank,
                    "url_retorna" => "",
                    "data_invoice_id" => $data_invoice_id,
                    "quote" => $cotacao_dolar,
                    "percent_markup" => $spread_deposit,
                    "quote_markup" => $cotacao_dolar_markup,
                ]);

                DB::commit();

                $link_qr = "https://image-charts.com/chart?chs=350x350&cht=qr&chl=".$dados;

                $json_return = array(
                    "order_id" => $params['order_id'],
                    "solicitation_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                    "code_identify" => $params['pedido'],
                    "amount" => $params['amount'],
                    "status" => "pending",
                    "link_qr" => $link_qr,
                    "content_qr" => $dados
                );

                // Success
                return response()->json($json_return,200);

            }catch(exception $e){
                DB::roolback();
            }

        }else{

            $ar = array(
                "message" => "internal error ".$params['pedido'],
                "paghiper_api" => $paghiper_api,
                "return" => $register
            );

            return json_encode($ar);
        }


    }

    public function registerTransactionINVOICEPagHiper($params = array(),$paghiper_api){

        $data = array(
        'apiKey' => $paghiper_api,
        'order_id' => $params['pedido'], // código interno do lojista para identificar a transacao.
        'payer_email' => 'received@fastpayments.com.br',
        'payer_name' => $params['nome_usuario'], // nome completo ou razao social
        'payer_cpf_cnpj' => $params['documento_usuario'], // cpf ou cnpj
        'payer_phone' => '', // fixou ou móvel
        'notification_url' => 'https://webhook.site/8585f53e-f752-46f0-a3b2-2a636f950f8d',
        'discount_cents' => '0', // em centavos
        'shipping_price_cents' => str_replace(".","",$params['amount']), // em centavos
        'shipping_methods' => 'PAC',
        'number_ntfiscal' => '',
        'fixed_description' => true,
        'days_due_date' => '5', // dias para vencimento do Pix
        'items' => array(
                array (
                    'description' => 'Crédito em FastPayments '.$params['pedido'],
                    'quantity' => '1',
                    'item_id' => '1',
                    'price_cents' => str_replace(".","",$params['amount']) // em centavos
                ),
            ),
        );

        return json_encode($data);

        $data_post = json_encode( $data );

        $url = "https://pix.paghiper.com/invoice/create/";
        $mediaType = "application/json"; // formato da requisição
        $charSet = "UTF-8";
        $headers = array();
        $headers[] = "Accept: ".$mediaType;
        $headers[] = "Accept-Charset: ".$charSet;
        $headers[] = "Accept-Encoding: ".$mediaType;
        $headers[] = "Content-Type: ".$mediaType.";charset=".$charSet;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_post);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);
        $json = json_decode($result, true);

        // captura o http code
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return $result;

    }

    public function registerTransactionPIXPagHiper($params = array(),$paghiper_api){

        $data = array(
        'apiKey' => $paghiper_api,
        'order_id' => $params['pedido'], // código interno do lojista para identificar a transacao.
        'payer_email' => 'received@fastpayments.com.br',
        'payer_name' => $params['nome_usuario'], // nome completo ou razao social
        'payer_cpf_cnpj' => $params['documento_usuario'], // cpf ou cnpj
        'payer_phone' => '', // fixou ou móvel
        'notification_url' => 'https://webhook.site/8585f53e-f752-46f0-a3b2-2a636f950f8d',
        'discount_cents' => '0', // em centavos
        'shipping_price_cents' => str_replace(".","",$params['amount']), // em centavos
        'shipping_methods' => 'PAC',
        'number_ntfiscal' => '',
        'fixed_description' => true,
        'days_due_date' => '5', // dias para vencimento do Pix
        'items' => array(
                array (
                    'description' => 'Crédito em FastPayments '.$params['pedido'],
                    'quantity' => '1',
                    'item_id' => '1',
                    'price_cents' => str_replace(".","",$params['amount']) // em centavos
                ),
            ),
        );

        return json_encode($data);

        $data_post = json_encode( $data );

        $url = "https://pix.paghiper.com/invoice/create/";
        $mediaType = "application/json"; // formato da requisição
        $charSet = "UTF-8";
        $headers = array();
        $headers[] = "Accept: ".$mediaType;
        $headers[] = "Accept-Charset: ".$charSet;
        $headers[] = "Accept-Encoding: ".$mediaType;
        $headers[] = "Content-Type: ".$mediaType.";charset=".$charSet;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_post);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);
        $json = json_decode($result, true);

        // captura o http code
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return $result;

    }

    // Create deposit PIX
    public function createTransactionPIX($params = array()){

        $tk = json_decode($this->getTokenBS2PIX($params['client_id_bs2'],$params['client_secret_bs2'],"prod"),true);

        if(isset($tk['access_token'])){

            $token_bs2 = $tk['access_token'];

            // GET CLIENT
            $clients = Clients::where("id","=",$params['client_id'])->first();

            $register = json_decode($this->registerTransactionPIX($params,$token_bs2,$clients->method_pix),true);

            if(isset($register['qrCode'])){

                $dados = $register['qrCode'];
                $date = date("Y-m-d H:i:s");
                $barcode = "";

                // GET DAYS SAFE
                $days_safe_pix = $clients->days_safe_pix;

                // GET BANK DATA
                $bank = $clients->bankPix;
                $link_callback_bank = "";

                $user_data = array(
                    "name" => $params['nome_usuario'],
                    "document" => $params['documento_usuario'],
                    "bank_name" => $bank->name,
                    "holder" => $bank->holder,
                    "agency" => $bank->agency,
                    "account_number" => $bank->account,
                    "operation_bank" => $bank->type_account,
                    "user_id" => $params['user_id'],
                    "client_id" => $params['client_id'],
                    "address" => $this->trata_unicode($params['endereco_usuario']),
                    "district" => $this->trata_unicode($params['bairro_usuario']),
                    "city" => $this->trata_unicode($params['cidade_usuario']),
                    "uf" => $params['uf_usuario'],
                    "cep" => $params['cep_usuario']
                );

                $user_account_data = base64_encode(json_encode($user_data));

                // Calulo Taxas //
                $cot_ar = $this->get_cotacao_dolar($params['client_id'],"deposit");
                $cotacao_dolar_markup = $cot_ar['markup'];
                $cotacao_dolar = $cot_ar['quote'];
                $spread_deposit = $cot_ar['spread'];

                // Taxas
                $tax = $clients->tax;

                if($clients->currency == "brl"){

                    $cotacao_dolar_markup = "1";
                    $cotacao_dolar = "1";
                    $spread_deposit = "0";

                    $final_amount = $params['amount'];
                    $percent_fee = ($final_amount * ($tax->pix_percent / 100));
                    $fixed_fee = $tax->pix_absolute;
                    if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                    $comission = ($percent_fee + $fixed_fee);
                    if($comission < $tax->min_fee_pix){ $comission = $tax->min_fee_pix; $min_fee = $tax->min_fee_pix; }else{ $min_fee = "NULL"; }

                }elseif($clients->currency == "usd"){

                    $final_amount = number_format(($params['amount'] / $cotacao_dolar_markup),6,".","");
                    $fixed_fee = number_format(($tax->pix_absolute),6,".","");
                    $percent_fee = number_format(($final_amount * ($tax->pix_percent / 100)),6,".","");
                    if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                    $comission = ($percent_fee + $fixed_fee);
                    if($comission < $tax->min_fee_pix){ $comission = $tax->min_fee_pix; $min_fee = $tax->min_fee_pix; }else{ $min_fee = "NULL"; }

                }

                $data_invoice_id = NULL;

                DB::beginTransaction();
                try {

                    // Insert Transaction
                    $transaction = Transactions::create([
                        "solicitation_date" => $date,
                        "final_date" => $date,
                        "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                        "code" => $params['pedido'],
                        "client_id" => $params['client_id'],
                        "order_id" => $params['order_id'],
                        "user_id" => $params['user_id'],
                        "user_document" => $params['documento_usuario'],
                        "user_account_data" => $user_account_data,
                        "user_name" => $params['nome_usuario'],
                        "id_bank" => $clients->bankPix->id,
                        "type_transaction" => 'deposit',
                        "method_transaction" => 'pix',
                        "amount_solicitation" => $params['amount'],
                        "final_amount" => $final_amount,
                        // "percent_fee" => $percent_fee,
                        // "fixed_fee" => $fixed_fee,
                        // "comission" => $comission,
                        // "min_fee" => $min_fee,
                        "status" => 'pending',
                        "data_bank" => $dados,
                        "payment_id" => "",
                        "code_bank" => "855",
                        "link_callback_bank" => $link_callback_bank,
                        "url_retorna" => "",
                        "data_invoice_id" => $data_invoice_id,
                        "quote" => $cotacao_dolar,
                        "percent_markup" => $spread_deposit,
                        "quote_markup" => $cotacao_dolar_markup,
                    ]);

                    DB::commit();

                    IndexTransaction::create([
                        "id_transaction" => $transaction->id,
                        "method_transaction" => "pix"
                    ]);

                    DB::commit();

                    $link_qr = "https://admin.fastpayments.com.br/qr/".$transaction->id."/".$params['order_id']."/200x200";

                    $json_return = array(
                        "order_id" => $params['order_id'],
                        "solicitation_date" => $date,
                        "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                        "code_identify" => $params['pedido'],
                        "amount" => $params['amount'],
                        "status" => "pending",
                        "link_qr" => $link_qr,
                        "content_qr" => $dados
                    );

                    // Success
                    return response()->json($json_return,200);

                }catch(exception $e){
                    DB::roolback();
                }

            }else{

                $ar = array(
                    "message" => "internal error ".$params['pedido'],
                    "token_bs2" => $token_bs2,
                    "return" => $register
                );

                return json_encode($ar);
            }

        }else{
            $data = [
                "message" => "error",
                "tk" => json_decode($tk,true)
            ];

            return response()->json($data);
        }

    }

    // Create deposit PIX
    public function createTransactionPIXNW($params = array()){

        $tk = json_decode($this->getTokenBS2PIX($params['client_id_bs2'],$params['client_secret_bs2'],"prod"),true);

        if(isset($tk['access_token'])){

            $token_bs2 = $tk['access_token'];

            // GET CLIENT
            $clients = Clients::where("id","=",$params['client_id'])->first();

            $register = json_decode($this->registerTransactionPIX($params,$token_bs2,$clients->method_pix),true);

            if(isset($register['qrCode'])){

                $dados = $register['qrCode'];
                $date = date("Y-m-d H:i:s");
                $barcode = "";

                // GET DAYS SAFE
                $days_safe_pix = $clients->days_safe_pix;

                // GET BANK DATA
                $bank = $clients->bankPix;
                $link_callback_bank = "";

                $user_data = array(
                    "name" => $params['nome_usuario'],
                    "document" => $params['documento_usuario'],
                    "bank_name" => $bank->name,
                    "holder" => $bank->holder,
                    "agency" => $bank->agency,
                    "account_number" => $bank->account,
                    "operation_bank" => $bank->type_account,
                    "user_id" => $params['user_id'],
                    "client_id" => $params['client_id'],
                    "address" => $this->trata_unicode($params['endereco_usuario']),
                    "district" => $this->trata_unicode($params['bairro_usuario']),
                    "city" => $this->trata_unicode($params['cidade_usuario']),
                    "uf" => $params['uf_usuario'],
                    "cep" => $params['cep_usuario']
                );

                $user_account_data = base64_encode(json_encode($user_data));

                // Calulo Taxas //
                $cot_ar = $this->get_cotacao_dolar($params['client_id'],"deposit");
                $cotacao_dolar_markup = $cot_ar['markup'];
                $cotacao_dolar = $cot_ar['quote'];
                $spread_deposit = $cot_ar['spread'];

                // Taxas
                $tax = $clients->tax;

                if($clients->currency == "brl"){

                    $cotacao_dolar_markup = "1";
                    $cotacao_dolar = "1";
                    $spread_deposit = "0";

                    $final_amount = $params['amount'];
                    $percent_fee = ($final_amount * ($tax->pix_percent / 100));
                    $fixed_fee = $tax->pix_absolute;
                    if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                    $comission = ($percent_fee + $fixed_fee);
                    if($comission < $tax->min_fee_pix){ $comission = $tax->min_fee_pix; $min_fee = $tax->min_fee_pix; }else{ $min_fee = "NULL"; }

                }elseif($clients->currency == "usd"){

                    $final_amount = number_format(($params['amount'] / $cotacao_dolar_markup),6,".","");
                    $fixed_fee = number_format(($tax->pix_absolute),6,".","");
                    $percent_fee = number_format(($final_amount * ($tax->pix_percent / 100)),6,".","");
                    if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                    $comission = ($percent_fee + $fixed_fee);
                    if($comission < $tax->min_fee_pix){ $comission = $tax->min_fee_pix; $min_fee = $tax->min_fee_pix; }else{ $min_fee = "NULL"; }

                }

                $data_invoice_id = NULL;

                $link_qr = "https://admin.fastpayments.com.br/qr/9999/".$params['order_id']."/200x200";

                $json_return = array(
                    "order_id" => $params['order_id'],
                    "solicitation_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                    "code_identify" => $params['pedido'],
                    "amount" => $params['amount'],
                    "status" => "pending",
                    "link_qr" => $link_qr,
                    "content_qr" => $dados
                );

                // Success
                return json_encode($json_return);

            }else{

                $ar = array(
                    "message" => "internal error ".$params['pedido'],
                    "token_bs2" => $token_bs2,
                    "return" => $register
                );

                return json_encode($ar);
            }

        }else{

        }

    }

    public function createTransactionPIXOPENPIX($params = array()){

        $token = $params['auth_openpix'];
        $clients = Clients::where("id","=",$params['client_id'])->first();

        $register = json_decode($this->registerTransactionPIXOPENPIX($params,$token),true);

        if(isset($register['brCode'])){

            $dados = $register['brCode'];
            $payment_id = $register['charge']['globalID'];
            $date = date("Y-m-d H:i:s");
            $barcode = "";

            $check_count = strlen($dados);

            if($check_count < 10){

                $path_name = "fastlogs-pix-".date("Y-m-d");

                if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                    mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                }

                $payload = [
                    "date" => $date,
                    "client" => $clients->name,
                    "params" => $params,
                    "return_openpix" => $register
                ];

                $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/pixlog.txt",json_encode($payload));

                $ar = array(
                    "code" => "558",
                    "message" => "Erro on create QrCode PIX ".$params['pedido'],
                    "token" => $token,
                    "return" => $register
                );

                return response()->json($ar,422);

            }

            // GET DAYS SAFE
            $days_safe_pix = $clients->days_safe_pix;

            // GET BANK DATA
            $bank = $clients->bankPix;
            $link_callback_bank = "";

            $user_data = array(
                "name" => $params['nome_usuario'],
                "document" => $params['documento_usuario'],
                "bank_name" => $bank->name,
                "holder" => $bank->holder,
                "agency" => $bank->agency,
                "account_number" => $bank->account,
                "operation_bank" => $bank->type_account,
                "user_id" => $params['user_id'],
                "client_id" => $params['client_id'],
                "address" => $this->trata_unicode($params['endereco_usuario']),
                "district" => $this->trata_unicode($params['bairro_usuario']),
                "city" => $this->trata_unicode($params['cidade_usuario']),
                "uf" => $params['uf_usuario'],
                "cep" => $params['cep_usuario']
            );

            $user_account_data = base64_encode(json_encode($user_data));

            // Taxas
            $tax = $clients->tax;

            $cotacao_dolar_markup = "1";
            $cotacao_dolar = "1";
            $spread_deposit = "0";

            $final_amount = $params['amount'];
            $percent_fee = ($final_amount * ($tax->pix_percent / 100));
            $fixed_fee = $tax->pix_absolute;
            if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
            $comission = ($percent_fee + $fixed_fee);
            if($comission < $tax->min_fee_pix){ $comission = $tax->min_fee_pix; $min_fee = $tax->min_fee_pix; }else{ $min_fee = "NULL"; }


            $data_invoice_id = NULL;

            DB::beginTransaction();
            try {

                // Insert Transaction
                $transaction = Transactions::create([
                    "solicitation_date" => $date,
                    "final_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                    "code" => $params['pedido'],
                    "client_id" => $params['client_id'],
                    "order_id" => $params['order_id'],
                    "user_id" => $params['user_id'],
                    "user_document" => $params['documento_usuario'],
                    "user_account_data" => $user_account_data,
                    "user_name" => $params['nome_usuario'],
                    "id_bank" => $clients->bankPix->id,
                    "type_transaction" => 'deposit',
                    "method_transaction" => 'pix',
                    "amount_solicitation" => $params['amount'],
                    "final_amount" => $final_amount,
                    // "percent_fee" => $percent_fee,
                    // "fixed_fee" => $fixed_fee,
                    // "comission" => $comission,
                    // "min_fee" => $min_fee,
                    "provider_reference" => $params['provider_reference'],
                    "status" => 'pending',
                    "bank_data" => $dados,
                    "payment_id" => $payment_id,
                    "code_bank" => "221",
                    "link_callback_bank" => $link_callback_bank,
                    "url_retorna" => "",
                    "data_invoice_id" => $data_invoice_id,
                    "quote" => $cotacao_dolar,
                    "percent_markup" => $spread_deposit,
                    "quote_markup" => $cotacao_dolar_markup,
                ]);

                DB::commit();

                $link_qr = "https://image-charts.com/chart?chs=350x350&cht=qr&chl=".$dados;

                if(in_array($clients->id,[11,27,28])){
                    $json_return = array(
                        "fast_id" => $transaction->id,
                        "order_id" => $params['order_id'],
                        "solicitation_date" => $date,
                        "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                        "code_identify" => $params['pedido'],
                        "provider_reference" => $transaction->id,
                        "amount" => $params['amount'],
                        "status" => "pending",
                        "link_qr" => $link_qr,
                        "content_qr" => $dados
                    );
                }else{
                    $json_return = array(
                        "order_id" => $params['order_id'],
                        "solicitation_date" => $date,
                        "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                        "code_identify" => $params['pedido'],
                        "amount" => $params['amount'],
                        "status" => "pending",
                        "link_qr" => $link_qr,
                        "content_qr" => $dados
                    );
                }

                $check_count = strlen($dados);

                if($check_count < 10){

                    $path_name = "fastlogs-pix-".date("Y-m-d");

                    if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                        mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                    }

                    $payload = [
                        "date" => $date,
                        "client" => $clients->name,
                        "params" => $params,
                        "return_openpix" => $register
                    ];

                    $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/pixlog.txt",json_encode($payload));

                    $ar = array(
                        "code" => "558",
                        "message" => "Erro on create QrCode PIX ".$params['pedido'],
                        "token" => $token,
                        "return" => $register
                    );

                    return response()->json($ar,422);

                }

                $path_name = "fastlogs-pix-success-".date("Y-m-d");

                if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                    mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                }

                $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode(["json_return" => $json_return, "return_openpix" => $register]));

                // Success
                return response()->json($json_return,200);

            }catch(exception $e){
                DB::roolback();
            }

        }else{

            $path_name = "fastlogs-pix-".date("Y-m-d");

            if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
            }

            $payload = [
                "date" => date("Y-m-d H:i:s"),
                "client" => $clients->name,
                "params" => $params,
                "return_openpix" => $register
            ];

            $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/pixlog.txt",json_encode($payload));

            $ar = array(
                "code" => "558",
                "message" => "Erro on create QrCode PIX ".$params['pedido'],
                "token" => $token,
                "return" => $register
            );

            return json_encode($ar);
        }

    }

    public function registerTransactionPIXOPENPIX($params = array(),$token){

        $data = [
            "correlationID" => $params['pedido'],
            "value" => str_replace(".","",($params['amount'] * 100)),
            "comment" => $params['pedido'],
            "customer" => [
                "name" => $params['nome_usuario'],
                "taxID" => $params['cpf'],
                "email" => "",
                "phone" => ""
            ],
            "additionalInfo" => [
                array(
                    "key" => "order_id",
                    "value" => $params['order_id']
                )
            ]
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.openpix.com.br/api/openpix/v1/charge',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Authorization: '.$token,
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function createTransactionTED($params = array()){

        $dados = "";
        $base64_image = "";
        $payment_id = "";
        $date = date("Y-m-d H:i:s");
        $barcode = "";

        $clients = Clients::where("id","=",$params['client_id'])->first();

        // GET DAYS SAFE
        $days_safe_ted = $clients->days_safe_ted;

        // GET BANK DATA
        $bank = $clients->bankTed;
        $link_callback_bank = "";

        $token = $clients->bankPix->auth_openpix;

        $register = json_decode($this->registerTransactionPIXOPENPIX($params,$token),true);

        if(isset($register['brCode'])){

            $dados = $register['brCode'];
            $payment_id = $register['charge']['globalID'];

        }

        $user_data = array(
            "name" => $params['nome_usuario'],
            "document" => $params['documento_usuario'],
            "bank_name" => $bank->name,
            "holder" => $bank->holder,
            "agency" => $bank->agency,
            "account_number" => $bank->account,
            "operation_bank" => $bank->type_account,
            "user_id" => $params['user_id'],
            "client_id" => $params['client_id'],
            "address" => $this->trata_unicode($params['endereco_usuario']),
            "district" => $this->trata_unicode($params['bairro_usuario']),
            "city" => $this->trata_unicode($params['cidade_usuario']),
            "uf" => $params['uf_usuario'],
            "cep" => $params['cep_usuario']
        );

        $user_account_data = base64_encode(json_encode($user_data));

        // Taxas
        $tax = $clients->tax;

        $cotacao_dolar_markup = "1";
        $cotacao_dolar = "1";
        $spread_deposit = "0";

        $final_amount = $params['amount'];
        $percent_fee = ($final_amount * ($tax->replacement_percent / 100));
        $fixed_fee = $tax->replacement_absolute;
        if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
        $comission = ($percent_fee + $fixed_fee);
        if($comission < $tax->min_fee_deposit){ $comission = $tax->min_fee_deposit; $min_fee = $tax->min_fee_deposit; }else{ $min_fee = "NULL"; }

        $data_invoice_id = NULL;

        DB::beginTransaction();
        try {

            // Insert Transaction
            $transaction = Transactions::create([
                "solicitation_date" => $date,
                "final_date" => $date,
                "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_ted." days")),
                "code" => $params['pedido'],
                "client_id" => $params['client_id'],
                "order_id" => $params['order_id'],
                "user_id" => $params['user_id'],
                "user_document" => $params['documento_usuario'],
                "user_account_data" => $user_account_data,
                "user_name" => $params['nome_usuario'],
                "id_bank" => $clients->bankTed->id,
                "type_transaction" => 'deposit',
                "method_transaction" => 'ted',
                "amount_solicitation" => $params['amount'],
                "final_amount" => $final_amount,
                "status" => 'pending',
                "data_bank" => $dados,
                "payment_id" => $payment_id,
                "code_bank" => $bank->code,
                "link_callback_bank" => $link_callback_bank,
                "url_retorna" => "",
                "data_invoice_id" => $data_invoice_id,
                "quote" => $cotacao_dolar,
                "percent_markup" => $spread_deposit,
                "quote_markup" => $cotacao_dolar_markup,
            ]);

            DB::commit();

            $json_return = array(
                "order_id" => $params['order_id'],
                "solicitation_date" => $date,
                "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_ted." days")),
                "bank_name" => $bank->name,
                "bank_code" => $bank->code,
                "bank_holder" => $bank->holder,
                "bank_document" => $bank->document,
                "bank_agency" => $bank->agency,
                "bank_account" => $bank->account,
                "bank_type_account" => $bank->type_account,
                // "bank_name" => "---",
                // "bank_code" => "---",
                // "bank_holder" => "---",
                // "bank_document" => "---",
                // "bank_agency" => "---",
                // "bank_account" => "---",
                // "bank_type_account" => "---",
                "code_identify" => $params['pedido'],
                "amount" => $params['amount'],
                "status" => "pending",
                "qrcode" => "https://image-charts.com/chart?chs=350x350&cht=qr&chl=".$dados
            );

            // Success
            return response()->json($json_return,200);

        }catch(exception $e){
            DB::roolback();
        }

    }


    public function registerTransactionPIX($params = array(),$token_bs2,$type_request){

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.bs2.com/pix/direto/forintegration/v1/chaves",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer ".$token_bs2,
            "Content-Type: application/json",
        ),
        ));

        $response_key = curl_exec($curl);

        curl_close($curl);

        $content_key = json_decode($response_key,true);

        $key = $content_key['items'][0]['valor'];

        if($type_request == "estatico"){

            // Post Estático
            $post = array(
                "chave" => $key,
                "valor" => floatval($params['amount']),
                "campoLivre" => $params['pedido'],
                "txId" => $params['pedido'],
                "aceitarMaisDeUmPagamento" => false
            );

            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.bs2.com/pix/direto/forintegration/v1/qrcodes/estatico",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($post),
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer ".$token_bs2,
                "Content-Type: application/json",
                "Content-Length: ".strlen(json_encode($post)),
            ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            return $response;

        }elseif($type_request == "dinamico"){

            // Post Dinamico
            $post = array(
                "txId" => $params['pedido'],
                "cobranca" => array(
                    "calendario" => array(
                        "expiracao" => floatval(86400)
                    ),
                    "devedor" => array(
                        "cpf" => $params['cpf'],
                        "nome" => $params['nome_usuario']
                    ),
                    "valor" => array(
                        "original" => floatval($params['amount'])
                    ),
                    "chave" => $key,
                    "solicitacaoPagador" => $params['pedido'],
                    "infoAdicionais" => array(
                        array(
                            "nome" => $params['pedido'],
                            "valor" => ""
                        )
                    )
                ),
                "aceitaMaisDeUmPagamento" => false,
                "recebivelAposVencimento" => false
            );

            $curl = curl_init();

            curl_setopt_array($curl, array(
            // CURLOPT_URL => "https://api.bs2.com/pix/direto/forintegration/v1/qrcodes/estatico",
            CURLOPT_URL => "https://api.bs2.com/pix/direto/forintegration/v1/qrcodes/dinamico",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($post),
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer ".$token_bs2,
                "Content-Type: application/json",
                "Content-Length: ".strlen(json_encode($post)),
            ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            return $response;

        }

    }

    public function createTransactionPIXSHIPAY($params = array()){

        $response_token = json_decode($this->getTokenShipay($params['shipay_client_id'],$params['shipay_access_key'],$params['shipay_secret_key']),true);
        // $response_token = $this->getTokenShipay($params['shipay_client_id'],$params['shipay_access_key'],$params['shipay_secret_key']);
        // return $response_token;
        $token = $response_token['access_token'];
        $clients = Clients::where("id","=",$params['client_id'])->first();

        $register_raw = json_decode($this->registerTransactionPIXSHIPAY($params,$token),true);
        $register = $register_raw['response'];

        if(isset($register['qr_code_text'])){

            $dados = $register['qr_code_text'];
            $payment_id = $register['order_id'];
            $date = date("Y-m-d H:i:s");
            $barcode = "";

            // GET DAYS SAFE
            $days_safe_pix = $clients->days_safe_pix;

            // GET BANK DATA
            $bank = $clients->bankPix;
            $link_callback_bank = "";

            $user_data = array(
                "name" => $params['nome_usuario'],
                "document" => $params['documento_usuario'],
                "bank_name" => $bank->name,
                "holder" => $bank->holder,
                "agency" => $bank->agency,
                "account_number" => $bank->account,
                "operation_bank" => $bank->type_account,
                "user_id" => $params['user_id'],
                "client_id" => $params['client_id'],
                "address" => $this->trata_unicode($params['endereco_usuario']),
                "district" => $this->trata_unicode($params['bairro_usuario']),
                "city" => $this->trata_unicode($params['cidade_usuario']),
                "uf" => $params['uf_usuario'],
                "cep" => $params['cep_usuario']
            );

            $user_account_data = base64_encode(json_encode($user_data));

            // Taxas
            $tax = $clients->tax;

            $cotacao_dolar_markup = "1";
            $cotacao_dolar = "1";
            $spread_deposit = "0";

            $final_amount = $params['amount'];
            $percent_fee = ($final_amount * ($tax->pix_percent / 100));
            $fixed_fee = $tax->pix_absolute;
            if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
            $comission = ($percent_fee + $fixed_fee);
            if($comission < $tax->min_fee_pix){ $comission = $tax->min_fee_pix; $min_fee = $tax->min_fee_pix; }else{ $min_fee = "NULL"; }

            $data_invoice_id = NULL;

            DB::beginTransaction();
            try {

                // Insert Transaction
                $transaction = Transactions::create([
                    "solicitation_date" => $date,
                    "final_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                    "code" => $params['pedido'],
                    "client_id" => $params['client_id'],
                    "order_id" => $params['order_id'],
                    "user_id" => $params['user_id'],
                    "user_document" => $params['documento_usuario'],
                    "user_account_data" => $user_account_data,
                    "user_name" => $params['nome_usuario'],
                    "id_bank" => $clients->bankPix->id,
                    "type_transaction" => 'deposit',
                    "method_transaction" => 'pix',
                    "amount_solicitation" => $params['amount'],
                    "final_amount" => $final_amount,
                    // "percent_fee" => $percent_fee,
                    // "fixed_fee" => $fixed_fee,
                    // "comission" => $comission,
                    // "min_fee" => $min_fee,
                    "provider_reference" => $params['provider_reference'],
                    "status" => 'pending',
                    "data_bank" => $dados,
                    "payment_id" => $payment_id,
                    "code_bank" => "221",
                    "link_callback_bank" => $link_callback_bank,
                    "url_retorna" => "",
                    "data_invoice_id" => $data_invoice_id,
                    "quote" => $cotacao_dolar,
                    "percent_markup" => $spread_deposit,
                    "quote_markup" => $cotacao_dolar_markup,
                ]);

                DB::commit();

                $link_qr = "https://image-charts.com/chart?chs=350x350&cht=qr&chl=".$dados;

                $json_return = array(
                    "order_id" => $params['order_id'],
                    "solicitation_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                    "code_identify" => $params['pedido'],
                    "amount" => $params['amount'],
                    "status" => "pending",
                    "link_qr" => $link_qr,
                    "content_qr" => $dados
                );

                // Success
                return response()->json($json_return,200);

            }catch(exception $e){
                DB::roolback();
            }

        }else{

            $ar = array(
                "message" => "internal error ".$params['pedido'],
                "token" => $token,
                "return" => $register_raw
            );

            return json_encode($ar);
        }

    }

    public function getTokenShipay($client_id,$access_key,$secret_key){

        $bank = Banks::where("shipay_client_id",$client_id)
        ->where("shipay_access_key",$access_key)
        ->where("shipay_secret_key",$secret_key)
        ->first();

        if(!is_null($bank->token_shipay)){

            $check_token = $this->checkTokenShipay($bank->token_shipay);

            if($check_token === true){

                $data = [
                    "access_token" => $bank->token_shipay
                ];

                return json_encode($data);

            }elseif($check_token === false){

                $data = [
                    "access_key" => $access_key,
                    "secret_key" => $secret_key,
                    "client_id" => $client_id,
                ];

                $curl = curl_init();

                curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.shipay.com.br/pdvauth',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
                ));

                $response = curl_exec($curl);

                curl_close($curl);

                $result = json_decode($response,true);
                if(isset($result['access_token'])){
                    $new_token = $result['access_token'];

                    DB::beginTransaction();
                    try{

                        $bank->update([
                            "token_shipay" => $new_token
                        ]);

                        DB::commit();

                    }catch(Exception $e){
                        DB::roolback();
                    }

                    return $response;
                }else{

                    return response()->json($result);

                }

            }

        }else{

            $data = [
                "access_key" => $access_key,
                "secret_key" => $secret_key,
                "client_id" => $client_id,
            ];


            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.shipay.com.br/pdvauth',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data,true),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
            ));

            $response = curl_exec($curl);

            return $response;

            curl_close($curl);

            $result = json_decode($response,true);
            if(isset($result['access_token'])){
                $new_token = $result['access_token'];

                DB::beginTransaction();
                try{

                    $bank->update([
                        "token_shipay" => $new_token
                    ]);

                    DB::commit();

                }catch(Exception $e){
                    DB::roolback();
                }
            }


            return $response;

        }

    }

    public function checkTokenShipay($token_shipay){

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.shipay.com.br/v1/wallets',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer '.$token_shipay
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $result = json_decode($response,true);
        if(isset($result[0]['friendly_name'])){
            return true;
        }else{
            return false;
        }


    }

    public function registerTransactionPIXSHIPAY($params,$token){

        $data = [
            "order_ref" => $params['pedido'],
            "callback_url" => "https://shipayhook.fastpayments.com.br/api/shipaywebhook",
            "wallet" => $params['shipay_method'],
            "total" => floatval($params['amount']),
            "items" => [
                array(
                    "item_title" => "Compra n° ".$params['pedido']." FastPayments",
                    "unit_price" => floatval($params['amount']),
                    "quantity" => 1
                )
            ],
            "buyer" => [
                "name" => $params['nome_usuario'],
                "cpf_cnpj" => $params['documento_usuario'],
                "email" => "",
                "phone" => ""
            ]
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.shipay.com.br/order',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data,true),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$token,
            'x-shipay-order-type: e-order'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $return = [
            "data" => $data,
            "response" => json_decode($response,true)
        ];

        return json_encode($return,true);

    }

    public function createTransactionPIXGenial($params = array()){

        // GET CLIENT
        $clients = Clients::where("id","=",$params['client_id'])->first();

        $register = json_decode($this->registerTransactionPIXGenial($params,$params['login_genial'],$params['pass_genial']),true);

        if(isset($register['data']['textContentField'])){

            $dados = $register['data']['textContentField'];
            $date = date("Y-m-d H:i:s");
            $barcode = "";

            // GET DAYS SAFE
            $days_safe_pix = $clients->days_safe_pix;

            // GET BANK DATA
            $bank = $clients->bankPix;
            $link_callback_bank = "";

            $user_data = array(
                "name" => $params['nome_usuario'],
                "document" => $params['documento_usuario'],
                "bank_name" => $bank->name,
                "holder" => $bank->holder,
                "agency" => $bank->agency,
                "account_number" => $bank->account,
                "operation_bank" => $bank->type_account,
                "user_id" => $params['user_id'],
                "client_id" => $params['client_id'],
                "address" => $this->trata_unicode($params['endereco_usuario']),
                "district" => $this->trata_unicode($params['bairro_usuario']),
                "city" => $this->trata_unicode($params['cidade_usuario']),
                "uf" => $params['uf_usuario'],
                "cep" => $params['cep_usuario']
            );

            $user_account_data = base64_encode(json_encode($user_data));

            // Calulo Taxas //
            $cot_ar = $this->get_cotacao_dolar($params['client_id'],"deposit");
            $cotacao_dolar_markup = $cot_ar['markup'];
            $cotacao_dolar = $cot_ar['quote'];
            $spread_deposit = $cot_ar['spread'];

            // Taxas
            $tax = $clients->tax;

            if($clients->currency == "brl"){

                $cotacao_dolar_markup = "1";
                $cotacao_dolar = "1";
                $spread_deposit = "0";

                $final_amount = $params['amount'];
                $percent_fee = ($final_amount * ($tax->pix_percent / 100));
                $fixed_fee = $tax->pix_absolute;
                if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                $comission = ($percent_fee + $fixed_fee);
                if($comission < $tax->min_fee_pix){ $comission = $tax->min_fee_pix; $min_fee = $tax->min_fee_pix; }else{ $min_fee = "NULL"; }

            }elseif($clients->currency == "usd"){

                $final_amount = number_format(($params['amount'] / $cotacao_dolar_markup),6,".","");
                $fixed_fee = number_format(($tax->pix_absolute),6,".","");
                $percent_fee = number_format(($final_amount * ($tax->pix_percent / 100)),6,".","");
                if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                $comission = ($percent_fee + $fixed_fee);
                if($comission < $tax->min_fee_pix){ $comission = $tax->min_fee_pix; $min_fee = $tax->min_fee_pix; }else{ $min_fee = "NULL"; }

            }

            $data_invoice_id = NULL;

            DB::beginTransaction();
            try {

                // Insert Transaction
                $transaction = Transactions::create([
                    "solicitation_date" => $date,
                    "final_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                    "code" => $params['pedido'],
                    "client_id" => $params['client_id'],
                    "order_id" => $params['order_id'],
                    "user_id" => $params['user_id'],
                    "user_document" => $params['documento_usuario'],
                    "user_account_data" => $user_account_data,
                    "user_name" => $params['nome_usuario'],
                    "id_bank" => $clients->bankPix->id,
                    "type_transaction" => 'deposit',
                    "method_transaction" => 'pix',
                    "amount_solicitation" => $params['amount'],
                    "final_amount" => $final_amount,
                    // "percent_fee" => $percent_fee,
                    // "fixed_fee" => $fixed_fee,
                    // "comission" => $comission,
                    // "min_fee" => $min_fee,
                    "status" => 'pending',
                    "data_bank" => $dados,
                    "payment_id" => "",
                    "code_bank" => $bank->code,
                    "link_callback_bank" => $link_callback_bank,
                    "url_retorna" => "",
                    "data_invoice_id" => $data_invoice_id,
                    "quote" => $cotacao_dolar,
                    "percent_markup" => $spread_deposit,
                    "quote_markup" => $cotacao_dolar_markup,
                ]);

                DB::commit();

                IndexTransaction::create([
                    "id_transaction" => $transaction->id,
                    "method_transaction" => "pix"
                ]);

                DB::commit();

                $link_qr = "https://admin.fastpayments.com.br/qr/".$transaction->id."/".$params['order_id']."/200x200";

                $json_return = array(
                    "order_id" => $params['order_id'],
                    "solicitation_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                    "code_identify" => $params['pedido'],
                    "amount" => $params['amount'],
                    "status" => "pending",
                    "link_qr" => $link_qr,
                    "content_qr" => $dados
                );

                // Success
                return response()->json($json_return,200);

            }catch(exception $e){
                DB::roolback();
            }

        }

    }

    public function registerTransactionPIXGenial($params = array(),$login_genial,$pass_genial){

        $client = Clients::where("id","=",$params['client_id'])->first();
        $bank = $client->bankPix;

        // Post Estático
        $post = [
            "authentication" => [
                "User" => $login_genial,
                "Password" => $pass_genial,
                "Agency" => floatval($bank->agency),
                "AccountNumber" => floatval($bank->account),
                "CPF_CNPJ" => $bank->document
            ],
            "value" => $params['amount'],
            "key" => "58a5e6db-2a1f-4da9-9732-55faed1c7cf3",
            "keyType" => "EVP",
            "reference" => $params['pedido'],
            "accountHolderName" => $bank->holder,
            "additionalInformation" => $params['pedido']
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://pixlatam.bancoplural.com/api/PIX/v1/GenerateStaticQRCode",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($post),
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function createTransactionPIXGerencianet($params = array()){

        // GET CLIENT
        $clients = Clients::where("id","=",$params['client_id'])->first();

        $auth = json_decode($this->authenticationGerencianet($params['client_id_gerencianet'],$params['password_gerencianet'],$params['path_gerencianet']),true);

        if(isset($auth)){
            $authentication = $auth['access_token'];

            $register = json_decode($this->registerCobPIXGerencianet($params,$authentication,$params['path_gerencianet']),true);

            if(isset($register['loc']['id'])){

                $get_content_qr = json_decode($this->getCobPIXGerencianet($register['loc']['id'],$authentication,$params['path_gerencianet']),true);

                $dados = "";
                $data_bank = $get_content_qr['qrcode'];
                $payment_id = $register['txid'];
                $date = date("Y-m-d H:i:s");
                $barcode = "";

                // GET DAYS SAFE
                $days_safe_pix = $clients->days_safe_pix;

                // GET BANK DATA
                $bank = $clients->bankPix;
                $link_callback_bank = "";

                $user_data = array(
                    "name" => $params['nome_usuario'],
                    "document" => $params['documento_usuario'],
                    "bank_name" => $bank->name,
                    "holder" => $bank->holder,
                    "agency" => $bank->agency,
                    "account_number" => $bank->account,
                    "operation_bank" => $bank->type_account,
                    "user_id" => $params['user_id'],
                    "client_id" => $params['client_id'],
                    "address" => $this->trata_unicode($params['endereco_usuario']),
                    "district" => $this->trata_unicode($params['bairro_usuario']),
                    "city" => $this->trata_unicode($params['cidade_usuario']),
                    "uf" => $params['uf_usuario'],
                    "cep" => $params['cep_usuario']
                );

                $user_account_data = base64_encode(json_encode($user_data));

                // Calulo Taxas //
                $cot_ar = $this->get_cotacao_dolar($params['client_id'],"deposit");
                $cotacao_dolar_markup = $cot_ar['markup'];
                $cotacao_dolar = $cot_ar['quote'];
                $spread_deposit = $cot_ar['spread'];

                // Taxas
                $tax = $clients->tax;

                if($clients->currency == "brl"){

                    $cotacao_dolar_markup = "1";
                    $cotacao_dolar = "1";
                    $spread_deposit = "0";

                    $final_amount = $params['amount'];
                    $percent_fee = ($final_amount * ($tax->pix_percent / 100));
                    $fixed_fee = $tax->pix_absolute;
                    if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                    $comission = ($percent_fee + $fixed_fee);
                    if($comission < $tax->min_fee_pix){ $comission = $tax->min_fee_pix; $min_fee = $tax->min_fee_pix; }else{ $min_fee = "NULL"; }

                }elseif($clients->currency == "usd"){

                    $final_amount = number_format(($params['amount'] / $cotacao_dolar_markup),6,".","");
                    $fixed_fee = number_format(($tax->pix_absolute),6,".","");
                    $percent_fee = number_format(($final_amount * ($tax->pix_percent / 100)),6,".","");
                    if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                    $comission = ($percent_fee + $fixed_fee);
                    if($comission < $tax->min_fee_pix){ $comission = $tax->min_fee_pix; $min_fee = $tax->min_fee_pix; }else{ $min_fee = "NULL"; }

                }

                $data_invoice_id = NULL;

                DB::beginTransaction();
                try {

                    // Insert Transaction
                    $transaction = Transactions::create([
                        "solicitation_date" => $date,
                        "final_date" => $date,
                        "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                        "code" => $params['pedido'],
                        "client_id" => $params['client_id'],
                        "order_id" => $params['order_id'],
                        "user_id" => $params['user_id'],
                        "user_document" => $params['documento_usuario'],
                        "user_account_data" => $user_account_data,
                        "user_name" => $params['nome_usuario'],
                        "id_bank" => $clients->bankPix->id,
                        "type_transaction" => 'deposit',
                        "method_transaction" => 'pix',
                        "amount_solicitation" => $params['amount'],
                        "final_amount" => $final_amount,
                        // "percent_fee" => $percent_fee,
                        // "fixed_fee" => $fixed_fee,
                        // "comission" => $comission,
                        // "min_fee" => $min_fee,
                        "status" => 'pending',
                        "dados" => $dados,
                        "data_bank" => $data_bank,
                        "payment_id" => $payment_id,
                        "code_bank" => $bank->code,
                        "link_callback_bank" => $link_callback_bank,
                        "url_retorna" => "",
                        "data_invoice_id" => $data_invoice_id,
                        "quote" => $cotacao_dolar,
                        "percent_markup" => $spread_deposit,
                        "quote_markup" => $cotacao_dolar_markup,
                    ]);

                    DB::commit();

                    IndexTransaction::create([
                        "id_transaction" => $transaction->id,
                        "method_transaction" => "pix"
                    ]);

                    DB::commit();

                    $link_qr = "https://admin.fastpayments.com.br/qr/".$transaction->id."/".$params['order_id']."/200x200";

                    $json_return = array(
                        "order_id" => $params['order_id'],
                        "solicitation_date" => $date,
                        "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                        "code_identify" => $params['pedido'],
                        "amount" => $params['amount'],
                        "status" => "pending",
                        "link_qr" => $link_qr,
                        "content_qr" => $data_bank
                    );

                    // Success
                    return response()->json($json_return,200);

                }catch(exception $e){
                    DB::roolback();
                }

            }

        }

    }

    public function registerCobPIXGerencianet($params = array(),$authentication,$path_gerencianet){

        $config = [
            "certificado" => $path_gerencianet,
        ];

        $data = [
            "calendario" => [
                "expiracao" => 3600
            ],
            "devedor" => [
                "cpf" => $params['cpf'],
                "nome" => $params['nome_usuario']
            ],
            "valor" => [
                "original" => $params['amount']
            ],
            "chave" => "095a2618-4c26-4ce1-ace5-1be97e10c54b",
            "solicitacaoPagador" => $params['pedido']
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api-pix.gerencianet.com.br/v2/cob',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_SSLCERT => $config['certificado'], // Caminho do certificado
        CURLOPT_SSLCERTPASSWD => "",
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Token: Bearer '.$authentication,
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function authenticationGerencianet($client_id_gerencianet,$password_gerencianet,$path_gerencianet){

        $config = [
            "certificado" => $path_gerencianet,
            "client_id" => $client_id_gerencianet,
            "client_secret" => $password_gerencianet
        ];

        $data = [
            "grant_type" => "client_credentials"
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api-pix.gerencianet.com.br/oauth/token',
            CURLOPT_USERPWD => $config["client_id"] . ":" . $config["client_secret"],
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_SSLCERT => $config['certificado'], // Caminho do certificado
            CURLOPT_SSLCERTPASSWD => "",
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Content-Length: '.strlen(json_encode($data))
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    // Get Content QrCode PIX LocID Gerencianet
    public function getCobPIXGerencianet($loc_id,$authentication,$path_gerencianet){

        $config = [
            "certificado" => $path_gerencianet,
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api-pix.gerencianet.com.br/v2/loc/'.$loc_id.'/qrcode',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_SSLCERT => $config['certificado'], // Caminho do certificado
        CURLOPT_SSLCERTPASSWD => "",
        CURLOPT_HTTPHEADER => array(
            'Token: Bearer '.$authentication
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    // Create Boleto on DB
    public function createBoletoBB($params = array(),$result){
        $dados = "";
        $date = date("Y-m-d H:i:s");

        if($result != ""){
            $barcode = $result['linhaDigitavel'];
        }else{
            $barcode = "";
        }

        // GET ALL CLIENT
        $clients = Clients::where("id","=",$params['client_id'])->first();
        $days_safe_boleto = $clients->days_safe_boleto;

        // GET BANK DATA
        $bank = $clients->bankInvoice;
        $link_callback_bank = "";

        $user_data = array(
            "name" => $params['nome_usuario'],
            "document" => $params['documento_usuario'],
            "bank_name" => $bank->name,
            "holder" => $bank->holder,
            "agency" => $bank->agency,
            "account_number" => $bank->account,
            "operation_bank" => $bank->type_account,
            "user_id" => $params['user_id'],
            "client_id" => $params['client_id'],
            "address" => $this->trata_unicode($params['endereco_usuario']),
            "district" => $this->trata_unicode($params['bairro_usuario']),
            "city" => $this->trata_unicode($params['cidade_usuario']),
            "uf" => $params['uf_usuario'],
            "cep" => $params['cep_usuario']
        );

        $user_account_data = base64_encode(json_encode($user_data));

        // Calulo Taxas //
        $cot_ar = $this->get_cotacao_dolar($params['client_id'],"deposit");
        $cotacao_dolar_markup = $cot_ar['markup'];
        $cotacao_dolar = $cot_ar['quote'];
        $spread_deposit = $cot_ar['spread'];

        // Taxas
        $tax = $clients->tax;

        if($clients->currency == "brl"){

            $final_amount = $params['amount'];
            $percent_fee = ($final_amount * ($tax->boleto_percent / 100));
            $fixed_fee = $tax->boleto_absolute;
            if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
            $comission = ($percent_fee + $fixed_fee);
            if($comission < $tax->min_fee_boleto){ $comission = $tax->min_fee_boleto; $min_fee = $tax->min_fee_boleto; }else{ $min_fee = "NULL"; }

        }elseif($clients->currency == "usd"){

            $final_amount = number_format(($params['amount'] / $cotacao_dolar_markup),6,".","");
            $fixed_fee = number_format(($tax->boleto_absolute),6,".","");
            $percent_fee = number_format(($final_amount * ($tax->boleto_percent / 100)),6,".","");
            if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
            $comission = ($percent_fee + $fixed_fee);
            if($comission < $tax->min_fee_boleto){ $comission = $tax->min_fee_boleto; $min_fee = $tax->min_fee_boleto; }else{ $min_fee = "NULL"; }

        }

        $data_invoice_id = NULL;

        DB::beginTransaction();
        try {

            if($barcode != ""){
                // Insert Data Invoice
                $datainvoice = DataInvoice::create([
                    "client_id" => $params['client_id'],
                    "order_id" => $params['order_id'],
                    "barcode" => $barcode,
                    "status_boletofirst" => "pending",
                    "registered_boletofirst" => 0,
                    // "done_at" => date("Y-m-d",strtotime($date."+ ".$days_safe_boleto." days")),
                    "error_boletofirst" => NULL
                ]);

                $data_invoice_id = $datainvoice->id;
            }

            // Insert Transaction
            $transaction = Transactions::create([
                "solicitation_date" => $date,
                "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_boleto." days")),
                "code" => $params['pedido'],
                "client_id" => $params['client_id'],
                "order_id" => $params['order_id'],
                "user_id" => $params['user_id'],
                "user_document" => $params['documento_usuario'],
                "user_account_data" => $user_account_data,
                "user_name" => $params['nome_usuario'],
                "id_bank" => $clients->bankInvoice->id,
                "type_transaction" => 'deposit',
                "method_transaction" => 'invoice',
                "amount_solicitation" => $params['amount'],
                "final_amount" => $final_amount,
                // "percent_fee" => $percent_fee,
                // "fixed_fee" => $fixed_fee,
                // "comission" => $comission,
                // "min_fee" => $min_fee,
                "status" => 'pending',
                "data_bank" => $dados,
                "payment_id" => "",
                "code_bank" => "100",
                "link_callback_bank" => $link_callback_bank,
                "url_retorna" => "",
                "data_invoice_id" => $data_invoice_id,
                "quote" => $cotacao_dolar,
                "percent_markup" => $spread_deposit,
                "quote_markup" => $cotacao_dolar_markup,
            ]);

            DB::commit();

            $link_invoice = "https://admin.fastpayments.com.br/get-invoice-bb/".$params['authorization']."/".$params['order_id'];

            if($barcode == ""){
                $json_return = array(
                    "order_id" => $params['order_id'],
                    "solicitation_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_boleto." days")),
                    "code_identify" => $params['pedido'],
                    "amount" => $params['amount'],
                    "bank_name" => $bank->name,
                    "holder" => $bank->holder,
                    "agency" => $bank->agency,
                    "type_account" => $bank->type_account,
                    "account" => $bank->account,
                    "status" => "pending",
                    "link_invoice" => $link_invoice
                );

            }else{
                $json_return = array(
                    "order_id" => $params['order_id'],
                    "solicitation_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_boleto." days")),
                    "code_identify" => $params['pedido'],
                    "amount" => $params['amount'],
                    "bank_name" => $bank->name,
                    "holder" => $bank->holder,
                    "agency" => $bank->agency,
                    "type_account" => $bank->type_account,
                    "account" => $bank->account,
                    "status" => "pending",
                    "link_invoice" => $link_invoice,
                    "bar_code" => $barcode
                );
            }

            // Success
            return response()->json($json_return,200);

        }catch(exception $e){
            DB::roolback();
        }
    }

    public function createWithdraw($params = array()){

        $dados = "";
        $payment_id = "";
        $date = date("Y-m-d H:i:s");

        // GET ALL CLIENT
        $clients = Clients::where("id","=",$params['client_id'])->first();

        // GET BANK DATA
        $link_callback_bank = "";

        $user_data = array(
            "name" => $params['user_name'],
            "document" => $params['user_document'],
            "bank_code" => $params['bank_code'],
            "bank_name" => $params['bank_name'],
            "holder" => "",
            "agency" => $params['agency'],
            "account_number" => $params['account'],
            "operation_bank" => $params['type_operation'],
            "user_id" => $params['user_id'],
            "client_id" => $params['client_id'],
            "address" => "",
            "district" => "",
            "city" => "",
            "uf" => "",
            "cep" => "",
            "pix_key" => $params['pix_key'],
            "type_pixkey" => $params['type_pixkey'],
        );

        $user_account_data = base64_encode(json_encode($user_data));

        // Taxas
        $tax = $clients->tax;

        $percent_fee = ($params['amount'] * ($tax->withdraw_percent / 100));
        $fixed_fee = floatval($tax->withdraw_absolute);
        $comission = floatval($percent_fee + $fixed_fee);
        $min_fee_withdraw = floatval($tax->min_fee_withdraw);
        $max_fee_withdraw = floatval($tax->max_fee_withdraw);
        if($comission <= $min_fee_withdraw){
            $percent_fee = $min_fee_withdraw;
            $comission = $min_fee_withdraw;
        }else{

            if($max_fee_withdraw > 0 && $comission >= $max_fee_withdraw){
                $percent_fee = $max_fee_withdraw;
                $comission = $max_fee_withdraw;
            }

        }

        $id_bank_withdraw = $clients->bank_withdraw_permition;

        // if($params['user_id'] == "123456"){
        //     return [
        //         "percent_fee" => $percent_fee,
        //         "fixed_fee" => $fixed_fee,
        //         "comission" => $comission,
        //         "min_fee_withdraw" => $min_fee_withdraw,
        //     ];
        // }

        if($params['method'] == "pix"){
            $method_transaction = "pix";
        }else{
            $method_transaction = "TED";
        }

        DB::beginTransaction();
        try {

            // Insert Transaction
            $transaction = Transactions::create([
                "solicitation_date" => $date,
                "final_date" => $date,
                "code" => $params['pedido'],
                "client_id" => $params['client_id'],
                "order_id" => $params['order_id'],
                "user_id" => $params['user_id'],
                "user_document" => $params['user_document'],
                "user_account_data" => $user_account_data,
                "user_name" => $params['user_name'],
                "type_transaction" => 'withdraw',
                "method_transaction" => $method_transaction,
                "amount_solicitation" => $params['amount'],
                "final_amount" => $params['amount'],
                "percent_fee" => $percent_fee,
                "fixed_fee" => $fixed_fee,
                "comission" => $comission,
                "status" => 'pending',
                "data_bank" => $dados,
                "payment_id" => $payment_id,
                "id_bank" => $id_bank_withdraw,
                "code_bank" => $params['bank_code'],
            ]);

            DB::commit();

            if($method_transaction == "TED"){
                $json_return = array(
                    "order_id" => $params['order_id'],
                    "solicitation_date" => $date,
                    "user_id" => $params['user_id'],
                    "user_name" => $params['user_name'],
                    "user_document" => $params['user_document'],
                    "bank_name" => $params['bank_name'],
                    "agency" => $params['agency'],
                    "type_operation" => $params['type_operation'],
                    "account" => $params['account'],
                    "amount_solicitation" => $params['amount'],
                    "status" => "pending"
                );
            }else{

                if(in_array($clients->id,[11,27,28])){
                    $json_return = array(
                        "order_id" => $params['order_id'],
                        "solicitation_date" => $date,
                        "provider_reference" => $transaction->id,
                        "user_id" => $params['user_id'],
                        "user_name" => $params['user_name'],
                        "user_document" => $params['user_document'],
                        "amount_solicitation" => $params['amount'],
                        "status" => "pending"
                    );
                }else{
                    $json_return = array(
                        "order_id" => $params['order_id'],
                        "solicitation_date" => $date,
                        "user_id" => $params['user_id'],
                        "user_name" => $params['user_name'],
                        "user_document" => $params['user_document'],
                        "amount_solicitation" => $params['amount'],
                        "status" => "pending"
                    );
                }
            }

            $today = date("Y-m-d 00:00:00");

            $amount_withdraw = $transaction->amount_solicitation;

            $av_today = Extract::where("client_id",$clients->id)
                ->where("disponibilization_date","<=",$today)
                ->sum("final_amount");

            $total_available = Extract::where("client_id",$clients->id)->where("disponibilization_date","<=",$today)->sum("final_amount");

            // Select all withdraws pending
            $sql_all_withdraw_pending = Transactions::where("client_id",$clients->id)->where("status","pending")->where("type_transaction","withdraw")->sum('amount_solicitation');
            if(!empty($sql_all_withdraw_pending[0])){
                $total_withdraw_pending = $sql_all_withdraw_pending;
            }else{
                $total_withdraw_pending = 0;
            }

            if($total_available > 0){
                // if(($total_available - $total_withdraw_pending) > 0){
                    // if((($total_available - $total_withdraw_pending) - $amount_withdraw) >= 0){
                    if(($total_available - $amount_withdraw) >= 0){

                        if($clients->withdraw_permition === true){
                            $id_bank_withdraw = $clients->bank_withdraw_permition;

                            $bank_withdraw = Banks::where("id",$id_bank_withdraw)->first();

                            if($bank_withdraw){
                                if($bank_withdraw->withdraw_permition === true){

                                    if($params['method'] == "pix" && $bank_withdraw->code == "221"){
                                        \App\Jobs\PerformWithdrawalPaymentPIX::dispatch($transaction->id,$bank_withdraw->id)->delay(now()->addSeconds('5'));
                                    }elseif($params['method'] == "pix" && $bank_withdraw->code == "587"){

                                        $return_info = [
                                            "bank_withdraw_code" => $bank_withdraw->code,
                                            "bank_id" => $bank_withdraw->id,
                                            "transaction_id" => $transaction->id,
                                            "order_id" => $transaction->order_id,
                                            "permitted" => "permitted"
                                        ];

                                        $path_name = "celcoin-permition-withdraw-".date("Y-m-d");

                                        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                                            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                                        }

                                        $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($return_info));

                                        \App\Jobs\PerformWithdrawalPaymentPIXCelcoinTRUE::dispatch($transaction->id,$bank_withdraw->id)->delay(now()->addSeconds('5'));

                                    }elseif($params['method'] == "pix" && $bank_withdraw->code == "588"){

                                        $return_info = [
                                            "bank_withdraw_code" => $bank_withdraw->code,
                                            "bank_id" => $bank_withdraw->id,
                                            "transaction_id" => $transaction->id,
                                            "order_id" => $transaction->order_id,
                                            "permitted" => "permitted"
                                        ];

                                        $path_name = "voluti-permition-withdraw-".date("Y-m-d");

                                        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                                            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                                        }

                                        $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($return_info));

                                        \App\Jobs\PerformWithdrawalPIXVoluti::dispatch($transaction->id,$bank_withdraw->id)->delay(now()->addSeconds('5'));

                                    }elseif($params['method'] == "pix" && $bank_withdraw->code == "222"){

                                        $return_info = [
                                            "bank_withdraw_code" => $bank_withdraw->code,
                                            "transaction_id" => $transaction->id,
                                            "order_id" => $transaction->order_id,
                                            "permitted" => "permitted"
                                        ];

                                        $path_name = "shipay-permition-withdraw-".date("Y-m-d");

                                        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                                            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                                        }

                                        $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($return_info));

                                        \App\Jobs\PerformWithdrawalPaymentPIX::dispatch($transaction->id)->delay(now()->addSeconds('5'));
                                    }elseif($params['method'] == "pix" && $bank_withdraw->code == "844"){

                                        $return_info = [
                                            "bank_withdraw_code" => $bank_withdraw->code,
                                            "bank_id" => $bank_withdraw->id,
                                            "transaction_id" => $transaction->id,
                                            "order_id" => $transaction->order_id,
                                            "permitted" => "permitted"
                                        ];

                                        $path_name = "hubapi-permition-withdraw-".date("Y-m-d");

                                        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                                            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                                        }

                                        $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($return_info));

                                        \App\Jobs\PerformWithdrawalPIXHUBAPIANYNEW::dispatch($transaction->id,$bank_withdraw->id)->delay(now()->addSeconds('5'));

                                    }elseif($params['method'] == "pix" && $bank_withdraw->code == "845"){

                                        $return_info = [
                                            "bank_withdraw_code" => $bank_withdraw->code,
                                            "bank_id" => $bank_withdraw->id,
                                            "transaction_id" => $transaction->id,
                                            "order_id" => $transaction->order_id,
                                            "permitted" => "permitted"
                                        ];

                                        $path_name = "volutinew-permition-withdraw-".date("Y-m-d");

                                        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                                            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                                        }

                                        $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($return_info));

                                        \App\Jobs\PerformWithdrawalPIXVolutiFILENEW::dispatch($transaction->id,$bank_withdraw->id)->delay(now()->addSeconds('5'));

                                    }elseif($params['method'] == "pix" && $bank_withdraw->code == "846"){

                                        $return_info = [
                                            "bank_withdraw_code" => $bank_withdraw->code,
                                            "bank_id" => $bank_withdraw->id,
                                            "transaction_id" => $transaction->id,
                                            "order_id" => $transaction->order_id,
                                            "permitted" => "permitted"
                                        ];

                                        $path_name = "luxtak-permition-withdraw-".date("Y-m-d");

                                        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                                            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                                        }

                                        $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($return_info));

                                        \App\Jobs\PerformWithdrawalPIXLuxTakNewsts::dispatch($transaction->id,$bank_withdraw->id)->delay(now()->addSeconds('5'));

                                    }else{

                                        $return_info = [
                                            "bank_withdraw_code" => $bank_withdraw->code,
                                            "transaction_id" => $transaction->id,
                                            "order_id" => $transaction->order_id,
                                            "permitted" => "not permitted"
                                        ];

                                        $path_name = "celcoin-permition-withdraw-".date("Y-m-d");

                                        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                                            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                                        }

                                        $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($return_info));

                                    }

                                }else{

                                    $return_info = [
                                        "bank_withdraw" => "not permition withdraw",
                                        "bank_id" => $bank_withdraw->id,
                                    ];

                                    $path_name = "bank-permition-withdraw-".date("Y-m-d");

                                    if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                                        mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                                    }

                                    $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($return_info));

                                }
                            }else{

                                $return_info = [
                                    "bank_withdraw" => "not exists",
                                    "client_id" => $transaction->client_id,
                                ];

                                $path_name = "celcoin-permition-withdraw-".date("Y-m-d");

                                if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                                    mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                                }

                                $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($return_info));

                            }

                        }else{

                            $return_info = [
                                "withdraw_permition" => "false",
                                "client_id" => $transaction->client_id,
                            ];

                            $path_name = "check-permition-withdraw-".date("Y-m-d");

                            if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                                mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                            }

                            $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($return_info));

                        }
                    }
                // }
            }
            // Success
            return response()->json($json_return,200);

        }catch(exception $e){
            DB::roolback();
        }
    }

    public function getDictPix($pix_key,$type_key){

        $data = [
            "type" => $type_key,
            "key" => $pix_key
        ];

        $authorization = env('TOKEN_HUB');

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.hubapis.com.br/api/v1/transactions/pix-key-info',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Authorization: Basic '.$authorization,
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function checkpixCelcoin($client_id,$transaction_id){

        $FunctionsAPIController = new FunctionsAPIController();
        $client = Clients::where("id","=",$client_id)->first();

        $client_id_celcoin = $client->bankWithdrawPix->client_id_celcoin;
        $client_secret_celcoin = $client->bankWithdrawPix->client_secret_celcoin;
        $access_token_celcoin = $client->bankWithdrawPix->access_token_celcoin;

        $params = [
            'client_id' => $client->id,
            'client_id_celcoin' => $client_id_celcoin,
            'client_secret_celcoin' => $client_secret_celcoin,
            'access_token_celcoin' => $access_token_celcoin
        ];

        $token_celcoin = json_decode($this->getTokenCELCOIN($params),true);

        $token = $token_celcoin['access_token'];
        $e2e_id = "";
        $url_send = "";

        if($transaction_id != ""){ $url_send = "https://apicorp.celcoin.com.br/pix/v1/payment/pi/status?transactionId=".$transaction_id; }
        if($e2e_id != ""){ $url_send = "https://apicorp.celcoin.com.br/pix/v1/payment/pi/status?endtoendId=".$e2e_id; }

        if($url_send == ""){

            return response()->json([
                "message" => "error"
            ]);

        }else{

            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => $url_send,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Authorization: Bearer '.$token
            ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            return $response;

        }

    }

    public function checkBalanceWithdraw($client_id,$amount_withdraw){

        // Get currency of Client
        $clients = Clients::where("id","=",$client_id)->first();
        $view_currency = $clients->currency;

        $today = date("Y-m-d 00:00:00");

        $start = date("Y-m-d 00:00:00");
        $total_available = Extract::where("client_id",$clients->id)->where("disponibilization_date","<=",$today)->sum("final_amount");

        // Select all withdraws pending
        $sql_all_withdraw_pending = Transactions::where("client_id","=",$client_id)->where("status","=","pending")->where("type_transaction","=","withdraw")->where("method_transaction","!=","bank_transfer")->sum('amount_solicitation');
        if(!empty($sql_all_withdraw_pending[0])){
            $total_withdraw_pending = $sql_all_withdraw_pending;
        }else{
            $total_withdraw_pending = 0;
        }

        if($total_available > 0){
            if(($total_available - $total_withdraw_pending) > 0){
                if((($total_available - $total_withdraw_pending) - $amount_withdraw) >= 0){
                    return array("message" => "success", "code" => "200");
                }else{
                    return array("message" => "Withdrawal amount greater than your balance available", "code" => "0443");
                }
            }else{
                return array("message" => "Insufficient balance available due to pending withdrawals", "code" => "0442");
            }
        }else{
            return array("message" => "Withdrawal not allowed due to insufficient balance available", "code" => "0441");
        }

    }

    public function consultIdReg($user_document){

        $paramters = "
        {
          'MerchantOrderId':'".$params['order_id']."',
          'Customer':
          {
            'Identity':'".$params['user_document']."',
            'Name':'".$params['user_name']."',
            'Email':'".$params['user_email']."',
            'Phone':'".$params['user_phone']."'
          },
          'Payment':
          {
              'Type':'CreditCard',
              'Amount':".$amount_clear.",
              'Provider':'Cielo',
              'Authenticate': ".$autenticate.",
              'ReturnUrl':'".$params['return_url']."',
              'Installments':1,
              'CreditCard':
              {
                'CardNumber':'".$params['card_number']."',
                'Holder':'".$params['card_holder']."',
                'ExpirationDate':'".$exp_date."',
                'SecurityCode':'".$params['card_cvv']."',
                'Brand':'".$params['card_brand']."'
              }
          },
          'Options':
          {
            'AntifraudEnabled':true
          }
        }
        ";

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://api.cieloecommerce.cielo.com.br/1/sales", // PROD
          //CURLOPT_URL => "https://apisandbox.cieloecommerce.cielo.com.br/1/sales", // SANDBOX
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $paramters,
          CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "MerchantId: ".$merchantId."",
            "MerchantKey: ".$merchantKey."",
            "Postman-Token: 6bac7494-f187-4a72-ad1a-941034fa7d50",
            "cache-control: no-cache"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

    }

    public function dia_semana($date){
        // Array com os dias da semana
        $diasemana = array('dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sab');

        // Varivel que recebe o dia da semana (0 = Domingo, 1 = Segunda ...)
        $diasemana_numero = date('w', strtotime($date));

        return $diasemana[$diasemana_numero];
    }

    // Check total deposit user daily
    public function total_deposit_daily($client_id,$user_id,$user_document,$method_transaction){

        // SUM TOTAL DEPOSIT DAILY
        $total_daily = Transactions::where("client_id","=",$client_id)
            ->where("user_id","=",$user_id)
            ->whereBetween("solicitation_date",[date("Y-m-d")." 00:00:00",date("Y-m-d H:i:s")])
            ->where("method_transaction","=",$method_transaction)
            ->get();

        return $total_day = $total_daily->sum("amount_solicitation");

    }

    // Check total deposit user daily
    public function qty_deposit_daily($client_id,$user_id,$user_document,$method_transaction){

        // SUM TOTAL DEPOSIT DAILY
        $total_daily = Transactions::where("client_id","=",$client_id)
            ->where("user_id","=",$user_id)
            ->whereBetween("solicitation_date",[date("Y-m-d")." 00:00:00",date("Y-m-d H:i:s")])
            ->where("method_transaction","=",$method_transaction)
            ->count();

        return $total_daily;

    }

    // Check total deposit user week
    public function qty_deposit_week($client_id,$user_id,$user_document,$method_transaction){


        $week_today = date("w");

        switch($week_today){
            case"0":
                $start_date = date("Y-m-d 00:00:00");
                $end_date = date("Y-m-d 23:59:59",strtotime("+6 days"));
            break;
            case"1":
                $start_date = date("Y-m-d 00:00:00",strtotime("-1 day"));
                $end_date = date("Y-m-d 23:59:59",strtotime("+5 days"));
            break;
            case"2":
                $start_date = date("Y-m-d 00:00:00",strtotime("-2 days"));
                $end_date = date("Y-m-d 23:59:59",strtotime("+4 days"));
            break;
            case"3":
                $start_date = date("Y-m-d 00:00:00",strtotime("-3 days"));
                $end_date = date("Y-m-d 23:59:59",strtotime("+3 days"));
            break;
            case"4":
                $start_date = date("Y-m-d 00:00:00",strtotime("-4 days"));
                $end_date = date("Y-m-d 23:59:59",strtotime("+2 days"));
            break;
            case"5":
                $start_date = date("Y-m-d 00:00:00",strtotime("-5 days"));
                $end_date = date("Y-m-d 23:59:59",strtotime("+1 days"));
            break;
            case"6":
                $start_date = date("Y-m-d 00:00:00",strtotime("-6 days"));
                $end_date = date("Y-m-d 23:59:59");
            break;
        }

        // SUM TOTAL DEPOSIT DAILY
        $total_week = Transactions::where("client_id","=",$client_id)
            ->where("user_id","=",$user_id)
            ->whereBetween("solicitation_date",[$start_date,$end_date])
            ->where("method_transaction","=",$method_transaction)
            ->count();

        return $total_week;

    }

    public function check_user_rules($client_id,$user_id,$user_document,$amount_solicitation,$method_payment){

        $clients = Clients::where("id","=",$client_id)->first();
        $have_limits = $clients->activate_limits;


        // $checkLimitsDetail = LimitDetailUser::where("client_id",$client_id)->where("user_id",$user_id)->first();
        // if(isset($checkLimitsDetail)){

        //     switch($method_payment){
        //         case"pix":
        //             if($amount_solicitation > $checkLimitsDetail->max_limit_transaction_pix){
        //                 // Amount request greater than requested
        //                 return array("message" => "Unable to perform transaction. Maximum limit per transaction R$ ".$this->doubletostr($checkLimitsDetail->max_limit_transaction_pix), "reason" => "Illegal Conditions", "code" => "0503");
        //                 exit();
        //             }

        //             $totalDepositpix = Transactions::where("client_id",$client_id)
        //                 ->where("user_id",$user_id)
        //                 ->where("type_transaction","deposit")
        //                 ->where("method_transaction","pix")
        //                 ->where("status","!=","canceled")
        //                 ->whereBetween("solicitation_date",[date("Y-m-d 00:00:00"),date("Y-m-d 23:59:59")])
        //                 ->sum("amount_solicitation");

        //             if(($totalDepositpix + $amount_solicitation) > $checkLimitsDetail->max_limit_day_pix){
        //                 // Amount request greater than requested
        //                 return array("message" => "Unable to perform transaction. Maximum limit per day R$ ".$this->doubletostr($checkLimitsDetail->max_limit_day_pix), "reason" => "Illegal Conditions", "code" => "0503");
        //                 exit();
        //             }

        //             return ["return" => "newRule"];

        //         break;
        //         case"invoice":
        //             if($amount_solicitation > $checkLimitsDetail->max_limit_transaction_invoice){
        //                 // 9274
        //                 return array("message" => "Unable to perform transaction. Maximum limit per transaction R$ ".$this->doubletostr($checkLimitsDetail->max_limit_transaction_invoice), "reason" => "Illegal Conditions", "code" => "0503");
        //                 exit();
        //             }

        //             $totalDepositinvoice = Transactions::where("client_id",$client_id)
        //                 ->where("user_id",$user_id)
        //                 ->where("type_transaction","deposit")
        //                 ->where("method_transaction","invoice")
        //                 ->where("status","!=","canceled")
        //                 ->whereBetween("solicitation_date",[date("Y-m-d 00:00:00"),date("Y-m-d 23:59:59")])
        //                 ->sum("amount_solicitation");

        //             if(($totalDepositinvoice + $amount_solicitation) > $checkLimitsDetail->max_limit_day_invoice){
        //                 // Amount request greater than requested
        //                 return array("message" => "Unable to perform transaction. Maximum limit per day R$ ".$this->doubletostr($checkLimitsDetail->max_limit_day_invoice), "reason" => "Illegal Conditions", "code" => "0503");
        //                 exit();
        //             }

        //             return ["return" => "newRule"];
        //         break;
        //         case"automatic_checking":
        //             if($amount_solicitation > $checkLimitsDetail->max_limit_transaction_shop){
        //                 // Order_id already exists
        //                 return array("message" => "Unable to perform transaction. Maximum limit per transaction R$ ".$this->doubletostr($checkLimitsDetail->max_limit_transaction_shop), "reason" => "Illegal Conditions", "code" => "0503");
        //                 exit();
        //             }

        //             $totalDepositshop = Transactions::where("client_id",$client_id)
        //                 ->where("user_id",$user_id)
        //                 ->where("type_transaction","deposit")
        //                 ->where("method_transaction","automatic_checking")
        //                 ->where("status","!=","canceled")
        //                 ->whereBetween("solicitation_date",[date("Y-m-d 00:00:00"),date("Y-m-d 23:59:59")])
        //                 ->sum("amount_solicitation");

        //             if(($totalDepositshop + $amount_solicitation) > $checkLimitsDetail->max_limit_day_shop){
        //                 // Amount request greater than requested
        //                 return array("message" => "Unable to perform transaction. Maximum limit per day R$ ".$this->doubletostr($checkLimitsDetail->max_limit_day_shop), "reason" => "Illegal Conditions", "code" => "0503");
        //                 exit();
        //             }

        //             return ["return" => "newRule"];
        //         break;
        //         case"credit_card":
        //             if($amount_solicitation > $checkLimitsDetail->max_limit_transaction_credit_card){
        //                 // Order_id already exists
        //                 return array("message" => "Unable to perform transaction. Maximum limit per transaction R$ ".$this->doubletostr($checkLimitsDetail->max_limit_transaction_credit_card), "reason" => "Illegal Conditions", "code" => "0503");
        //                 exit();
        //             }

        //             $totalDepositcredit_card = Transactions::where("client_id",$client_id)
        //                 ->where("user_id",$user_id)
        //                 ->where("type_transaction","deposit")
        //                 ->where("method_transaction","credit_card")
        //                 ->where("status","!=","canceled")
        //                 ->whereBetween("solicitation_date",[date("Y-m-d 00:00:00"),date("Y-m-d 23:59:59")])
        //                 ->sum("amount_solicitation");

        //             if(($totalDepositcredit_card + $amount_solicitation) > $checkLimitsDetail->max_limit_day_credit_card){
        //                 // Amount request greater than requested
        //                 return array("message" => "Unable to perform transaction. Maximum limit per day R$ ".$this->doubletostr($checkLimitsDetail->max_limit_day_credit_card), "reason" => "Illegal Conditions", "code" => "0503");
        //                 exit();
        //             }

        //             return ["return" => "newRule"];
        //         break;
        //     }

        // }

        if($have_limits === "yes"){

            // CHECK LIST VIP
            $vip = DB::table('white_list')->where("client_id","=",$client_id)->where("user_id","=",$user_id)->orWhere("user_document","=",$user_document)->first();

            if($vip){
                // IF USER IS VIP

                $taxes = $clients->tax;

                $max_deposit_invoice_vip = $taxes->max_boleto_vip;
                $max_deposit_shop_vip = $taxes->max_shop_vip;
                $max_deposit_cc_vip = $taxes->max_credit_card_vip;
                $max_deposit_debit_vip = $taxes->max_debit_card_vip;
                $max_deposit_ame_vip = $taxes->max_ame_vip;
                $max_deposit_pix_vip = $taxes->max_pix_vip;

                // LIMITS QTY
                $max_deposit_per_day = $taxes->cc_limit_request_day_vip;
                $max_deposit_per_week = $taxes->cc_limit_request_week_vip;

                switch($method_payment){
                    case"invoice": $max_deposit = $max_deposit_invoice_vip; break;
                    case"automatic_cheking": $max_deposit = $max_deposit_shop_vip; break;
                    case"credit_card": $max_deposit = $max_deposit_cc_vip; break;
                    case"debit_card": $max_deposit = $max_deposit_debit_vip; break;
                    case"ame_digital": $max_deposit = $max_deposit_ame_vip; break;
                    case"pix": $max_deposit = $max_deposit_pix_vip; break;
                }

                if($method_payment == "credit_card" || $method_payment == "debit_card"){

                    // LIMITS VIP QTY
                    $qty_deposits_today = $this->qty_deposit_daily($client_id,$user_id,$user_document,$method_payment);
                    $qty_deposits_week = $this->qty_deposit_week($client_id,$user_id,$user_document,$method_payment);

                    if($max_deposit_per_day < ($qty_deposits_today + 1)){

                        // Order_id already exists
                        return array("message" => "You have already requested your daily limit of ".$max_deposit_per_day." daily transactions. Wait until tomorrow to be able to carry out new transactions for this method.", "reason" => "Illegal Conditions", "code" => "0504");
                        exit();

                    }

                    if($max_deposit_per_week < ($qty_deposits_week + 1)){

                        // Order_id already exists
                        return array("message" => "You have already requested your limit of ".$max_deposit_per_week." weekly transactions. Wait until next week before you can make new transactions for this method.", "reason" => "Illegal Conditions", "code" => "0505");
                        exit();

                    }


                    // LIMITS VIP
                    $deposits_today = $this->total_deposit_daily($client_id,$user_id,$user_document,$method_payment);

                    $sub_rest = ($max_deposit - $deposits_today);
                    if($sub_rest <= 0){
                        $sub_rest = 0;
                    }

                    if($deposits_today > $max_deposit){

                        // Order_id already exists
                        // return array("message" => "We were unable to complete your transaction due to your daily limit of R$ ".$this->doubletostr($max_deposit).". You still have R$ ".$this->doubletostr($sub_rest)." available to deposit today", "reason" => "Illegal Conditions", "code" => "0503");
                        return array("message" => "We were unable to complete your transaction due to your dayli limit (".$method_payment." vip deposits_today > max_deposit)", "reason" => "Illegal Conditions", "code" => "0503");
                        exit();

                    }elseif(($deposits_today + $amount_solicitation) > $max_deposit){

                        // Order_id already exists
                        // return array("message" => "We were unable to complete your transaction due to your daily limit of R$ ".$this->doubletostr($max_deposit).". You still have R$ ".$this->doubletostr($sub_rest)." available to deposit today", "reason" => "Illegal Conditions", "code" => "0503");
                        return array("message" => "We were unable to complete your transaction due to your dayli limit (".$method_payment." vip (deposits_today + amount_solicitation) > max_deposit)", "reason" => "Illegal Conditions", "code" => "0503");
                        exit();

                    }else{

                        return ["return" => "vip"];

                    }

                }elseif($method_payment == "invoice" || $method_payment == "automatic_cheking" || $method_payment == "ame_digital" || $method_payment == "pix"){

                    // LIMITS VIP
                    $deposits_today = $this->total_deposit_daily($client_id,$user_id,$user_document,$method_payment);

                    $sub_rest = ($max_deposit - $deposits_today);
                    if($sub_rest <= 0){
                        $sub_rest = 0;
                    }

                    if($deposits_today > $max_deposit){

                        // Order_id already exists
                        // return array("message" => "We were unable to complete your transaction due to your daily limit of R$ ".$this->doubletostr($max_deposit).". You still have R$ ".$this->doubletostr($sub_rest)." available to deposit today", "reason" => "Illegal Conditions", "code" => "0503");
                        return array("message" => "We were unable to complete your transaction due to your dayli limit (".$method_payment." normal deposits_today > max_deposit)", "reason" => "Illegal Conditions", "code" => "0503");
                        exit();

                    }elseif(($deposits_today + $amount_solicitation) > $max_deposit){

                        // Order_id already exists
                        // return array("message" => "We were unable to complete your transaction due to your daily limit of R$ ".$this->doubletostr($max_deposit).". You still have R$ ".$this->doubletostr($sub_rest)." available to deposit today", "reason" => "Illegal Conditions", "code" => "0503");
                        return array("message" => "We were unable to complete your transaction due to your dayli limit (".$method_payment." normal (deposits_today + amount_solicitation) > max_deposit)", "reason" => "Illegal Conditions", "code" => "0503");
                        exit();

                    }else{

                        return ["return" => "vip"];

                    }

                }else{

                    // Order_id already exists
                    return array("message" => "Invalid method from deposit", "reason" => "Illegal Conditions");
                    exit();

                }

            }else{
                // IF USER NOT VIP


                // LIMITS NORMAL
                $taxes = $clients->tax;

                $max_deposit_invoice = $taxes->max_boleto;
                $max_deposit_shop = $taxes->max_shop;
                $max_deposit_cc = $taxes->max_credit_card;
                $max_deposit_debit = $taxes->max_debit_card;
                $max_deposit_ame = $taxes->max_ame;
                $max_deposit_pix = $taxes->max_pix;

                // LIMITS QTY
                $max_deposit_per_day = $taxes->cc_limit_request_day;
                $max_deposit_per_week = $taxes->cc_limit_request_week;

                switch($method_payment){
                    case"invoice": $max_deposit = $max_deposit_invoice; break;
                    case"automatic_cheking": $max_deposit = $max_deposit_shop; break;
                    case"credit_card": $max_deposit = $max_deposit_cc; break;
                    case"debit_card": $max_deposit = $max_deposit_debit; break;
                    case"ame_digital": $max_deposit = $max_deposit_ame; break;
                    case"pix": $max_deposit = $max_deposit_pix; break;
                }

                if($method_payment == "credit_card" || $method_payment == "debit_card"){

                    // LIMITS VIP QTY
                    $qty_deposits_today = $this->qty_deposit_daily($client_id,$user_id,$user_document,$method_payment);
                    $qty_deposits_week = $this->qty_deposit_week($client_id,$user_id,$user_document,$method_payment);

                    if($max_deposit_per_day < ($qty_deposits_today + 1)){

                        // Order_id already exists
                        return array("message" => "You have already requested your daily limit of ".$max_deposit_per_day." daily transactions. Wait until tomorrow to be able to carry out new transactions for method Credit Card.", "reason" => "Illegal Conditions", "code" => "0504");
                        exit();

                    }

                    if($max_deposit_per_week < ($qty_deposits_week + 1)){

                        // Order_id already exists
                        return array("message" => "You have already requested your limit of ".$max_deposit_per_week." weekly transactions. Wait until next week before you can make new transactions for method Credit Card.", "reason" => "Illegal Conditions", "code" => "0505");
                        exit();

                    }


                    // LIMITS VIP
                    $deposits_today = $this->total_deposit_daily($client_id,$user_id,$user_document,$method_payment);

                    $sub_rest = ($max_deposit - $deposits_today);
                    if($sub_rest <= 0){
                        $sub_rest = 0;
                    }

                    if($deposits_today > $max_deposit){

                        // Order_id already exists
                        // return array("message" => "We were unable to complete your transaction due to your daily limit of R$ ".$this->doubletostr($max_deposit).". You still have R$ ".$this->doubletostr($sub_rest)." available to deposit today", "reason" => "Illegal Conditions", "code" => "0503");
                        return array("message" => "We were unable to complete your transaction due to your dayli limit", "reason" => "Illegal Conditions", "code" => "0503");
                        exit();

                    }elseif(($deposits_today + $amount_solicitation) > $max_deposit){

                        // Order_id already exists
                        // return array("message" => "We were unable to complete your transaction due to your daily limit of R$ ".$this->doubletostr($max_deposit).". You still have R$ ".$this->doubletostr($sub_rest)." available to deposit today", "reason" => "Illegal Conditions", "code" => "0503");
                        return array("message" => "We were unable to complete your transaction due to your dayli limit", "reason" => "Illegal Conditions", "code" => "0503");
                        exit();

                    }else{

                        return ["return" => "ok"];

                    }

                }elseif($method_payment == "invoice" || $method_payment == "automatic_cheking" || $method_payment == "ame_digital" || $method_payment == "pix"){


                    // LIMITS VIP
                    $deposits_today = $this->total_deposit_daily($client_id,$user_id,$user_document,$method_payment);

                    $sub_rest = ($max_deposit - $deposits_today);
                    if($sub_rest <= 0){
                        $sub_rest = 0;
                    }

                    if($deposits_today > $max_deposit){

                        // Order_id already exists
                        // return array("message" => "We were unable to complete your transaction due to your daily limit of R$ ".$this->doubletostr($max_deposit).". You still have R$ ".$this->doubletostr($sub_rest)." available to deposit today", "reason" => "Illegal Conditions", "code" => "0503");
                        return array("message" => "We were unable to complete your transaction due to your dayli limit", "reason" => "Illegal Conditions", "code" => "0503");
                        exit();

                    }elseif(($deposits_today + $amount_solicitation) > $max_deposit){

                        // Order_id already exists
                        // return array("message" => "We were unable to complete your transaction due to your daily limit of R$ ".$this->doubletostr($max_deposit).". You still have R$ ".$this->doubletostr($sub_rest)." available to deposit today", "reason" => "Illegal Conditions", "code" => "0503");
                        return array("message" => "We were unable to complete your transaction due to your dayli limit", "reason" => "Illegal Conditions", "code" => "0503");
                        exit();

                    }else{

                        return ["return" => "ok"];

                    }

                }else{

                    // Order_id already exists
                    return array("message" => "Invalid method from deposit", "reason" => "Illegal Conditions");
                    exit();

                }

            }



        }else{

            $client = Clients::where("id","=",$client_id)->first();

            $taxes = $client->tax;

            $max_deposit_invoice = $taxes->max_boleto;
            $max_deposit_ted = $taxes->max_deposit;
            $max_deposit_cc = $taxes->max_cc;
            $max_deposit_debit = $taxes->max_debit_card;
            $max_deposit_ame = $taxes->max_ame;
            $max_deposit_pix = $taxes->max_pix;

            $min_deposit_invoice = $taxes->min_boleto;
            $min_deposit_ted = $taxes->min_deposit;
            $min_deposit_cc = $taxes->min_cc;
            $min_deposit_debit = $taxes->min_debit_card;
            $min_deposit_ame = $taxes->min_ame;
            $min_deposit_pix = $taxes->min_pix;

            switch($method_payment){
                case"invoice": $max_deposit = $max_deposit_invoice; $min_deposit = $min_deposit_invoice; break;
                case"ted": $max_deposit = $max_deposit_ted; $min_deposit = $min_deposit_ted; break;
                case"creditcard": $max_deposit = $max_deposit_cc; $min_deposit = $min_deposit_cc; break;
                case"debit_card": $max_deposit = $max_deposit_debit; $min_deposit = $min_deposit_debit; break;
                case"ame_digital": $max_deposit = $max_deposit_ame; $min_deposit = $min_deposit_ame; break;
                case"pix": $max_deposit = $max_deposit_pix; $min_deposit = $min_deposit_pix; break;
            }

            $amount = $amount_solicitation;
            $subs = substr($amount,-3,1);
            if($subs == "."){
                $amount = $amount;
                $amount_fiat_pix = number_format($amount,2,",","");
            }elseif($subs == ","){
                $amount = $this->strtodouble($amount);
                $amount_fiat_pix = number_format($amount,2,",","");
            }else{
                $amount = number_format($amount,2,".","");
                $amount_fiat_pix = number_format($amount,2,",","");
            }


            if($amount < $min_deposit){

                return array("message" => "Minimum amount R$ ".number_format($min_deposit,2,",","."), "reason" => "Illegal Conditions");
                exit();

            }

            if($amount > $max_deposit){

                return array("message" => "Maximum amount R$ ".number_format($max_deposit,2,",","."), "reason" => "Illegal Conditions");
                exit();

            }


            return ["return" => "ok"];



        }

    }

    public function create_order_ame($amount, $token_ame){

        $curl = curl_init();

        $params_post = array(
            "title" => "FastPayments",
            "description" => "Crédito em FastPayments",
            "amount"=> str_replace(".","",$amount),
            "type"=> "PAYMENT",
            "attributes" => array(
                "cashbackAmountValue"=> 0,
                "transactionChangedCallbackUrl"=> "https://admin.fastpayments.com.br/api/webhook_ame",
                "paymentOnce"=> "true",
                "items" => array("0" => array(
                    "description"=> "Crédito em FastPayments",
                    "quantity"=> 1,
                    "amount"=> str_replace(".","",$amount)
                    )
                )
            )
        );

        // $params_post = array(
        //     "title" => "",
        //     "softDescription" => "null",
        //     "description" => "Crédito em FastPayments",
        //     "amount"=> str_replace(".","",$amount),
        //     "type"=> "PAYMENT",
        //     "attributes" => array(
        //         "cashbackAmountValue"=> 0,
        //         "transactionChangedCallbackUrl"=> "https://admin.fastpayments.com.br/api/webhook_ame", // Prod
        //         // "transactionChangedCallbackUrl"=> "https://webhooksandbox.FastPayments.com/webhook/webhook_ame.php", // SandBox
        //         "items" => array("0" => array(
        //             "description"=> "Crédito em FastPayments",
        //             "quantity"=> 1,
        //             "amount"=> str_replace(".","",$amount)
        //             )
        //         ),
        //         "customPayload" => array(
        //             "ShippingValue" => "0",
        //             "shippingAddress" => array(
        //                "shippingAddress" => array(
        //                   "country" => "BRA",
        //                   "number" => 1767,
        //                   "city" => "Sao Paulo",
        //                   "street" => "Alameda Santos",
        //                   "postalCode" => "01419100",
        //                   "neighborhood" => "Cerqueira Cesar",
        //                   "state" => "SP"
        //                 )
        //             ),
        //             "billingAddress" => array(
        //                "billingAddress" => array(
        //                   "country" => "BRA",
        //                   "number" => 1767,
        //                   "city" => "Sao Paulo",
        //                   "street" => "Alameda Santos",
        //                   "postalCode" => "01419100",
        //                   "neighborhood" => "Cerqueira Cesar",
        //                   "state" => "SP"
        //                )
        //             ),
        //          ),
        //         "paymentOnce"=> "true",
        //     )
        // );

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://api.amedigital.com/api/orders", // PROD
        //   CURLOPT_URL => "https://api.hml.amedigital.com/api/orders", // SANDBOX
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => json_encode($params_post,true),
          CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer ".$token_ame,
            "Content-Type: application/json"
          ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;


    }

    public function capture_order_ame($secundary_id_ame, $token_ame){

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.amedigital.com/api/wallet/user/payments/".$secundary_id_ame."/capture",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer ".$token_ame,
        "Content-Type: application/json",
        "Content-Length: 0"
        ),
        ));

        $response = curl_exec($curl);
        $http_status  = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        return $response;

    }

    public function get_token_ame($id_bank){

        $bank_token = Banks::where("id","=",$id_bank)->first();

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.hml.amedigital.com/api/auth/oauth/token",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "grant_type=client_credentials",
        CURLOPT_HTTPHEADER => array(
            "Authorization: Basic ".$bank_token->userpass_ame,
            "Content-Type: application/x-www-form-urlencoded"
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $resp = json_decode($response,true);

        $token = "";
        if(isset($resp['access_token'])){
            $token = $resp['access_token'];
        }

        DB::beginTransaction();
        try{

            $bank_token->update([
                "token_ame" => $token
            ]);

            DB::commit();

            $return = array("token_ame" => $token);
        }catch(Exception $e){
            DB::rollback();

            $return = array("token_ame" => "error");
        }

        return response()->json($return);

    }

    public function register_transaction_ame($params = array()){
        $dados = "";
        $payment_id = "";
        $date = date("Y-m-d H:i:s");

        // GET ALL CLIENT
        $clients = Clients::where("id","=",$params['client_id'])->first();
        $days_safe_shop = $clients->days_safe_shop;

        // GET BANK DATA
        $bank = $clients->bankAme;

        $user_data = array(
            "name" => $params['user_name'],
            "document" => $params['user_document'],
            "bank_name" => $bank->name,
            "holder" => $bank->holder,
            "agency" => $bank->agency,
            "account_number" => $bank->account,
            "operation_bank" => $bank->type_account,
            "user_id" => $params['user_id'],
            "client_id" => $params['client_id'],
            "address" => $this->trata_unicode($params['user_address']),
            "district" => $this->trata_unicode($params['user_address_district']),
            "city" => $this->trata_unicode($params['user_address_city']),
            "uf" => $params['user_address_state'],
            "cep" => $params['user_address_zipcode']
        );

        $user_account_data = base64_encode(json_encode($user_data));


        // Taxas
        $tax = $clients->tax;

        $percent_fee = ($params['amount'] * ($tax->ame_percent / 100));
        $fixed_fee = $tax->ame_absolute;
        $comission = ($percent_fee + $fixed_fee);

        DB::beginTransaction();
        try {

            // Insert Transaction
            $transaction = Transactions::create([
                "solicitation_date" => $date,
                "final_date" => $date,
                "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_shop." days")),
                "code" => $params['pedido'],
                "client_id" => $params['client_id'],
                "order_id" => $params['order_id'],
                "user_id" => $params['user_id'],
                "user_document" => $params['user_document'],
                "user_account_data" => $user_account_data,
                "user_name" => $params['user_name'],
                "id_bank" => $clients->bankAme->id,
                "type_transaction" => 'deposit',
                "method_transaction" => 'ame_digital',
                "amount_solicitation" => $params['amount'],
                "final_amount" => $params['amount'],
                "percent_fee" => $percent_fee,
                "fixed_fee" => $fixed_fee,
                "comission" => $comission,
                "status" => 'pending',
                "data_bank" => $dados,
                "payment_id" => $payment_id,
                "code_bank" => "145",
                "link_callback_bank" => $params['link_callback_bank'],
                "url_retorna" => $params['return_url'],
                "deep_link" => $params['deep_link'],
            ]);

            DB::commit();

            $json_return = array(
                "order_id" => $params['order_id'],
                "solicitation_date" => $date,
                "due_date" => $this->datetostr(date("Y-m-d",strtotime($date."+ ".$days_safe_shop." days"))),
                "code_identify" => $params['pedido'],
                "amount" => $params['amount'],
                "fees" => $comission,
                "status" => "pending",
                "qrcode" => $params['link_callback_bank'],
                "deepLink" => $params['deep_link'],
            );

            // Success
            return response()->json($json_return,200);

        }catch(exception $e){
            DB::roolback();
        }
    }


    public function api_request_bradesco($PaymentId, $merchantId, $merchantKey){
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://apiquery.cieloecommerce.cielo.com.br/1/sales/".$PaymentId, // PROD
        //CURLOPT_URL => "https://apiquerysandbox.cieloecommerce.cielo.com.br/1/sales/".$PaymentId, // HOMOLOG
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "MerchantId: ".$merchantId."",
            "MerchantKey: ".$merchantKey.""
        ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if($err){
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }

    }

    public function api_request_bradesco_captura($PaymentId, $merchantId, $merchantKey){
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.cieloecommerce.cielo.com.br/1/sales/".$PaymentId, // PROD
        //CURLOPT_URL => "https://apisandbox.cieloecommerce.cielo.com.br/1/sales/".$PaymentId, // PROD
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "MerchantId: ".$merchantId."",
            "MerchantKey: ".$merchantKey."",
            "Postman-Token: 6bac7494-f187-4a72-ad1a-941034fa7d50",
            "cache-control: no-cache"
        ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if($err){
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }

    }

    public function getTokenCCFacilPay($params = array(),$hashFacilPay){
        $curl = curl_init();

        curl_setopt_array($curl, array(
        // CURLOPT_URL => "https://apiacao.facilpay.com.br:8443/GatewayConnector/api/createToken",
        CURLOPT_URL => "https://186.202.37.236:8443/GatewayConnector/api/createToken",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYHOST => 0,
	    CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($params,true),
        CURLOPT_HTTPHEADER => array(
            "X-JCNGatewayConnector-Source-Product: PAYMENT",
            "X-JCNGatewayConnector-Source-Channel: WS",
            "X-JCNGatewayConnector-Source-Hash: ".$hashFacilPay,
            "X-JCNGatewayConnector-Source-Service: GTW",
            "Content-Type: application/json"
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    public function createPaymentFacilPay($params = array(),$tokenCC,$hashFacilPay){

        $date_register = date("Y-m-d H:i:s");

        $client = Clients::where("id",$params['client_id'])->first();
        $bank = $client->bankCreditCard;

        $data = array(
            "cardToken" => $tokenCC,
            "requestValue" => $params['amount'],
            "ruleCreditAnalysis" => $client->risk,
            "idCustomerTransaction" => $params['order_id'],
            "cvv" => $params['card_cvv'],
            "numberOfTimes" => 1,
            "CreditAnalysisContainer" => array(
                "sessionID" => 0,
                "Consumer" => array(
                    "idDocument" => $params['user_document'],
                    "name" => $params['user_name'],
                    "email" => $params['user_email'],
                    "phoneNumber" => $params['user_phone'],
                    "registration" => $date_register
                ),
                "Address" => array(
                    "street" => $params['user_address'],
                    "number" => $params['user_address_number'],
                    "neighborhood" => $params['user_address_district'],
                    "city" => $params['user_address_city'],
                    "state" => $params['user_address_state'],
                    "stateAcronym" => $params['user_address_state'],
                    "zipCode" => $params['user_address_zipcode'],
                    "country" => "Brasil"
                ),
                "Product" => array(
                    "itemName" => $bank->name,
                    "value" => str_replace(".","",$params['amount']),
                    "category" => "Credit",
                    "quantity" => 1
                )
            )
        );

        $curl = curl_init();

        curl_setopt_array($curl, array(
        // CURLOPT_URL => "https://apiacao.facilpay.com.br:8443/GatewayConnector/api/createPayment",
        CURLOPT_URL => "https://186.202.37.236:8443/GatewayConnector/api/createPayment",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYHOST => 0,
	    CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($data,true),
        CURLOPT_HTTPHEADER => array(
            "X-JCNGatewayConnector-Source-Product: PAYMENT",
            "X-JCNGatewayConnector-Source-Channel: WS",
            "X-JCNGatewayConnector-Source-Hash: ".$hashFacilPay,
            "X-JCNGatewayConnector-Source-Service: GTW",
            "Content-Type: application/json"
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $this->registerRecivedsRequests("/var/www/html/nexapay/logs/response-create-facilpay-creditcard.txt",$response);

        return $response;
    }

    public function createFacilPayCC($params = array(),$hashFacilPay){

        switch($params['card_brand']){
            case"master": $codeBrand = "1"; break;
            case"visa": $codeBrand = "3"; break;
            case"elo": $codeBrand = "10"; break;
            default: $codeBrand = "error";
        }

        if($codeBrand == "error"){
            // Error, Brand not found
            $json_return = array("message" => "Incorrect brand for operation", "reason" => "Illegal Conditions");
            return response()->json($json_return,401);
            exit();
        }

        $card = array(
            "cardNumber" => $params['card_number'],
            "cardholderName" => $params['card_holder'],
            "cardExpirationDate" => substr($params['card_expired'],2,4).substr($params['card_expired'],0,2),
            "brand" => $codeBrand,
            "idCustomerCard" => $params['user_id']."-".$params['pedido'],
        );

        $token = json_decode($this->getTokenCCFacilPay($card,$hashFacilPay),true);

        if($token['returnCode'] == "0"){
            // Error, Brand not found
            $json_return = array("message" => "Error on generate token credit card", "reason" => "Illegal Conditions", "return" => $token);
            return response()->json($json_return,401);
            exit();
        }

        $createPayment = json_decode($this->createPaymentFacilPay($params,$token['cardToken'],$hashFacilPay),true);

        if(!isset($createPayment['tid'])){
            // Error, Brand not found
            $json_return = array("message" => "Error on create payment credit card", "reason" => "Illegal Conditions");
            return response()->json($json_return,401);
            exit();
        }

        $dados = "";
        $payment_id = $createPayment['tid'];
        $date = date("Y-m-d H:i:s");
        $client = Clients::where("id","=",$params['client_id'])->first();
        $bank = $client->bankCreditCard;
        $days_safe_credit_card = $client->days_safe_credit_card;

        $user_data = array(
            "name" => $params['user_name'],
            "document" => $params['user_document'],
            "bank_name" => $bank->name,
            "holder" => $bank->holder,
            "agency" => $bank->agency,
            "account_number" => $bank->account,
            "operation_bank" => $bank->type_account,
            "user_id" => $params['user_id'],
            "client_id" => $params['client_id'],
            "address" => $this->trata_unicode($params['user_address']),
            "district" => $this->trata_unicode($params['user_address_district']),
            "city" => $this->trata_unicode($params['user_address_city']),
            "uf" => $params['user_address_state'],
            "cep" => $params['user_address_zipcode']
        );

        $user_account_data = base64_encode(json_encode($user_data));

        // Calulo Taxas //
        $cot_ar = $this->get_cotacao_dolar($params['client_id'],"deposit");
        $cotacao_dolar_markup = $cot_ar['markup'];
        $cotacao_dolar = $cot_ar['quote'];
        $spread_deposit = $cot_ar['spread'];

        // Taxas
        $tax = $client->tax;

        if($client->currency == "brl"){

            $cotacao_dolar_markup = "1";
            $cotacao_dolar = "1";
            $spread_deposit = "0";

            $final_amount = $params['amount'];
            $percent_fee = ($final_amount * ($tax->credit_card_percent / 100));
            $fixed_fee = $tax->credit_card_absolute;
            if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
            $comission = ($percent_fee + $fixed_fee);
            if($comission < $tax->min_fee_credit_card){ $comission = $tax->min_fee_credit_card; $min_fee = $tax->min_fee_credit_card; }else{ $min_fee = "NULL"; }

        }elseif($client->currency == "usd"){

            $final_amount = number_format(($params['amount'] / $cotacao_dolar_markup),6,".","");
            $fixed_fee = number_format(($tax->credit_card_absolute),6,".","");
            $percent_fee = number_format(($final_amount * ($tax->credit_card_percent / 100)),6,".","");
            if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
            $comission = ($percent_fee + $fixed_fee);
            if($comission < $tax->min_fee_credit_card){ $comission = $tax->min_fee_credit_card; $min_fee = $tax->min_fee_credit_card; }else{ $min_fee = "NULL"; }

        }

        $number_card_bd = substr($params['card_number'],0,6)."******".substr($params['card_number'],12,4);
        $brand = strtolower($params['card_brand']);

        $exp_date = substr($params['card_expired'],0,2)."/".substr($params['card_expired'],2,4);
        $amount_clear = str_replace(",","",$params['amount']);
        $amount_clear = str_replace(".","",$amount_clear);

        $autenticate = "false";

        $link_callback_bank = $params['return_url'];

        DB::beginTransaction();
        try{

            // Insert Transaction
            $transaction = Transactions::create([
                "solicitation_date" => $date,
                "final_date" => $date,
                "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_credit_card." days")),
                "code" => $params['pedido'],
                "client_id" => $params['client_id'],
                "order_id" => $params['order_id'],
                "user_id" => $params['user_id'],
                "user_document" => $params['user_document'],
                "user_account_data" => $user_account_data,
                "user_name" => $params['user_name'],
                "id_bank" => $bank->id,
                "type_transaction" => 'deposit',
                "method_transaction" => 'credit_card',
                "amount_solicitation" => $params['amount'],
                "final_amount" => $final_amount,
                "percent_fee" => $percent_fee,
                "fixed_fee" => $fixed_fee,
                "comission" => $comission,
                "min_fee" => $min_fee,
                "status" => 'pending',
                "payment_id" => $payment_id,
                "dados" => $dados,
                "link_callback_bank" => $link_callback_bank,
                "url_retorna" => $link_callback_bank,
                "code_bank" => $bank->code,
                "number_card" => $number_card_bd,
                "brand" => $brand,
                "quote" => $cotacao_dolar,
                "percent_markup" => $spread_deposit,
                "quote_markup" => $cotacao_dolar_markup,
            ]);

            DB::commit();

            $json_return = array(
                "order_id" => $params['order_id'],
                "solicitation_date" => $date,
                "due_date" => $this->datetostr(date("Y-m-d",strtotime($date."+ ".$days_safe_credit_card." days"))),
                "code_identify" => $params['pedido'],
                "amount" => $params['amount'],
                "fees" => $comission,
                "status" => "pending",
                "link_shop" => $params['return_url']
            );

            // Success
            return response()->json($json_return,200);

        }catch(exception $e){
            DB::rollback();
        }

    }

    public function createFacilPayCCV2($params = array(),$facilpay_client_id,$facilpay_client_secret,$facilpay_ec_id){

        switch($params['card_brand']){
            case"master": $codeBrand = "MASTERCARD"; break;
            case"visa": $codeBrand = "VISA"; break;
            case"elo": $codeBrand = "ELO"; break;
            default: $codeBrand = "error";
        }

        if($codeBrand == "error"){
            // Error, Brand not found
            $json_return = array("message" => "Incorrect brand for operation", "reason" => "Illegal Conditions");
            return response()->json($json_return,401);
            exit();
        }

        $client = Clients::where("id","=",$params['client_id'])->first();
        $bank = $client->bankCreditCard;

        $token = json_decode($this->getTokenCCFacilPayV2($facilpay_client_id,$facilpay_client_secret),true);

        if(!isset($token['access_token'])){

            // Error, Brand not found
            $json_return = array("message" => "Error on generate token credit card", "reason" => "Illegal Conditions", "return" => $token);
            return response()->json($json_return,401);
            exit();

        }else{

            $createPayment = json_decode($this->createPaymentFacilPayV2($params,$token['access_token'],$facilpay_ec_id),true);

            if(isset($createPayment['error'])){
                // Error, Brand not found
                $json_return = array("message" => "Error on create payment credit card", "reason" => "Illegal Conditions", "return"=> json_encode($createPayment,true));
                return response()->json($json_return,401);
                exit();
            }else{

                $data_bank = json_encode($createPayment,true);
                $payment_id = $createPayment['idTransacao'];
                $date = date("Y-m-d H:i:s");
                $client = Clients::where("id","=",$params['client_id'])->first();
                $bank = $client->bankCreditCard;
                $days_safe_credit_card = $client->days_safe_credit_card;

                $user_data = array(
                    "name" => $params['user_name'],
                    "document" => $params['user_document'],
                    "bank_name" => $bank->name,
                    "holder" => $bank->holder,
                    "agency" => $bank->agency,
                    "account_number" => $bank->account,
                    "operation_bank" => $bank->type_account,
                    "user_id" => $params['user_id'],
                    "client_id" => $params['client_id'],
                    "address" => $this->trata_unicode($params['user_address']),
                    "district" => $this->trata_unicode($params['user_address_district']),
                    "city" => $this->trata_unicode($params['user_address_city']),
                    "uf" => $params['user_address_state'],
                    "cep" => $params['user_address_zipcode'],
                    "user_phone" => $params['user_phone'],
                    "user_email" => $params['user_email'],
                    "birth_date" => $params['birth_date'],
                    "ip" => $params['ip'],
                    "card_number" => $params['card_number'],
                    "card_expired" => $params['card_expired']
                );

                $user_account_data = base64_encode(json_encode($user_data));

                // Calulo Taxas //
                $cot_ar = $this->get_cotacao_dolar($params['client_id'],"deposit");
                $cotacao_dolar_markup = $cot_ar['markup'];
                $cotacao_dolar = $cot_ar['quote'];
                $spread_deposit = $cot_ar['spread'];

                // Taxas
                $tax = $client->tax;

                if($client->currency == "brl"){

                    $cotacao_dolar_markup = "1";
                    $cotacao_dolar = "1";
                    $spread_deposit = "0";

                    $final_amount = $params['amount'];
                    $percent_fee = ($final_amount * ($tax->credit_card_percent / 100));
                    $fixed_fee = $tax->credit_card_absolute;
                    if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                    $comission = ($percent_fee + $fixed_fee);
                    if($comission < $tax->min_fee_credit_card){ $comission = $tax->min_fee_credit_card; $min_fee = $tax->min_fee_credit_card; }else{ $min_fee = "NULL"; }

                }elseif($client->currency == "usd"){

                    $final_amount = number_format(($params['amount'] / $cotacao_dolar_markup),6,".","");
                    $fixed_fee = number_format(($tax->credit_card_absolute),6,".","");
                    $percent_fee = number_format(($final_amount * ($tax->credit_card_percent / 100)),6,".","");
                    if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                    $comission = ($percent_fee + $fixed_fee);
                    if($comission < $tax->min_fee_credit_card){ $comission = $tax->min_fee_credit_card; $min_fee = $tax->min_fee_credit_card; }else{ $min_fee = "NULL"; }

                }

                $number_card_bd = substr($params['card_number'],0,6)."******".substr($params['card_number'],12,4);
                $brand = strtolower($params['card_brand']);

                $exp_date = substr($params['card_expired'],0,2)."/".substr($params['card_expired'],2,4);
                $amount_clear = str_replace(",","",$params['amount']);
                $amount_clear = str_replace(".","",$amount_clear);

                $autenticate = "false";

                $link_callback_bank = $params['return_url'];

                DB::beginTransaction();
                try{

                    // Insert Transaction
                    $transaction = Transactions::create([
                        "solicitation_date" => $date,
                        "final_date" => $date,
                        "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_credit_card." days")),
                        "code" => $params['pedido'],
                        "client_id" => $params['client_id'],
                        "order_id" => $params['order_id'],
                        "user_id" => $params['user_id'],
                        "user_document" => $params['user_document'],
                        "user_account_data" => $user_account_data,
                        "user_name" => $params['user_name'],
                        "id_bank" => $bank->id,
                        "type_transaction" => 'deposit',
                        "method_transaction" => 'credit_card',
                        "amount_solicitation" => $params['amount'],
                        "final_amount" => $final_amount,
                        "percent_fee" => $percent_fee,
                        "fixed_fee" => $fixed_fee,
                        "comission" => $comission,
                        "min_fee" => $min_fee,
                        "status" => 'pending',
                        "payment_id" => $payment_id,
                        "data_bank" => $data_bank,
                        "link_callback_bank" => $link_callback_bank,
                        "url_retorna" => $link_callback_bank,
                        "code_bank" => $bank->code,
                        "number_card" => $number_card_bd,
                        "brand" => $brand,
                        "quote" => $cotacao_dolar,
                        "percent_markup" => $spread_deposit,
                        "quote_markup" => $cotacao_dolar_markup,
                    ]);

                    DB::commit();

                    $json_return = array(
                        "order_id" => $params['order_id'],
                        "solicitation_date" => $date,
                        "due_date" => $this->datetostr(date("Y-m-d",strtotime($date."+ ".$days_safe_credit_card." days"))),
                        "code_identify" => $params['pedido'],
                        "amount" => $params['amount'],
                        "fees" => $comission,
                        "status" => "pending",
                        "link_shop" => $params['return_url']
                    );

                    \App\Jobs\CaptureFacilpayV2::dispatch($transaction->id)->delay(now()->addSeconds('5'));

                    // Success
                    return $json_return;

                }catch(exception $e){
                    DB::rollback();
                }

            }



        }

    }

    public function getTokenCCFacilPayV2($facilpay_client_id,$facilpay_client_secret){

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://oauth.facilpay.com.br:8088/oauth/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials&scope=web',
        CURLOPT_HTTPHEADER => array(
        'Authorization: Basic '.base64_encode($facilpay_client_id.":".$facilpay_client_secret),
        'Content-Type: application/x-www-form-urlencoded'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function createPaymentFacilPayV2($params = array(),$access_token,$facilpay_ec_id){

        switch($params['card_brand']){
            case"master": $card_brand = "MASTERCARD"; break;
            case"visa": $card_brand = "VISA"; break;
            case"elo": $card_brand = "ELO"; break;
        }

        $data = [
            "analiseDeRisco"=> [
                "confirmacaoTardia" => false,
                "deveUtilizar" => true,
                "status_notification_url" => 0,
                "subacquirer_merchant" => [
                    "address" => "string",
                    "city" =>  "string",
                    "country" => "string",
                    "state" => "string",
                    "zip_code" => "string"
                ],
                "urlNotificacao" => "https://webhook.site/011d5c4e-779b-406b-adc0-4542c524ff9d"
            ],
            "ecId" => $facilpay_ec_id,
            "numeroPedido" => $params['pedido'],
            "parcelas" => [
                array(
                    "cartaoUtilizado" => [
                        "numero" => $params['card_number'],
                        "bandeira" => $card_brand,
                        "nomeDoTitular" => $params['card_holder'],
                        "dataDeVencimento" => "31/".substr($params['card_expired'],0,2)."/".substr($params['card_expired'],2,4),
                        "cvv" => $params['card_cvv'],
                        "portadorTitular" => [
                            "nome" => $params['user_name'],
                            "cpf" => $params['user_document'],
                            "dataNascimento" => date("d/m/Y",strtotime($params['birth_date'])),
                            "contato" => [
                                "telefone" => $params['user_phone'],
                                "celular" => $params['user_phone'],
                                "email" => $params['user_email'],
                                "pessoa" => $params['user_name']
                            ],
                            "endereco" => [
                                "cep" => $params['user_address_zipcode'],
                                "logradouro" => $params['user_address'],
                                "numero" => $params['user_address_number'],
                                "bairro" => $params['user_address_district'],
                                "cidade" => $params['user_address_city'],
                                "estado" => $params['user_address_state']
                            ]
                        ]
                    ],
                    "quantidadeParcelas" => 1,
                    "valorParcela" => floatval($params['amount']),
                )
            ],
            "quantidadeCartoes" => 1,
            "recorrencia" => [
                "intervalo" => 1,
                "intervaloNovasTentativas" => 1,
                "quantidadeMaximaNovasTentativas" => 1,
                "tipoIntervalo" => "MENSAL",
                "tipoIntervaloNovasTentativas" => "DIARIO"
            ],
            "sequencialPedido" => 1,
            "valorTotal" => floatval($params['amount'])
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://payment.facilpay.com.br:8081/pagamentos',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data,true),
        CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'Authorization: Bearer '.$access_token
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }


    public function createPagseguroCC($params = array(),$email,$token){

        $FunctionsAPIController = new FunctionsAPIController();

        $session = json_decode($this->generateTokenPagseguro($email,$token),true)['id'];

        $token_card = json_decode($this->generateTokenCCPagseguro($params,$session),true)['token'];

        $createPayment = json_decode($this->createPaymentPagseguro($params,$email,$token,$session,$token_card),true);

        if(isset($createPayment['code'])){

            $dados = "";
            $data_bank = json_encode($createPayment);
            $payment_id = $createPayment['code'];
            $date = date("Y-m-d H:i:s");
            $client = Clients::where("id","=",$params['client_id'])->first();
            $bank = $client->bankCreditCard;
            $days_safe_credit_card = $client->days_safe_credit_card;

            $user_data = array(
                "name" => $params['user_name'],
                "document" => $params['user_document'],
                "bank_name" => $bank->name,
                "holder" => $bank->holder,
                "agency" => $bank->agency,
                "account_number" => $bank->account,
                "operation_bank" => $bank->type_account,
                "user_id" => $params['user_id'],
                "client_id" => $params['client_id'],
                "address" => $this->trata_unicode($params['user_address']),
                "district" => $this->trata_unicode($params['user_address_district']),
                "city" => $this->trata_unicode($params['user_address_city']),
                "uf" => $params['user_address_state'],
                "cep" => $params['user_address_zipcode']
            );

            $user_account_data = base64_encode(json_encode($user_data));

            // Calulo Taxas //
            $cot_ar = $this->get_cotacao_dolar($params['client_id'],"deposit");
            $cotacao_dolar_markup = $cot_ar['markup'];
            $cotacao_dolar = $cot_ar['quote'];
            $spread_deposit = $cot_ar['spread'];

            // Taxas
            $tax = $client->tax;

            if($client->currency == "brl"){

                $cotacao_dolar_markup = "1";
                $cotacao_dolar = "1";
                $spread_deposit = "0";

                $final_amount = $params['amount'];
                $percent_fee = ($final_amount * ($tax->credit_card_percent / 100));
                $fixed_fee = $tax->credit_card_absolute;
                if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                $comission = ($percent_fee + $fixed_fee);
                if($comission < $tax->min_fee_credit_card){ $comission = $tax->min_fee_credit_card; $min_fee = $tax->min_fee_credit_card; }else{ $min_fee = "NULL"; }

            }elseif($client->currency == "usd"){

                $final_amount = number_format(($params['amount'] / $cotacao_dolar_markup),6,".","");
                $fixed_fee = number_format(($tax->credit_card_absolute),6,".","");
                $percent_fee = number_format(($final_amount * ($tax->credit_card_percent / 100)),6,".","");
                if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                $comission = ($percent_fee + $fixed_fee);
                if($comission < $tax->min_fee_credit_card){ $comission = $tax->min_fee_credit_card; $min_fee = $tax->min_fee_credit_card; }else{ $min_fee = "NULL"; }

            }

            // $number_card_bd = "";
            // $brand = $params['card_brand'];
            $number_card_bd = substr($params['card_number'],0,6)."******".substr($params['card_number'],12,4);
            $brand = strtolower($params['card_brand']);

            $exp_date = substr($params['card_expired'],0,2)."/".substr($params['card_expired'],2,4);
            $amount_clear = str_replace(",","",$params['amount']);
            $amount_clear = str_replace(".","",$amount_clear);

            $autenticate = "false";

            $link_callback_bank = "";

            DB::beginTransaction();
            try{

                // Insert Transaction
                $transaction = Transactions::create([
                    "solicitation_date" => $date,
                    "final_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_credit_card." days")),
                    "code" => $params['pedido'],
                    "client_id" => $params['client_id'],
                    "order_id" => $params['order_id'],
                    "user_id" => $params['user_id'],
                    "user_document" => $params['user_document'],
                    "user_account_data" => $user_account_data,
                    "user_name" => $params['user_name'],
                    "id_bank" => $bank->id,
                    "type_transaction" => 'deposit',
                    "method_transaction" => 'credit_card',
                    "amount_solicitation" => $params['amount'],
                    "final_amount" => $final_amount,
                    "percent_fee" => $percent_fee,
                    "fixed_fee" => $fixed_fee,
                    "comission" => $comission,
                    "min_fee" => $min_fee,
                    "status" => 'pending',
                    "payment_id" => $payment_id,
                    "dados" => $dados,
                    "data_bank" => $data_bank,
                    "link_callback_bank" => $link_callback_bank,
                    "url_retorna" => $link_callback_bank,
                    "code_bank" => $bank->code,
                    "number_card" => $number_card_bd,
                    "brand" => $brand,
                    "quote" => $cotacao_dolar,
                    "percent_markup" => $spread_deposit,
                    "quote_markup" => $cotacao_dolar_markup,
                ]);

                DB::commit();

                $json_return = array(
                    "order_id" => $params['order_id'],
                    "solicitation_date" => $date,
                    "due_date" => $this->datetostr(date("Y-m-d",strtotime($date."+ ".$days_safe_credit_card." days"))),
                    "code_identify" => $params['pedido'],
                    "amount" => $params['amount'],
                    "fees" => $comission,
                    "status" => "pending",
                    "link_shop" => ""
                );

                // Success
                return response()->json($json_return,200);

            }catch(exception $e){
                DB::rollback();
            }

        }else{
            // Error, Brand not found
            $json_return = array("message" => "Error on create credit card transaction", "reason" => "Illegal Conditions", "code" => $createPayment['error']['code'], "return" => $createPayment['error']['message']);
            return response()->json($json_return,401);
            exit();

        }

    }

    // Generate Transaction Credit Card Pagseguro
    public function createPaymentPagseguro($params = array(),$email_pagseguro,$token_pagseguro,$session,$token_card){

        $curl = curl_init();

        $client = Clients::where("id","=",$params['client_id'])->first();
        $bank = $client->bankCreditCard;

        $phone = $params['user_phone'];
        $birth_date = date("d/m/Y",strtotime($params['birth_date']));

        if(strlen($phone) > 11){
            $pn = substr($phone,-11);
            $area = substr($pn,2,2);
            $number_phone = substr($pn,2,9);
        }elseif(strlen($phone) == 11){
            $area = substr($phone,0,2);
            $number_phone = substr($phone,2,9);
        }else{
            $area = "11";
            $number_phone = $phone;
        }

        $data = array(
            "email" => $email_pagseguro,
            "token" => $token_pagseguro,
            "paymentMode" => "default",
            "paymentMethod" => "creditCard",
            "receiverEmail" => $email_pagseguro,
            "currency" => "BRL",
            "extraAmount" => "0.00",
            "itemId1" => $params['pedido'],
            "itemDescription1" => "Credito em ".$bank->bank_name,
            "itemAmount1" => $params['amount'],
            "itemQuantity1" => "1",
            "notificationURL" => "https://pagsegurohook.FastPayments.com/api/pagsegurohook/",
            "reference" => $params['pedido'],
            "senderName" => $params['user_name'],
            "senderCPF" => $params['user_document'],
            "senderAreaCode" => $area,
            "senderPhone" => $number_phone,
            "senderEmail" => $params['user_email'],
            "senderIp" => $params['ip'],
            "shippingAddressStreet" => "Alameda Santos",
            "shippingAddressNumber" => "1767",
            "shippingAddressComplement" => "",
            "shippingAddressDistrict" => "Cerqueira Cesar",
            "shippingAddressPostalCode" => "01419100",
            "shippingAddressCity" => "Sao Paulo",
            "shippingAddressState" => "SP",
            "shippingAddressCountry" => "BRA",
            "shippingType" => "1",
            "shippingCost" => "0.00",
            "creditCardToken" => $token_card,
            "installmentQuantity" => "1",
            "installmentValue" => $params['amount'],
            "creditCardHolderName" => $params['user_name'],
            "creditCardHolderCPF" => $params['user_document'],
            "creditCardHolderBirthDate" => $birth_date,
            "creditCardHolderAreaCode" => $area,
            "creditCardHolderPhone" => $number_phone,
            "billingAddressStreet" => $params['user_address'],
            "billingAddressNumber" => $params['user_address_number'],
            "billingAddressComplement" => $params['user_address_complement'],
            "billingAddressDistrict" => $params['user_address_district'],
            "billingAddressPostalCode" => $params['user_address_zipcode'],
            "billingAddressCity" => $params['user_address_city'],
            "billingAddressState" => $params['user_address_state'],
            "billingAddressCountry" => "BRA",
        );

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://ws.pagseguro.uol.com.br/v2/transactions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded',
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $this->registerRecivedsRequests("/var/www/html/nexapay/logs/create_cc_pagseguro_return.txt",json_encode((array)simplexml_load_string($response)));

        return json_encode((array)simplexml_load_string($response));

    }

    // Generate Token Credit Card Pagseguro
    public function generateTokenCCPagseguro($params = array(),$token_auth){
        $curl = curl_init();

        $data = array(
            "sessionId" => $token_auth,
            "cardNumber" => $params['card_number'],
            "cardBrand" => $params['card_brand'],
            "cardCvv" => $params['card_cvv'],
            "cardExpirationMonth" => substr($params['card_expired'],0,2),
            "cardExpirationYear" => substr($params['card_expired'],2,4),
        );

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://df.uol.com.br/v2/cards',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return json_encode((array)simplexml_load_string($response));
    }

    // Generate Token Auth Pagseguro
    public function generateTokenPagseguro($email,$token){

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://ws.pagseguro.uol.com.br/v2/sessions?email='.$email.'&token='.$token,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return json_encode((array)simplexml_load_string($response));
    }

    // Credit Card Credpay
    public function createCredpayCC($params,$tokenCredpay){

        $FunctionsAPIController = new FunctionsAPIController();

        $tkcr = json_decode($this->generateSimulator($params['amount'],$tokenCredpay),true);

        if(isset($tkcr['simulationToken'])){
            $tokenCredit = $tkcr['simulationToken'];

            $transactionCredpay = json_decode($this->createPaymentCredpay($tokenCredpay,$tokenCredit,$params),true);

            if(isset($transactionCredpay['transactionId'])){

                $dados = "";
                $data_bank = json_encode($transactionCredpay,true);
                $payment_id = $transactionCredpay['transactionId'];
                $date = date("Y-m-d H:i:s");
                $client = Clients::where("id","=",$params['client_id'])->first();

                if($params['card_mod'] == "card_b"){
                    $bank = Banks::where("id","42")->first();
                }else{
                    $bank = $client->bankCreditCard;
                }

                $days_safe_credit_card = $client->days_safe_credit_card;

                $link_shop = $transactionCredpay['payment']['card']['authenticationURL'];

                $user_data = array(
                    "name" => $params['user_name'],
                    "document" => $params['user_document'],
                    "bank_name" => $bank->name,
                    "holder" => $bank->holder,
                    "agency" => $bank->agency,
                    "account_number" => $bank->account,
                    "operation_bank" => $bank->type_account,
                    "user_id" => $params['user_id'],
                    "client_id" => $params['client_id'],
                    "address" => $this->trata_unicode($params['user_address']),
                    "district" => $this->trata_unicode($params['user_address_district']),
                    "city" => $this->trata_unicode($params['user_address_city']),
                    "uf" => $params['user_address_state'],
                    "cep" => $params['user_address_zipcode']
                );

                $user_account_data = base64_encode(json_encode($user_data));

                // Calulo Taxas //
                $cot_ar = $this->get_cotacao_dolar($params['client_id'],"deposit");
                $cotacao_dolar_markup = $cot_ar['markup'];
                $cotacao_dolar = $cot_ar['quote'];
                $spread_deposit = $cot_ar['spread'];

                // Taxas
                $tax = $client->tax;

                if($client->currency == "brl"){

                    $cotacao_dolar_markup = "1";
                    $cotacao_dolar = "1";
                    $spread_deposit = "0";

                    $final_amount = $params['amount'];
                    $percent_fee = ($final_amount * ($tax->credit_card_percent / 100));
                    $fixed_fee = $tax->credit_card_absolute;
                    if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                    $comission = ($percent_fee + $fixed_fee);
                    if($comission < $tax->min_fee_credit_card){ $comission = $tax->min_fee_credit_card; $min_fee = $tax->min_fee_credit_card; }else{ $min_fee = "NULL"; }

                }elseif($client->currency == "usd"){

                    $final_amount = number_format(($params['amount'] / $cotacao_dolar_markup),6,".","");
                    $fixed_fee = number_format(($tax->credit_card_absolute),6,".","");
                    $percent_fee = number_format(($final_amount * ($tax->credit_card_percent / 100)),6,".","");
                    if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                    $comission = ($percent_fee + $fixed_fee);
                    if($comission < $tax->min_fee_credit_card){ $comission = $tax->min_fee_credit_card; $min_fee = $tax->min_fee_credit_card; }else{ $min_fee = "NULL"; }

                }

                // $number_card_bd = "";
                // $brand = $params['card_brand'];
                $number_card_bd = substr($params['card_number'],0,6)."******".substr($params['card_number'],12,4);
                $brand = strtolower($params['card_brand']);

                $exp_date = substr($params['card_expired'],0,2)."/".substr($params['card_expired'],2,4);
                $amount_clear = str_replace(",","",$params['amount']);
                $amount_clear = str_replace(".","",$amount_clear);

                $autenticate = "false";

                $link_callback_bank = $link_shop;
                $return_url = $params['return_url'];

                DB::beginTransaction();
                try{

                    // Insert Transaction
                    $transaction = Transactions::create([
                        "solicitation_date" => $date,
                        "final_date" => $date,
                        "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_credit_card." days")),
                        "code" => $params['pedido'],
                        "client_id" => $params['client_id'],
                        "order_id" => $params['order_id'],
                        "user_id" => $params['user_id'],
                        "user_document" => $params['user_document'],
                        "user_account_data" => $user_account_data,
                        "user_name" => $params['user_name'],
                        "id_bank" => $bank->id,
                        "type_transaction" => 'deposit',
                        "method_transaction" => 'credit_card',
                        "amount_solicitation" => $params['amount'],
                        "final_amount" => $final_amount,
                        // "percent_fee" => $percent_fee,
                        // "fixed_fee" => $fixed_fee,
                        // "comission" => $comission,
                        // "min_fee" => $min_fee,
                        "status" => 'pending',
                        "payment_id" => $payment_id,
                        "dados" => $dados,
                        "data_bank" => $data_bank,
                        "link_callback_bank" => $link_callback_bank,
                        "url_retorna" => $return_url,
                        "code_bank" => $bank->code,
                        "number_card" => $number_card_bd,
                        "brand" => $brand,
                        "quote" => $cotacao_dolar,
                        "percent_markup" => $spread_deposit,
                        "quote_markup" => $cotacao_dolar_markup,
                    ]);

                    DB::commit();

                    $json_return = array(
                        "order_id" => $params['order_id'],
                        "solicitation_date" => $date,
                        "due_date" => $this->datetostr(date("Y-m-d",strtotime($date."+ ".$days_safe_credit_card." days"))),
                        "code_identify" => $params['pedido'],
                        "amount" => $params['amount'],
                        "fees" => $comission,
                        "status" => "pending",
                        "link_shop" => $link_shop,
                        "link_invoice" => $link_shop
                    );

                    // Success
                    return response()->json($json_return,200);

                }catch(exception $e){
                    DB::rollback();
                }

            }else{
                // Error, Brand not found
                $json_return = array("message" => "Error on create credit card transaction", "reason" => "Illegal Conditions", "return" => json_encode($transactionCredpay));

                $this->registerRecivedsRequests("/var/www/html/nexapay/logs/request-error-log-deposit-creditcard-credpay.txt",json_encode($json_return));

                return response()->json($json_return,401);
                exit();

            }

        }



    }

    // Generate Simulator value CredPay
    public function generateSimulator($amount,$tokenCredpay){

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://payments.credpay.com.vc/v2/simulador/obterTaxas',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
            "valor": '.$amount.',
            "servico": 2
        }',
        CURLOPT_HTTPHEADER => array(
        'x-api-key: '.$tokenCredpay,
        'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    // Create Transaction CredPay
    public function createPaymentCredpay($tokenCredpay,$tokenCredit,$params = array()){

        $client = Clients::where("id",$params['client_id'])->first();
        $bank = $client->bankCreditCard;

        $data = [
            "referenceId" => $params['pedido'],
            "simulationToken" => $tokenCredit,
            "forcarAutenticacao" => true,
            "description" => "CREDITO EM ".$params['bank_holder'],
            "postbackUrl" => "https://credpayhook.FastPayments.com/api/credpaywebhook",
            "redirectUrl" => "https://credpay.FastPayments.com/confirm",
            "urlQuery" => "?transactionId=",
            "customer" =>
                [
                    "name" => $params['user_name'],
                    "document" => $params['user_document'],
                    "mobilePhone" => $params['user_phone'],
                    "email" => $params['user_email'],
                    "postCode" => $params['user_address_zipcode'],
                    "number" => $params['user_address_number']
                ],
            "items" =>
                [
                    "totalAmount" => $params['amount'],
                    "unitPrice" => $params['amount'],
                    "quantity" => 1,
                    "productSKU" => "001",
                    "productDescription" => "CREDITO EM ".$params['bank_holder'],
                ],
            "payment" =>
                [
                    "card" =>
                        [
                            "type" => "1",
                            "installments" => "1",
                            "authenticate" => "1",
                            "capture" => "true",
                            "recurrent" => "",
                            "cardInfo" =>
                                [
                                    "number" => $params['card_number'],
                                    "expirationMonth" => substr($params['card_expired'],0,2),
                                    "expirationYear" => substr($params['card_expired'],2,4),
                                    "cvv" => $params['card_cvv'],
                                    "holderName" => $params['card_holder'],
                                    "document" => $params['user_document'],
                                ]
                        ],
                ]
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://payments.credpay.com.vc/v2/transacao/autorizar',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data,true),
        CURLOPT_HTTPHEADER => array(
        'x-api-key: '.$tokenCredpay,
        'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    // Get Status Transaction Credpay
    public function getstatusCredpay($tokenCredpay,$authorizationToken){

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://payments.credpay.com.vc/v2/transacao/consultar/'.$authorizationToken,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
        'x-api-key: '.$tokenCredpay
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    // Create PING Transaction Credpay
    public function validatePingCredpayCC($params = array(),$tokenCredpay){

        $client = Clients::where("id",$params['client_id'])->first();
        $bank = $client->bankCreditCard;

        $data = [
            "referenceId" => $params['pedido'],
            "valor" => $params['amount'],
            "payment" => [
                "type" => "1",
                "number" => $params['card_number'],
                "expirationMonth" => substr($params['card_expired'],0,2),
                "expirationYear" => substr($params['card_expired'],2,4),
                "cvv" => $params['card_cvv'],
                "holderName" => $params['card_holder'],
                "document" => $params['user_document']
            ]
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://payments.credpay.com.br/v2/transacao/ping',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data,true),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'x-api-key: '.$tokenCredpay
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $rep = json_decode($response,true);

        if(isset($rep['status'])){
            if($rep['status'] === true){

                $dados = "";
                $data_bank = "";
                $payment_id = "";
                $date = date("Y-m-d H:i:s");
                $client = Clients::where("id","=",$params['client_id'])->first();

                if($params['card_mod'] == "card_b"){
                    $bank = Banks::where("id","42")->first();
                }else{
                    $bank = $client->bankCreditCard;
                }

                $days_safe_credit_card = $client->days_safe_credit_card;

                $link_shop = "";

                $user_data = array(
                    "name" => $params['user_name'],
                    "document" => $params['user_document'],
                    "bank_name" => $bank->name,
                    "holder" => $bank->holder,
                    "agency" => $bank->agency,
                    "account_number" => $bank->account,
                    "operation_bank" => $bank->type_account,
                    "user_id" => $params['user_id'],
                    "client_id" => $params['client_id'],
                    "address" => $this->trata_unicode($params['user_address']),
                    "district" => $this->trata_unicode($params['user_address_district']),
                    "city" => $this->trata_unicode($params['user_address_city']),
                    "uf" => $params['user_address_state'],
                    "cep" => $params['user_address_zipcode']
                );

                $user_account_data = base64_encode(json_encode($user_data));

                // Calulo Taxas //
                $cot_ar = $this->get_cotacao_dolar($params['client_id'],"deposit");
                $cotacao_dolar_markup = $cot_ar['markup'];
                $cotacao_dolar = $cot_ar['quote'];
                $spread_deposit = $cot_ar['spread'];

                // Taxas
                $tax = $client->tax;

                if($client->currency == "brl"){

                    $cotacao_dolar_markup = "1";
                    $cotacao_dolar = "1";
                    $spread_deposit = "0";

                    $final_amount = $params['amount'];
                    $percent_fee = ($final_amount * ($tax->credit_card_percent / 100));
                    $fixed_fee = $tax->credit_card_absolute;
                    if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                    $comission = ($percent_fee + $fixed_fee);
                    if($comission < $tax->min_fee_credit_card){ $comission = $tax->min_fee_credit_card; $min_fee = $tax->min_fee_credit_card; }else{ $min_fee = "NULL"; }

                }elseif($client->currency == "usd"){

                    $final_amount = number_format(($params['amount'] / $cotacao_dolar_markup),6,".","");
                    $fixed_fee = number_format(($tax->credit_card_absolute),6,".","");
                    $percent_fee = number_format(($final_amount * ($tax->credit_card_percent / 100)),6,".","");
                    if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                    $comission = ($percent_fee + $fixed_fee);
                    if($comission < $tax->min_fee_credit_card){ $comission = $tax->min_fee_credit_card; $min_fee = $tax->min_fee_credit_card; }else{ $min_fee = "NULL"; }

                }

                $number_card_bd = substr($params['card_number'],0,6)."******".substr($params['card_number'],12,4);
                $brand = strtolower($params['card_brand']);

                $exp_date = substr($params['card_expired'],0,2)."/".substr($params['card_expired'],2,4);
                $amount_clear = str_replace(",","",$params['amount']);
                $amount_clear = str_replace(".","",$amount_clear);

                $autenticate = "false";

                $link_callback_bank = $link_shop;
                $return_url = $params['return_url'];

                DB::beginTransaction();
                try{

                    // Insert Transaction
                    $transaction = Transactions::create([
                        "solicitation_date" => $date,
                        "refund_date" => $date,
                        "final_date" => $date,
                        "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_credit_card." days")),
                        "code" => $params['pedido'],
                        "client_id" => $params['client_id'],
                        "order_id" => $params['order_id'],
                        "user_id" => $params['user_id'],
                        "user_document" => $params['user_document'],
                        "user_account_data" => $user_account_data,
                        "user_name" => $params['user_name'],
                        "id_bank" => $bank->id,
                        "type_transaction" => 'deposit',
                        "method_transaction" => 'credit_card',
                        "amount_solicitation" => $params['amount'],
                        "final_amount" => $final_amount,
                        "percent_fee" => $percent_fee,
                        "fixed_fee" => $fixed_fee,
                        "comission" => "0.00",
                        "min_fee" => $min_fee,
                        "status" => 'refund',
                        "payment_id" => $payment_id,
                        "dados" => $dados,
                        "data_bank" => $data_bank,
                        "link_callback_bank" => $link_callback_bank,
                        "url_retorna" => $return_url,
                        "code_bank" => $bank->code,
                        "number_card" => $number_card_bd,
                        "brand" => $brand,
                        "quote" => $cotacao_dolar,
                        "percent_markup" => $spread_deposit,
                        "quote_markup" => $cotacao_dolar_markup,
                    ]);

                    DB::commit();

                    $json_return = array(
                        "order_id" => $params['order_id'],
                        "solicitation_date" => $date,
                        "refund_date" => $date,
                        "due_date" => $this->datetostr(date("Y-m-d",strtotime($date."+ ".$days_safe_credit_card." days"))),
                        "code_identify" => $params['pedido'],
                        "amount" => $params['amount'],
                        "fees" => "0.00",
                        "status" => "refund"
                    );

                    // Success
                    return response()->json($json_return,200);

                }catch(exception $e){
                    DB::rollback();
                }

            }else{

                // Error, Brand not found
                $json_return = array("message" => "Error in validating your credit card", "reason" => "Illegal Conditions", "return" => json_encode($rep));
                return response()->json($json_return,401);
                exit();

            }
        }

    }

    // new integration Voluti //

    public function createTransactionPIXVolutiNew($params = array()){

        $clients = Clients::where("id","=",$params['client_id'])->first();
        $getTokenVoluti = json_decode($this->getTokenVolutiNew($params),true);

        $pixVolutiNew = json_decode($this->registerPIXVolutiNew($params,$getTokenVoluti['access_token']),true);

        if(isset($pixVolutiNew['pixCopiaECola'])){

            $dados = $pixVolutiNew['pixCopiaECola'];
            $payment_id = $pixVolutiNew['txid'];
            $date = date("Y-m-d H:i:s");
            $barcode = "";

            $check_count = strlen($dados);

            if($check_count < 10){

                $path_name = "fastlogs-pix-volutinew-".date("Y-m-d");

                if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                    mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                }

                $payload = [
                    "date" => $date,
                    "client" => $clients->name,
                    "params" => $params,
                    "return_voluti" => $pixVolutiNew
                ];

                $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/pixlog.txt",json_encode($payload));

                $ar = array(
                    "code" => "558",
                    "message" => "Erro on create QrCode PIX ".$params['pedido'],
                    "return" => $pixVolutiNew
                );

                return response()->json($ar,422);

            }

            // GET DAYS SAFE
            $days_safe_pix = $clients->days_safe_pix;

            // GET BANK DATA
            $bank = $clients->bankPix;
            $link_callback_bank = "";

            $user_data = array(
                "name" => $params['nome_usuario'],
                "document" => $params['documento_usuario'],
                "bank_name" => $bank->name,
                "holder" => $bank->holder,
                "agency" => $bank->agency,
                "account_number" => $bank->account,
                "operation_bank" => $bank->type_account,
                "user_id" => $params['user_id'],
                "client_id" => $params['client_id'],
                "address" => $this->trata_unicode($params['endereco_usuario']),
                "district" => $this->trata_unicode($params['bairro_usuario']),
                "city" => $this->trata_unicode($params['cidade_usuario']),
                "uf" => $params['uf_usuario'],
                "cep" => $params['cep_usuario']
            );

            $user_account_data = base64_encode(json_encode($user_data));

            // Taxas
            $tax = $clients->tax;

            $cotacao_dolar_markup = "1";
            $cotacao_dolar = "1";
            $spread_deposit = "0";

            $final_amount = $params['amount'];
            $percent_fee = ($final_amount * ($tax->pix_percent / 100));
            $fixed_fee = $tax->pix_absolute;
            if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
            $comission = ($percent_fee + $fixed_fee);
            if($comission < $tax->min_fee_pix){ $comission = $tax->min_fee_pix; $min_fee = $tax->min_fee_pix; }else{ $min_fee = "NULL"; }


            $data_invoice_id = NULL;

            DB::beginTransaction();
            try {

                // Insert Transaction
                $transaction = Transactions::create([
                    "solicitation_date" => $date,
                    "final_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                    "code" => $params['pedido'],
                    "client_id" => $params['client_id'],
                    "order_id" => $params['order_id'],
                    "user_id" => $params['user_id'],
                    "user_document" => $params['documento_usuario'],
                    "user_account_data" => $user_account_data,
                    "user_name" => $params['nome_usuario'],
                    "id_bank" => $clients->bankPix->id,
                    "type_transaction" => 'deposit',
                    "method_transaction" => 'pix',
                    "amount_solicitation" => $params['amount'],
                    "final_amount" => $final_amount,
                    "status" => 'pending',
                    "bank_data" => $dados,
                    "payment_id" => $payment_id,
                    "code_bank" => "221",
                    "link_callback_bank" => $link_callback_bank,
                    "url_retorna" => "",
                    "data_invoice_id" => $data_invoice_id,
                    "quote" => $cotacao_dolar,
                    "percent_markup" => $spread_deposit,
                    "quote_markup" => $cotacao_dolar_markup,
                ]);

                DB::commit();

                $link_qr = "https://image-charts.com/chart?chs=350x350&cht=qr&chl=".$dados;

                $json_return = array(
                    "order_id" => $params['order_id'],
                    "solicitation_date" => $date,
                    "due_date" => date("Y-m-d",strtotime($date."+ ".$days_safe_pix." days")),
                    "code_identify" => $params['pedido'],
                    "amount" => $params['amount'],
                    "status" => "pending",
                    "link_qr" => $link_qr,
                    "content_qr" => $dados
                );

                $path_name = "fastlogs-pix-volutinew-success-".date("Y-m-d");

                if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                    mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                }

                $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode(["json_return" => $json_return, "return_voluti" => $pixVolutiNew]));

                // Success
                return response()->json($json_return,200);

            }catch(exception $e){
                DB::roolback();
            }

        }else{

            return response()->json($pixVolutiNew);

        }

    }

    public function getTokenVolutiNew($params = array()){

        $url = "https://api.pix.voluti.com.br/oauth/token";

		$postData = http_build_query([
			'client_id'     => $params['voluti_clientid'],
			'client_secret' => $params['voluti_clientsecret'],
			'grant_type'    => 'client_credentials'
		]);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/x-www-form-urlencoded',
			'Accept: application/json'
		]);

        curl_setopt($ch, CURLOPT_SSLCERT, env('VOLUTI_CERT_DEPOSIT'));
        curl_setopt($ch, CURLOPT_SSLCERTPASSWD, env('VOLUTI_CERT_DEPOSIT_PASSPHRASE'));
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, env('VOLUTI_SSLKEYTYPE'));
        curl_setopt($ch, CURLOPT_SSLKEY, env('VOLUTI_KEY_DEPOSIT'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

		$response = curl_exec($ch);

		if (curl_errno($ch)) {
			print_r(curl_error($ch));
            curl_close($ch);
            exit();
		}

		curl_close($ch);

		return $response;

    }

    public function getTokenVolutiWithdrawNew($params = array()){

        $url = "https://accounts.voluti.com.br/api/v2/oauth/token";

        $postData = http_build_query([
			'client_id'     => $params['voluti_clientid'],
			'client_secret' => $params['voluti_clientsecret'],
			'grant_type'    => 'client_credentials'
		]);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/x-www-form-urlencoded',
			'Accept: application/json'
		]);

        curl_setopt($ch, CURLOPT_SSLCERT, env('VOLUTI_CERT_WITHDRAW'));
        curl_setopt($ch, CURLOPT_SSLCERTPASSWD, env('VOLUTI_CERT_WITHDRAW_PASSPHRASE'));
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, env('VOLUTI_SSLKEYTYPE'));
        curl_setopt($ch, CURLOPT_SSLKEY, env('VOLUTI_KEY_WITHDRAW'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

		$response = curl_exec($ch);

		if (curl_errno($ch)) {
			print_r(curl_error($ch));

            $path_name = "volutinew-token-error-withdraw-".date("Y-m-d");

            if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
            }

            $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode(["error" => print_r(curl_error($ch))]));

            curl_close($ch);
            exit();
		}

		curl_close($ch);

		return $response;

    }

    public function registerPIXVolutiNew($params = array(),$token){

        $url = "https://api.pix.voluti.com.br/cob";

		$postData = [
			"calendario" => [
                "expiracao" => $params['expiration']
            ],
            "valor" => [
                "original" => floatval($params['amount']),
                "modalidadeAlteracao" => "0"
            ],
            "devedor" => [
                "cpf" => $params['cpf'],
                "nome" => $params['nome_usuario']
            ],
            "chave" => $params['voluti_pixkey']
		];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Accept: application/json',
            'Authorization: Bearer '.$token
		]);

        curl_setopt($ch, CURLOPT_SSLCERT, env('VOLUTI_CERT_DEPOSIT'));
        curl_setopt($ch, CURLOPT_SSLCERTPASSWD, env('VOLUTI_CERT_DEPOSIT_PASSPHRASE'));
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, env('VOLUTI_SSLKEYTYPE'));
        curl_setopt($ch, CURLOPT_SSLKEY, env('VOLUTI_KEY_DEPOSIT'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

		$response = curl_exec($ch);

		if (curl_errno($ch)) {
			print_r(curl_error($ch));
            curl_close($ch);
            exit();
		}

		curl_close($ch);

		return $response;

    }

    // end new integration Voluti //

    // strat integration LuxTax //

    public function registerPIXLUXTAX($params = array()){

        $data = [
            "app_id" => "17517246249487096",
            "out_trade_no" => $params['pedido'],
            "method" => "PIX",
            "order_amount" => $params['amount'],
            "order_currency" => "BRL",
            "subject" => "Pagamento ID ".$params['pedido'],
            "content" => "Pagamento ID ".$params['pedido'],
            "notify_url" => "https://hook.fastpayments.com.br/api/luxtakhook",
            "return_url" => "",
            "buyer_id" => "buyer_0101_0001",
            "timestamp" => date("Y-m-d H:i:s"),
            "timeout_express" => "1c",
            "customer" => [
            "identify" => [
                "type" => "CPF",
                "number" => $params['documento_usuario']
                ],
            "name" => $params['nome_usuario'],
            "email" => "test@luxtak.com"
            ]
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://gateway.luxtak.com/trade/pay',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Authorization: Basic MTc1MTcyNDYyNDk0ODcwOTY6THV4dGFrX3NrX2I0NDcyNDkzMDdhMDE2NDFmNzJjNDQ4NDcyOGNjZWIzMDU4NDg0MDUyZTZiYzYxYmJhMDIzOGNmMDUyZDIyOTc=',
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

		return $response;

    }

    // end integration LuxTax

    // strat integration SuitPay //

    public function registerPIXSUITPAY($params = array()){

        $data = [
            "requestNumber" => $params['pedido'],
            "dueDate" => date("Y-m-d"),
            "amount" => $params['amount'],
            "shippingAmount" => 0.0,
            "discountAmount" => 0.0,
            "usernameCheckout" => "checkout",
            "callbackUrl" => "https://hooknexapay.financebaking.com/api/suitpayhook",
            "client" => [
                "name" => $params['nome_usuario'],
                "document" => $params['documento_usuario'],
                "phoneNumber" => "62999815500",
                "email" => "systemnexapay@gmail.com",
                    "address" => [
                        "codIbge" =>"5208707",
                        "street" => $params['endereco_usuario'],
                        "number" => $params['numero_endereco'],
                        "complement" => "",
                        "zipCode" => $params['cep_usuario'],
                        "neighborhood" => $params['bairro_usuario'],
                        "city" => $params['cidade_usuario'],
                        "state" => $params['uf_usuario']
                    ]
            ],
            "products" => [
                array(
                    "description" => "Pedido n ".$params['pedido'],
                "quantity" => 1,
                "value" => $params['amount']
                )
            ]
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://ws.suitpay.app/api/v1/gateway/request-qrcode',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'ci: '.$params['ci'],
            'cs: '.$params['cs']
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

		return $response;

    }

    // end integration SuitPay


    // Functions used as inheritance

    public function error_log_api($type,$method,$params = array(),$json_return,$http_status){
        DB::beginTransaction();
        try {

            $request_body = json_encode($params,true);

            // Insert Transaction
            $api = Api::create([
                "order_id" => $params['order_id'],
                "method_payment" => $method,
                "action" => $type,
                "request_body" => base64_encode($request_body),
                "response_body" => json_encode($json_return),
                "callback_body" => "",
                "http_status" => $http_status
            ]);

            DB::commit();

            return "success";
        }catch(exception $e){
            DB::roolback();

            return "error";
        }
    }

    // Generates deposit pedido number without repetition
    public function gera_pedido($client_id){

        $number_invoice = mt_rand(0,99999);

        $number_invoice_set = $number_invoice;

        if(strlen($number_invoice_set) == "8"){
            $pedido = $number_invoice_set;
        }elseif(strlen($number_invoice_set) == "7"){
            $pedido = "0".$number_invoice_set;
        }elseif(strlen($number_invoice_set) == "6"){
            $pedido = "00".$number_invoice_set;
        }elseif(strlen($number_invoice_set) == "5"){
            $pedido = "000".$number_invoice_set;
        }elseif(strlen($number_invoice_set) == "4"){
            $pedido = "0000".$number_invoice_set;
        }elseif(strlen($number_invoice_set) == "3"){
            $pedido = "00000".$number_invoice_set;
        }elseif(strlen($number_invoice_set) == "2"){
            $pedido = "000000".$number_invoice_set;
        }elseif(strlen($number_invoice_set) == "1"){
            $pedido = "0000000".$number_invoice_set;
        }elseif(strlen($number_invoice_set) == "0"){
            $pedido = "00000000".$number_invoice_set;
        }

        $sql_consult = Transactions::where("client_id","=",$client_id)->where("code","=",$pedido)->first();

        if($sql_consult){
            return gera_pedido($client_id);
        }else{
            return $pedido;
        }

    }

    // Generates deposit pix pedido number without repetition
    public function gera_pedido_pix($client_id){


        $number_invoice_one = mt_rand(0,99999999);
        $number_invoice_two = mt_rand(0,99999999);
        $number_invoice_three = mt_rand(0,99999999);

        $number_invoice = $number_invoice_one.$number_invoice_two.$number_invoice_three;

        $cnt = strlen($number_invoice);
        $ccnt = (21 - $cnt);

        if($ccnt > 0){

            for($i = 0;$i <= ($ccnt - 1);$i++){
                $number_invoice .= "0".$number_invoice;
            }

        }

        $pedido = $number_invoice;

        $client = Clients::where("id",$client_id)->first();
        $bank = $client->bankPix;

        $sql_consult = Transactions::where("client_id","=",$client_id)->where("code","=",$bank->prefix.$pedido)->first();
        if($sql_consult){
            return gera_pedido($client_id);
        }else{
            return $pedido;
        }



    }

    public function gera_pedido_pix_new($client_id){


        $number_invoice_one = mt_rand(0,999999999);
        $number_invoice_two = mt_rand(0,999999999);
        $number_invoice_three = mt_rand(0,999999999);

        $number_invoice = $number_invoice_one.$number_invoice_two.$number_invoice_three;

        $cnt = strlen($number_invoice);
        $ccnt = (26 - $cnt);

        if($ccnt > 0){

            for($i = 0;$i <= ($ccnt - 1);$i++){
                $number_invoice .= "0".$number_invoice;
            }

        }

        $pedido = $number_invoice;

        $client = Clients::where("id",$client_id)->first();
        $bank = $client->bankPix;

        $sql_consult = Transactions::where("client_id","=",$client_id)->where("code","=",$bank->prefix.$pedido)->first();
        if($sql_consult){
            return gera_pedido_pix_new($client_id);
        }else{
            return $pedido;
        }



    }

    // Generates deposit pix pedido number without repetition
    public function gera_pedido_pix_celcoin($client_id){


        $number_random = mt_rand(11111111111,99999999999999);

        $number_random = $number_random;

        $cnt = strlen($number_random);
        $ccnt = (11 - $cnt);

        if($ccnt > 0){

            for($i = 0;$i <= ($ccnt - 1);$i++){
                $number_random .= "0".$number_random;
            }

        }

        $pedido = $number_random;

        $client = Clients::where("id",$client_id)->first();
        $bank = $client->bankPix;

        $sql_consult = Transactions::where("client_id","=",$client_id)->where("code","=",$bank->prefix.$pedido)->first();
        if($sql_consult){
            return gera_pedido($client_id);
        }else{
            return $pedido;
        }



    }

    // Generates withdraw pedido number without repetition
    public function gera_pedido_withdraw($client_id){

        $number_invoice = mt_rand(0,99999999);

        $number_invoice_set = $number_invoice;

        if(strlen($number_invoice_set) == "8"){
            $pedido = $number_invoice_set;
        }elseif(strlen($number_invoice_set) == "7"){
            $pedido = "0".$number_invoice_set;
        }elseif(strlen($number_invoice_set) == "6"){
            $pedido = "00".$number_invoice_set;
        }elseif(strlen($number_invoice_set) == "5"){
            $pedido = "000".$number_invoice_set;
        }elseif(strlen($number_invoice_set) == "4"){
            $pedido = "0000".$number_invoice_set;
        }elseif(strlen($number_invoice_set) == "3"){
            $pedido = "00000".$number_invoice_set;
        }elseif(strlen($number_invoice_set) == "2"){
            $pedido = "000000".$number_invoice_set;
        }elseif(strlen($number_invoice_set) == "1"){
            $pedido = "0000000".$number_invoice_set;
        }elseif(strlen($number_invoice_set) == "0"){
            $pedido = "00000000".$number_invoice_set;
        }

        $sql_consult = Transactions::where("client_id","=",$client_id)->where("code","=",$pedido)->first();
        if($sql_consult){
            return gera_pedido_withdraw($client_id);
        }else{
            return $pedido;
        }

    }

    // Checks if return URL exists
    public function returnurl($url){
        if(isset($url)){
            if($url != ""){
                $variavel_url_retorno = $url;
            }else{
                $variavel_url_retorno = "https://www.FastPayments.com/confirmacao";
            }
        }else{
            $variavel_url_retorno = "https://www.FastPayments.com/confirmacao";
        }

        return $variavel_url_retorno;
    }

    // Clear CPF
    public function clearCPF($valor){
        $valor = trim($valor);
        $valor = str_replace(".", "", $valor);
        $valor = str_replace(",", "", $valor);
        $valor = str_replace("-", "", $valor);
        $valor = str_replace("/", "", $valor);

        $leng = strlen($valor);
        if($leng == 10){
            $valor = "0".$valor;
        }elseif($leng == 9){
            $valor = "00".$valor;
        }elseif($leng == 8){
            $valor = "000".$valor;
        }

        return $valor;
    }

    public function day_week($date){
        // Array com os dias da semana
        $diasemana = array('dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sab');

        // Varivel que recebe o dia da semana (0 = Domingo, 1 = Segunda ...)
        $diasemana_numero = date('w', strtotime($date));

        return $diasemana[$diasemana_numero];
    }

    // Validate CPF
    public function validateCPF($cpf = null) {

        // Verifica se um número foi informado
        if(empty($cpf)) {
            return "no";
        }

        // Elimina possivel mascara
        $cpf = preg_replace("/[^0-9]/", "", $cpf);
        $cpf = str_pad($cpf, 11, '0', STR_PAD_LEFT);

        if($cpf == "45403734000150"){
            return "yes";
        }

        // Verifica se o numero de digitos informados é igual a 11
        if (strlen($cpf) != 11) {
            return "no";
        }
        // Verifica se nenhuma das sequências invalidas abaixo
        // foi digitada. Caso afirmativo, retorna falso
        else if ($cpf == '00000000000' ||
            $cpf == '11111111111' ||
            $cpf == '22222222222' ||
            $cpf == '33333333333' ||
            $cpf == '44444444444' ||
            $cpf == '55555555555' ||
            $cpf == '66666666666' ||
            $cpf == '77777777777' ||
            $cpf == '88888888888' ||
            $cpf == '99999999999') {

            return "no";
         // Calcula os digitos verificadores para verificar se o
         // CPF é válido
         } else {

            for ($t = 9; $t < 11; $t++) {

                for ($d = 0, $c = 0; $c < $t; $c++) {
                    $d += $cpf[$c] * (($t + 1) - $c);
                }
                $d = ((10 * $d) % 11) % 10;
                if ($cpf[$c] != $d) {
                    return "no";
                }
            }

            return "yes";
        }
    }

    // Validade Credit/Debit Card
    public function validateCard($card, $cvc = false){
        $card = preg_replace("/[^0-9]/", "", $card);
        if($cvc) $cvc = preg_replace("/[^0-9]/", "", $cvc);

        $cards = array(
                'visa'		 => array('len' => array(13,16),    'cvc' => 3),
                'mastercard' => array('len' => array(16),       'cvc' => 3),
                'diners'	 => array('len' => array(14,16),    'cvc' => 3),
                'elo'		 => array('len' => array(16),       'cvc' => 3),
                'amex'	 	 => array('len' => array(15),       'cvc' => 4),
                'discover'	 => array('len' => array(16),       'cvc' => 4),
                'aura'		 => array('len' => array(16),       'cvc' => 3),
                'jcb'		 => array('len' => array(16),       'cvc' => 3),
                'hipercard'  => array('len' => array(13,16,19), 'cvc' => 3),
        );


        switch($card){
            case (bool) preg_match('/^(636368|438935|504175|451416|636297)/', $card) :
                $brand = 'elo';
            break;

            case (bool) preg_match('/^(606282)/', $card) :
                $brand = 'hipercard';
            break;

            case (bool) preg_match('/^(5067|4576|4011)/', $card) :
                $brand = 'elo';
            break;

            case (bool) preg_match('/^(3841)/', $card) :
                $brand = 'hipercard';
            break;

            case (bool) preg_match('/^(6011)/', $card) :
                $brand = 'discover';
            break;

            case (bool) preg_match('/^(622)/', $card) :
                $brand = 'discover';
            break;

            case (bool) preg_match('/^(301|305)/', $card) :
                $brand = 'diners';
            break;

            case (bool) preg_match('/^(34|37)/', $card) :
                $brand = 'amex';
            break;

            case (bool) preg_match('/^(36,38)/', $card) :
                $brand = 'diners';
            break;

            case (bool) preg_match('/^(64,65)/', $card) :
                $brand = 'discover';
            break;

            case (bool) preg_match('/^(50)/', $card) :
                $brand = 'aura';
            break;

            case (bool) preg_match('/^(35)/', $card) :
                $brand = 'jcb';
            break;

            case (bool) preg_match('/^(60)/', $card) :
                $brand = 'hipercard';
            break;

            case (bool) preg_match('/^(4)/', $card) :
                $brand = 'visa';
            break;

            case (bool) preg_match('/^(5)/', $card) :
                $brand = 'mastercard';
            break;
        }

        $card_data = $cards[$brand];
        if(!is_array($card_data)) return array(false, false, false);

        $valid     = true;
        $valid_cvc = false;

        if(!in_array(strlen($card), $card_data['len'])) $valid = false;
        if($cvc AND strlen($cvc) <= $card_data['cvc'] AND strlen($cvc) !=0) $valid_cvc = true;
        return array($brand, $valid, $valid_cvc);
    }

    // Get address from table on DB
    public function get_random_address(){

        $address_other = DB::table('region')->inRandomOrder()->first();

        if($this->check_zipcode($address_other->cep) == "error"){
            $this->get_random_address();
        }

        $array_resposta = array("cep" => $address_other->cep, "endereco" => $this->trata_unicode($address_other->endereco), "bairro" => $this->trata_unicode($address_other->bairro), "cidade" => $this->trata_unicode($address_other->cidade), "estado" => $address_other->estado);

        return $array_resposta;

    }

    public function trata_unicode($string){

        // Letra A //
        $string = str_replace("\u00e1","á",$string);
        $string = str_replace("\u00e0","à",$string);
        $string = str_replace("\u00e2","â",$string);
        $string = str_replace("\u00e3","ã",$string);
        $string = str_replace("\u00e4","ä",$string);
        $string = str_replace("\u00c1","Á",$string);
        $string = str_replace("\u00c0","À",$string);
        $string = str_replace("\u00c2","Â",$string);
        $string = str_replace("\u00c3","Ã",$string);
        $string = str_replace("\u00c4","Ä",$string);

        // Letra E //
        $string = str_replace("\u00e9","é",$string);
        $string = str_replace("\u00e8","è",$string);
        $string = str_replace("\u00ea","ê",$string);
        $string = str_replace("\u00ea","ê",$string);
        $string = str_replace("\u00c9","É",$string);
        $string = str_replace("\u00c8","È",$string);
        $string = str_replace("\u00ca","Ê",$string);
        $string = str_replace("\u00cb","Ë",$string);

        // Letra I //
        $string = str_replace("\u00ed","í",$string);
        $string = str_replace("\u00ec","ì",$string);
        $string = str_replace("\u00ee","î",$string);
        $string = str_replace("\u00ef","ï",$string);
        $string = str_replace("\u00cd","Í",$string);
        $string = str_replace("\u00cc","Ì",$string);
        $string = str_replace("\u00ce","Î",$string);
        $string = str_replace("\u00cf","Ï",$string);

        // Letra O //
        $string = str_replace("\u00f3","ó",$string);
        $string = str_replace("\u00f2","ò",$string);
        $string = str_replace("\u00f4","ô",$string);
        $string = str_replace("\u00f5","õ",$string);
        $string = str_replace("\u00f6","ö",$string);
        $string = str_replace("\u00d3","Ó",$string);
        $string = str_replace("\u00d2","Ò",$string);
        $string = str_replace("\u00d4","Ô",$string);
        $string = str_replace("\u00d5","Õ",$string);
        $string = str_replace("\u00d6","Ö",$string);

        // Letra O //
        $string = str_replace("\u00fa","ú",$string);
        $string = str_replace("\u00f9","ù",$string);
        $string = str_replace("\u00fb","û",$string);
        $string = str_replace("\u00fc","ü",$string);
        $string = str_replace("\u00da","Ú",$string);
        $string = str_replace("\u00d9","Ù",$string);
        $string = str_replace("\u00db","Û",$string);

        // Consoeantes //
        $string = str_replace("\u00e7","ç",$string);
        $string = str_replace("\u00c7","Ç",$string);
        $string = str_replace("\u00f1","ñ",$string);
        $string = str_replace("\u00d1","Ñ",$string);

        $string = str_replace("s\/n","",$string);

        return $string;
    }

    public function limpaCPF_CNPJ($valor){
        $valor = trim($valor);
        $valor = str_replace(".", "", $valor);
        $valor = str_replace(",", "", $valor);
        $valor = str_replace("-", "", $valor);
        $valor = str_replace("/", "", $valor);

        $leng = strlen($valor);
        if($leng == 10){
            $valor = "0".$valor;
        }elseif($leng == 9){
            $valor = "00".$valor;
        }

        return $valor;
    }

    public function strtodouble($var) {
        $var = str_replace(".","",$var);
        $var = str_replace(",",".",$var);
        return $var;
    }

    // -- Funcção valor double para string -- //
    public function doubletostr($var) {
        $var = number_format($var, 2, ',', '.');
        return $var;
    }

    public function limit_caracter($var,$limit){
        return substr($var,0,$limit);
    }

    public function tirarAcentos($string){
        return preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/"),explode(" ","a A e E i I o O u U n N"),$string);
    }

    public function clear_phone($string){
        $string = str_replace("(","",$string);
        $string = str_replace(")","",$string);
        $string = str_replace("-","",$string);
        $string = str_replace(".","",$string);
        $string = str_replace(",","",$string);
        $string = str_replace(" ","",$string);

        return $string;
    }

    public function datetostr($data){
        $dia = substr($data, -2);
        $mes = substr($data, -5, 2);
        $ano = substr($data, -10, 4);
        $data = "$dia/$mes/$ano";
        return $data;
    }

    public function geraSenha($tamanho = 8, $maiusculas = true, $numeros = true, $simbolos = false, $menusculas = true){
        $lmin = 'abcdefghijklmnopqrstuvwxyz';
        $lmai = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $num = '1234567890';
        $simb = '!@#$%*-';
        $retorno = '';
        $caracteres = '';
        if ($menusculas) $caracteres .= $lmin;
        if ($maiusculas) $caracteres .= $lmai;
        if ($numeros) $caracteres .= $num;
        if ($simbolos) $caracteres .= $simb;
        $len = strlen($caracteres);
        for ($n = 1; $n <= $tamanho; $n++) {
            $rand = mt_rand(1, $len);
            $retorno .= $caracteres[$rand-1];
        }
        return $retorno;
    }

    public function get_cotacao_dolar($client_id,$param){

        $quote = Quote::orderByRaw("created_at DESC")->limit(1)->first();

        $client = Clients::where("id","=",$client_id)->first();
        // Taxas
        $tax = $client->tax;

        $spread = "";
        if($param == "deposit"){

            $spread_deposit = $tax->spread_deposit;
            $markup = ((1 + ($spread_deposit / 100)) * $quote->quote);
            $spread = $spread_deposit;

        }elseif($param == "withdraw"){

            $spread_withdraw = $tax->spread_withdraw;
            $markup = ((1 - ($spread_withdraw / 100)) * $quote->quote);
            $spread = $spread_withdraw;

        }

        $arr = array("markup" => $markup, "quote" => $quote->quote, "spread" => $spread);
        return $arr;

    }

    public function teste(){

        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIyMThlYmViMS1hN2U3LTRhOTgtOTg1Ny1iNWQ3NGUzYTM1MWQiLCJwYXlsb2FkIjp7IndhbGxldElkIjoiZjliM2Y0NTYtMTI0Ni00NTRjLThjYmMtNWU1MGFjYjg4YTk2IiwiY3VzdG9tZXJJZCI6IjEzZjg1ZTFjLTYzYjQtNDkzZS1hZmI5LTZhNWVhNTZlMGMzZSIsInBsYW5UeXBlcyI6WyJCU0MiLCJQTFMiXX0sInNjb3BlIjpbIndhbGxldDpmOWIzZjQ1Ni0xMjQ2LTQ1NGMtOGNiYy01ZTUwYWNiODhhOTY6cmVhZEJhbGFuY2UiLCJ3YWxsZXQ6ZjliM2Y0NTYtMTI0Ni00NTRjLThjYmMtNWU1MGFjYjg4YTk2OnJlYWRTdGF0ZW1lbnQiLCJnb3NzaXB5OmFkbWluX2RldmljZXMiLCJ3YWxsZXQ6ZjliM2Y0NTYtMTI0Ni00NTRjLThjYmMtNWU1MGFjYjg4YTk2OmNhcHR1cmVQYXltZW50IiwiYXV0aDpyZWFkVXNlciIsIm9yZGVyOmY5YjNmNDU2LTEyNDYtNDU0Yy04Y2JjLTVlNTBhY2I4OGE5Njp1cGRhdGVPcmRlciIsIm9yZGVyOmdldE9yZGVySWQiLCJjdXN0b21lcjoxM2Y4NWUxYy02M2I0LTQ5M2UtYWZiOS02YTVlYTU2ZTBjM2U6Z2V0Q3VzdG9tZXIiLCJ3YWxsZXQ6ZjliM2Y0NTYtMTI0Ni00NTRjLThjYmMtNWU1MGFjYjg4YTk2OmNyZWF0ZVRyYW5zZmVyIiwid2FsbGV0OmY5YjNmNDU2LTEyNDYtNDU0Yy04Y2JjLTVlNTBhY2I4OGE5NjpyZWZ1bmRQYXltZW50IiwiYXV0aDpjaGVja1VzZXIiLCJvcmRlcjpmOWIzZjQ1Ni0xMjQ2LTQ1NGMtOGNiYy01ZTUwYWNiODhhOTY6ZGVsZXRlT3JkZXIiLCJ3YWxsZXQ6ZjliM2Y0NTYtMTI0Ni00NTRjLThjYmMtNWU1MGFjYjg4YTk2OmNhbmNlbFBheW1lbnQiLCJ3YWxsZXQ6ZjliM2Y0NTYtMTI0Ni00NTRjLThjYmMtNWU1MGFjYjg4YTk2OmNhbmNlbENhc2hJbiIsIm9yZGVyOmY5YjNmNDU2LTEyNDYtNDU0Yy04Y2JjLTVlNTBhY2I4OGE5NjpnZXRPcmRlciIsIm9yZGVyOmY5YjNmNDU2LTEyNDYtNDU0Yy04Y2JjLTVlNTBhY2I4OGE5NjpjcmVhdGVPcmRlciIsIndhbGxldDpmOWIzZjQ1Ni0xMjQ2LTQ1NGMtOGNiYy01ZTUwYWNiODhhOTY6Y3JlYXRlQ2FzaEluIiwid2FsbGV0OmY5YjNmNDU2LTEyNDYtNDU0Yy04Y2JjLTVlNTBhY2I4OGE5NjpjcmVhdGVQYXltZW50IiwiZ29zc2lweTphZG1pbl9zdWJzY3JpcHRpb25zIl0sImlzcyI6Imh0dHBzOlwvXC9hbWVkaWdpdGFsLmNvbSIsInBsYXRmb3JtX2lkIjoiYW1lIiwiY29uc3VtZXJfdHlwZSI6IkNMSUVOVCIsImV4cCI6MTU5NTEyNDU3MiwiaWF0IjoxNTkyNTMyNTcyLCJqdGkiOiJiN2YzMTI4NS1hMzk4LTRjNjUtYTVjYy01OWQ0NmUzN2NiNGEiLCJjbGllbnRfaWQiOiIyMThlYmViMS1hN2U3LTRhOTgtOTg1Ny1iNWQ3NGUzYTM1MWQifQ.ZL6WSguLe1owYic0guxIlE2eba6yZd5ligL-fX6HnsG1OmXUpXSFE-45WE79oIwKc5ZvsKnhAkYo-W42h-xvMdU2_Md6TAuMr65A0uxA2jEJSTYg3CNsODGY7ojtBkkmVhYwr6N9koH-P7sQA9Z9UP0ygJ3C0ThBubBprUgasf7cujUn1z1ZrrGIbl3-KlG4sta8NS1_2D9ZTNfOKS-sA7GAdpef9Hi2htjfUokICN9YFEXKWzoHg4BjJ0etZsj9ljX6Vxjz20-upCCEMZt7q97AvKgiGwVYPn85mORzwDLkX0w-pW6G9xKninqAbMFJ6Bfmr180CvqK_j6DzfWbmA";

        $params_post = array(
            "title" => "",
            "description" => "Crédito em FastPayments",
            "amount"=> str_replace(".","","142.20"),
            "type"=> "PAYMENT",
            "attributes" => array(
                "cashbackAmountValue"=> 0,
                "transactionChangedCallbackUrl"=> "https://ame.FastPayments.com/webhook_ame",
                "paymentOnce"=> "true",
                "items" => array("0" => array(
                    "description"=> "Crédito em FastPayments",
                    "quantity"=> 1,
                    "amount"=> str_replace(".","","142.20")
                    )
                )
            )
        );

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://api.amedigital.com/api/orders",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => json_encode($params_post,true),
          CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer ".$token,
            "Content-Type: application/json"
          ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function registerRecivedsRequests($fullpath,$body){

        // $fullpath = caminho completo incluindo o nome do arquivo e extensão
        // $body = formato json

        $fp = fopen($fullpath, 'a');
        fwrite($fp, $body."\n");
        fclose($fp);

    }

    public function strtodate($data){
        $dia = substr($data, -10, 2);
        $mes = substr($data, -7, 2);
        $ano = substr($data, -4);
        $data = "$ano-$mes-$dia";
        return $data;
    }

    public function check_zipcode($cep) {

        $cep = preg_replace('/[^0-9]/', '', (string) $cep);

        $url = "http://viacep.com.br/ws/".$cep."/json/";
        // CURL
        $ch = curl_init();
        // Disable SSL verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // Will return the response, if false it print the response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Set the url
        curl_setopt($ch, CURLOPT_URL, $url);
        // Execute
        $result = curl_exec($ch);
        // Closing
        curl_close($ch);

        $json=json_decode($result);

        //var_dump($json);
        if(!isset($json->erro)){
            $array = "success";
        }else{
            $array = 'error';
        }

        return $array;
    }

    public function remove_accents($string){
        return preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/"),explode(" ","a A e E i I o O u U n N"),$string);
    }

    public function SomarData($data, $dias, $meses, $ano){
        $data = explode("/", $data);
        $newData = date("d/m/Y", mktime(0, 0, 0, $data[1] + $meses,
        $data[0] + $dias, $data[2] + $ano) );
        return $newData;
    }
}
