<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\BBBoletoWebService;
use App\Http\Controllers\ItaucriptoController;
use App\Models\{Clients,Extract,Transactions,DataInvoice,Api,Notifications,Banks,UserDataMP,UserCreditCardMP,Quote,IndexTransaction,LimitDetailUser};

class FunctionsController extends Controller
{

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

            $link_invoice = "https://xdash.FastPayments.com/get-invoice-FastPayments/".$params['authorization']."/".$params['order_id'];

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

                    $link_qr = "https://xdash.FastPayments.com/qrcodepix/".$transaction->id."/".$params['order_id']."/200x200";

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
            "cep" => ""
        );

        $user_account_data = base64_encode(json_encode($user_data));

        // Taxas
        $tax = $clients->tax;

        $percent_fee = ($params['amount'] * ($tax->withdraw_percent / 100));
        $fixed_fee = $tax->withdraw_absolute;
        $comission = ($percent_fee + $fixed_fee);

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
                "type_transaction" => 'withdraw',
                "method_transaction" => 'TEF',
                "amount_solicitation" => $params['amount'],
                "final_amount" => $params['amount'],
                "percent_fee" => $percent_fee,
                "fixed_fee" => $fixed_fee,
                "comission" => $comission,
                "status" => 'pending',
                "data_bank" => $dados,
                "payment_id" => $payment_id,
                "id_bank" => "14",
                "code_bank" => $params['bank_code'],
            ]);

            DB::commit();

            $json_return = array(
                "id" => $transaction->id,
                "order_id" => $params['order_id'],
                "solicitation_date" => $date,
                "user_id" => $params['user_id'],
                "user_name" => $params['user_name'],
                "user_document" => $params['user_document'],
                "code_identify" => $params['pedido'],
                "bank_name" => $params['bank_name'],
                "agency" => $params['agency'],
                "type_operation" => $params['type_operation'],
                "account" => $params['account'],
                "amount_solicitation" => $params['amount'],
                "currency" => $params['currency'],
                "status" => "pending"
            );

            if($clients->withdraw_permition === true){
                $id_bank_withdraw = $clients->bank_withdraw_permition;

                $bank_withdraw = Banks::where("id",$id_bank_withdraw)->first();

                if(!empty($bank_withdraw)){
                    if($bank_withdraw->withdraw_permition === true){

                        if($params['method'] == "pix"){
                            \App\Jobs\PerformWithdrawalPaymentPIX::dispatch($transaction->id,$bank_withdraw->id)->delay(now()->addSeconds('5'));
                        }else{
                            \App\Jobs\PerformWithdrawalPayment::dispatch($transaction->id,$bank_withdraw->id)->delay(now()->addSeconds('5'));
                        }

                    }
                }

            }

            // Success
            return response()->json($json_return,200);

        }catch(exception $e){
            DB::roolback();
        }
    }

    public function checkBalanceWithdraw($client_id,$amount_withdraw){

        // Get currency of Client
        $clients = Clients::where("id","=",$client_id)->first();
        $view_currency = $clients->currency;

        // Get deposits safe
        $deposit_safe = Transactions::where("client_id","=",$client_id)
        ->where("type_transaction","=","deposit")
        ->where("status","=","confirmed")
        ->where("disponibilization_date",">",date("Y-m-d"))
        ->get();

        // Get deposits safe
        $deposit_freeze = Transactions::where("client_id","=",$client_id)
            ->where("type_transaction","=","deposit")
            ->where("status","=","freeze")
            ->get();

        // Get deposits cancel fees
        $deposit_cancel = Transactions::where("client_id","=",$client_id)
            ->where("type_transaction","=","deposit")
            ->whereIn("method_transaction",['invoice'])
            ->where("status","=","canceled")
            ->where("solicitation_date",">=","2020-07-12 00:00:00")
            ->get();

        // Get deposits refund
        $deposit_refund = Transactions::where("client_id","=",$client_id)
            ->where("type_transaction","=","deposit")
            ->where("status","=","refund")
            ->get();

        // Get deposits chargeback
        $deposit_chargeback = Transactions::where("client_id","=",$client_id)
            ->where("type_transaction","=","deposit")
            ->where("status","=","chargeback")
            ->get();

        // Get withdrawals confirmed
        $withdraw_confirmed = Transactions::where("client_id","=",$client_id)
            ->where("type_transaction","=","withdraw")
            ->where("status","=","confirmed")
            ->get();

        // Get withdrawals cancel
        $withdraw_cancel = Transactions::where("client_id","=",$client_id)
            ->where("type_transaction","=","withdraw")
            ->where("status","=","canceled")
            ->get();

        /**
         * Bloco final amounts
         */

        // Get total deposits confirmed
        $total_deposit_confirmed = Transactions::where("client_id","=",$client_id)
            ->where("type_transaction","=","deposit")
            ->where("status","=","confirmed")
            ->where("disponibilization_date","<=",date("Y-m-d"))
            ->where("id_bank","!=","5")
            ->where("amount_solicitation",">=","5")
            ->sum("amount_solicitation");

        // Get total withdaws confirmed
        $total_withdraw_confirmed = $withdraw_confirmed->sum("amount_solicitation");

        // Get total deposits confirmed nao disponiveis
        $total_deposit_confirmed_unavailable = $deposit_safe->sum('amount_solicitation');

        // Get total freeze
        $total_deposit_freeze = $deposit_freeze->sum("amount_solicitation");

        /**
         * Bloco Fees
         */

        // Get total fee deposits confirmed
        $total_deposit_confirmed_fee = Transactions::where("client_id","=",$client_id)
            ->where("type_transaction","=","deposit")
            ->where("status","=","confirmed")
            ->where("disponibilization_date","<=",date("Y-m-d"))
            ->sum("comission");

        // Get total fee deposits invoice canceled
        $total_deposit_canceled_fee = $deposit_cancel->sum("comission");

        // Get total fee withdrawals confirmed
        $total_withdraw_confirmed_fee = $withdraw_confirmed->sum("comission");

        // Get total fee withdrawals canceled
        $total_withdraw_canceled_fee = $withdraw_cancel->sum("comission");

        // Get total fee deposits refund
        $total_deposit_refund_fee = $deposit_refund->sum("comission");

        // Get total fee deposits chargeback
        $total_deposit_chargeback_fee = $deposit_chargeback->sum("comission");

        // Get total freeze fee
        $total_deposit_freeze_fee = $deposit_freeze->sum("comission");

        // Get total fee unavailable
        $total_fee_confirmed_unavailable = $deposit_safe->sum('comission');

        /**
         * Soma geral
         */

        $total_available = ($total_deposit_confirmed - $total_withdraw_confirmed - ($total_deposit_confirmed_fee + $total_deposit_canceled_fee + $total_withdraw_confirmed_fee + $total_withdraw_canceled_fee + $total_deposit_refund_fee + $total_deposit_chargeback_fee));
        // $total_safe = ($total_deposit_confirmed_unavailable + $total_deposit_freeze - ($total_fee_confirmed_unavailable - $total_deposit_freeze_fee));

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
            return array("message" => "Withdrawal not allowed due to insufficient balance available", "code" => "0441", "available" => $total_available);
        }

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


        $checkLimitsDetail = LimitDetailUser::where("client_id",$client_id)->where("user_id",$user_id)->first();
        if(isset($checkLimitsDetail)){

            switch($method_payment){
                case"pix":
                    if($amount_solicitation > $checkLimitsDetail->max_limit_transaction_pix){
                        // Amount request greater than requested
                        return array("message" => "Unable to perform transaction. Maximum limit per transaction R$ ".$this->doubletostr($checkLimitsDetail->max_limit_transaction_pix), "reason" => "Illegal Conditions", "code" => "0503");
                        exit();
                    }

                    $totalDepositpix = Transactions::where("client_id",$client_id)
                        ->where("user_id",$user_id)
                        ->where("type_transaction","deposit")
                        ->where("method_transaction","pix")
                        ->where("status","!=","canceled")
                        ->whereBetween("solicitation_date",[date("Y-m-d 00:00:00"),date("Y-m-d 23:59:59")])
                        ->sum("amount_solicitation");

                    if(($totalDepositpix + $amount_solicitation) > $checkLimitsDetail->max_limit_day_pix){
                        // Amount request greater than requested
                        return array("message" => "Unable to perform transaction. Maximum limit per day R$ ".$this->doubletostr($checkLimitsDetail->max_limit_day_pix), "reason" => "Illegal Conditions", "code" => "0503");
                        exit();
                    }

                    return ["return" => "newRule"];

                break;
                case"invoice":
                    if($amount_solicitation > $checkLimitsDetail->max_limit_transaction_invoice){
                        // Order_id already exists
                        return array("message" => "Unable to perform transaction. Maximum limit per transaction R$ ".$this->doubletostr($checkLimitsDetail->max_limit_transaction_invoice), "reason" => "Illegal Conditions", "code" => "0503");
                        exit();
                    }

                    $totalDepositinvoice = Transactions::where("client_id",$client_id)
                        ->where("user_id",$user_id)
                        ->where("type_transaction","deposit")
                        ->where("method_transaction","invoice")
                        ->where("status","!=","canceled")
                        ->whereBetween("solicitation_date",[date("Y-m-d 00:00:00"),date("Y-m-d 23:59:59")])
                        ->sum("amount_solicitation");

                    if(($totalDepositinvoice + $amount_solicitation) > $checkLimitsDetail->max_limit_day_invoice){
                        // Amount request greater than requested
                        return array("message" => "Unable to perform transaction. Maximum limit per day R$ ".$this->doubletostr($checkLimitsDetail->max_limit_day_invoice), "reason" => "Illegal Conditions", "code" => "0503");
                        exit();
                    }

                    return ["return" => "newRule"];
                break;
                case"automatic_checking":
                    if($amount_solicitation > $checkLimitsDetail->max_limit_transaction_shop){
                        // Order_id already exists
                        return array("message" => "Unable to perform transaction. Maximum limit per transaction R$ ".$this->doubletostr($checkLimitsDetail->max_limit_transaction_shop), "reason" => "Illegal Conditions", "code" => "0503");
                        exit();
                    }

                    $totalDepositshop = Transactions::where("client_id",$client_id)
                        ->where("user_id",$user_id)
                        ->where("type_transaction","deposit")
                        ->where("method_transaction","automatic_checking")
                        ->where("status","!=","canceled")
                        ->whereBetween("solicitation_date",[date("Y-m-d 00:00:00"),date("Y-m-d 23:59:59")])
                        ->sum("amount_solicitation");

                    if(($totalDepositshop + $amount_solicitation) > $checkLimitsDetail->max_limit_day_shop){
                        // Amount request greater than requested
                        return array("message" => "Unable to perform transaction. Maximum limit per day R$ ".$this->doubletostr($checkLimitsDetail->max_limit_day_shop), "reason" => "Illegal Conditions", "code" => "0503");
                        exit();
                    }

                    return ["return" => "newRule"];
                break;
                case"credit_card":
                    if($amount_solicitation > $checkLimitsDetail->max_limit_transaction_credit_card){
                        // Order_id already exists
                        return array("message" => "Unable to perform transaction. Maximum limit per transaction R$ ".$this->doubletostr($checkLimitsDetail->max_limit_transaction_credit_card), "reason" => "Illegal Conditions", "code" => "0503");
                        exit();
                    }

                    $totalDepositcredit_card = Transactions::where("client_id",$client_id)
                        ->where("user_id",$user_id)
                        ->where("type_transaction","deposit")
                        ->where("method_transaction","credit_card")
                        ->where("status","!=","canceled")
                        ->whereBetween("solicitation_date",[date("Y-m-d 00:00:00"),date("Y-m-d 23:59:59")])
                        ->sum("amount_solicitation");

                    if(($totalDepositcredit_card + $amount_solicitation) > $checkLimitsDetail->max_limit_day_credit_card){
                        // Amount request greater than requested
                        return array("message" => "Unable to perform transaction. Maximum limit per day R$ ".$this->doubletostr($checkLimitsDetail->max_limit_day_credit_card), "reason" => "Illegal Conditions", "code" => "0503");
                        exit();
                    }

                    return ["return" => "newRule"];
                break;
            }

        }

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
                        return array("message" => "We were unable to complete your transaction due to your dayli limit", "reason" => "Illegal Conditions", "code" => "0503");
                        exit();

                    }elseif(($deposits_today + $amount_solicitation) > $max_deposit){

                        // Order_id already exists
                        // return array("message" => "We were unable to complete your transaction due to your daily limit of R$ ".$this->doubletostr($max_deposit).". You still have R$ ".$this->doubletostr($sub_rest)." available to deposit today", "reason" => "Illegal Conditions", "code" => "0503");
                        return array("message" => "We were unable to complete your transaction due to your dayli limit", "reason" => "Illegal Conditions", "code" => "0503");
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
                        return array("message" => "We were unable to complete your transaction due to your dayli limit", "reason" => "Illegal Conditions", "code" => "0503");
                        exit();

                    }elseif(($deposits_today + $amount_solicitation) > $max_deposit){

                        // Order_id already exists
                        // return array("message" => "We were unable to complete your transaction due to your daily limit of R$ ".$this->doubletostr($max_deposit).". You still have R$ ".$this->doubletostr($sub_rest)." available to deposit today", "reason" => "Illegal Conditions", "code" => "0503");
                        return array("message" => "We were unable to complete your transaction due to your dayli limit", "reason" => "Illegal Conditions", "code" => "0503");
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
            $max_deposit_shop = $taxes->max_shop;
            $max_deposit_cc = $taxes->max_credit_card;
            $max_deposit_debit = $taxes->max_debit_card;
            $max_deposit_ame = $taxes->max_ame;
            $max_deposit_pix = $taxes->max_pix;

            $min_deposit_invoice = $taxes->min_boleto;
            $min_deposit_shop = $taxes->min_shop;
            $min_deposit_cc = $taxes->min_credit_card;
            $min_deposit_debit = $taxes->min_debit_card;
            $min_deposit_ame = $taxes->min_ame;
            $min_deposit_pix = $taxes->min_pix;

            switch($method_payment){
                case"invoice": $max_deposit = $max_deposit_invoice; $min_deposit = $min_deposit_invoice; break;
                case"automatic_cheking": $max_deposit = $max_deposit_shop; $min_deposit = $min_deposit_shop; break;
                case"credit_card": $max_deposit = $max_deposit_cc; $min_deposit = $min_deposit_cc; break;
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
                $amount = $FunctionsController->$this->strtodouble($amount);
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
                "transactionChangedCallbackUrl"=> "https://xdash.FastPayments.com/api/webhook_ame",
                "paymentOnce"=> "true",
                "items" => array("0" => array(
                    "description"=> "Crédito em FastPayments",
                    "quantity"=> 1,
                    "amount"=> str_replace(".","",$amount)
                    )
                )
            )
        );

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

        $sql_consult = DB::table('transactions')->where("client_id","=",$client_id)->where("code","=","A4P".$pedido)->first();
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

}
