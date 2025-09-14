<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Models\{Clients,Keys,Transactions,Banks,RegisterUserMerchant,Blocklist};
use App\Http\Controllers\FunctionsAPIController;
use App\Http\Controllers\BBBoletoWebService;

class DepositController extends Controller
{
    //
    public function get(Request $request){

        $FunctionsAPIController = new FunctionsAPIController();

        $Authorization = $request->header('Token');

        $key = Keys::where("authorization","=",$Authorization)->first();
        $client = $key->client;

        $status = [];
        $st = explode(",",$request->status);
        foreach($st as $s){
            array_push($status,$s);
        }

        // $method = [];
        // $mt = explode(",",$request->method);
        // foreach($mt as $m){
        //     array_push($method,$m);
        // }

        $allTransactions = Transactions::where("client_id",$client->id)
            ->where("type_transaction","deposit");

        if(isset($request->first_date,$request->last_date)){
            $first_date = $request->first_date." 00:00:00";
            $last_date = $request->last_date." 23:59:59";

            $allTransactions->whereBetween("solicitation_date",[$first_date,$last_date]);
        }

        if(!empty($status[0])){
            $allTransactions->whereIn("status",$status);
        }

        // if(!empty($method[0])){
        //     $allTransactions->whereIn("method_transaction",$method);
        // }

        if(isset($request->order_id)){
            $allTransactions->where("order_id",$request->order_id);
        }

        $transactions = $allTransactions->get();

        $json_return = array("orders" => array());

        $bank_var = "";

        if(isset($transactions)){
            foreach($transactions as $row){

                $bank = Banks::where("id",$row->id_bank)->first();

                $bank_name = $bank->name;
                $holder = $bank->holder;
                $agency = $bank->agency;
                $type_account = $bank->type_account;
                $account = $bank->account;

                $method = $row->method_transaction;
                $code_bank = $row->bank->code;
                $link_invoice = "";

                if($method == "credit_card"){
                    $link_invoice = $row->link_callback_bank;
                }elseif($method == "invoice"){

                    switch($code_bank){
                        case"033": $bank_var = "santander"; break;
                        case"341": $bank_var = "itau"; break;
                        case"001": $bank_var = "bb"; break;
                        case"237": $bank_var = "bradesco"; break;
                    }

                    $link_invoice = "https://admin.fastpayments.com.br/get-invoice-".$bank_var."/".$key->authorization."/".$row->order_id;

                }elseif($method == "automatic_checking"){

                    switch($code_bank){
                        case"341": $bank_var = "itau"; break;
                        case"001": $bank_var = "bb"; break;
                        case"237": $bank_var = "bradesco"; break;
                        case"033": $bank_var = "santander"; break;
                    }

                    $link_invoice = "https://admin.fastpayments.com.br/get-shop-".$bank_var."/".$key->authorization."/".$row->order_id;
                }elseif($method == "ame_digital"){
                    $link_invoice = $row->link_callback_bank;
                    $deepLink = $row->deep_link;
                }elseif($method == "pix"){
                    $link_invoice = "https://admin.fastpayments.com.br/qrcodepix/".$row->id."/".$row->order_id."/200x200";
                    $deepLink = $row->data_bank;
                }

                $receipt = "";

                if($row->status == "refund"){
                    $json_return['orders'][] = array(
                    "id" => $row->id,
                    "fast_id" => $row->id,
                    "order_id" => $row->order_id,
                    "refund_date_clear" => $row->refund_date,
                    "refund_date" => $FunctionsAPIController->datetostr(substr($row->refund_date,0,10))." ".substr($row->refund_date,11,8),
                    "due_date" => $FunctionsAPIController->datetostr(substr($row->due_date,0,10)),
                    "code_identify" => $row->code,
                    "provider_reference" => $row->id,
                    "amount_solicitation" => $row->amount_solicitation,
                    "amount_confirmed" => $row->amount_confirmed,
                    "code_bank" => $row->code_bank,
                    "bank_name" => $bank_name,
                    "holder" => $holder,
                    "agency" => $agency,
                    "type_account" => $type_account,
                    "account" => $account,
                    "status" => $row->status,
                    "link_invoice" => $link_invoice,
                    "receipt" => $receipt,
                    "comission" => $row->comission,
                    );
                }elseif($row->status == "pending"){
                    $json_return['orders'][] = array(
                    "id" => $row->id,
                    "fast_id" => $row->id,
                    "order_id" => $row->order_id,
                    "solicitation_date_clear" => $row->solicitation_date,
                    "solicitation_date" => $FunctionsAPIController->datetostr(substr($row->solicitation_date,0,10))." ".substr($row->solicitation_date,11,8),
                    "due_date" => $FunctionsAPIController->datetostr(substr($row->due_date,0,10)),
                    "code_identify" => $row->code,
                    "provider_reference" => $row->id,
                    "amount_solicitation" => $row->amount_solicitation,
                    "amount_confirmed" => $row->amount_confirmed,
                    "code_bank" => $row->code_bank,
                    "bank_name" => $bank_name,
                    "holder" => $holder,
                    "agency" => $agency,
                    "type_account" => $type_account,
                    "account" => $account,
                    "status" => $row->status,
                    "link_invoice" => $link_invoice,
                    "receipt" => $receipt
                    );
                }elseif($row->status == "confirmed"){
                    $json_return['orders'][] = array(
                    "id" => $row->id,
                    "fast_id" => $row->id,
                    "order_id" => $row->order_id,
                    "paid_date_clear" => $row->paid_date,
                    "paid_date" => $FunctionsAPIController->datetostr(substr($row->paid_date,0,10))." ".substr($row->paid_date,11,8),
                    "due_date" => $FunctionsAPIController->datetostr(substr($row->due_date,0,10)),
                    "code_identify" => $row->code,
                    "provider_reference" => $row->id,
                    "amount_solicitation" => $row->amount_solicitation,
                    "amount_confirmed" => $row->amount_confirmed,
                    "code_bank" => $row->code_bank,
                    "bank_name" => $bank_name,
                    "holder" => $holder,
                    "agency" => $agency,
                    "type_account" => $type_account,
                    "account" => $account,
                    "status" => $row->status,
                    "link_invoice" => $link_invoice,
                    "receipt" => $receipt,
                    "comission" => $row->comission,
                    "disponibilization_date" => date("d/m/Y 00:00:00",strtotime($row->disponibilization_date))
                    );
                }elseif($row->status == "chargeback"){
                    $json_return['orders'][] = array(
                    "id" => $row->id,
                    "fast_id" => $row->id,
                    "order_id" => $row->order_id,
                    "chargeback_date_clear" => $row->chargeback_date,
                    "chargeback_date" => $FunctionsAPIController->datetostr(substr($row->chargeback_date,0,10))." ".substr($row->chargeback_date,11,8),
                    "due_date" => $FunctionsAPIController->datetostr(substr($row->due_date,0,10)),
                    "code_identify" => $row->code,
                    "provider_reference" => $row->id,
                    "amount_solicitation" => $row->amount_solicitation,
                    "amount_confirmed" => $row->amount_confirmed,
                    "code_bank" => $row->code_bank,
                    "bank_name" => $bank_name,
                    "holder" => $holder,
                    "agency" => $agency,
                    "type_account" => $type_account,
                    "account" => $account,
                    "status" => $row->status,
                    "link_invoice" => $link_invoice,
                    "receipt" => $receipt,
                    "comission" => $row->comission,
                    );
                }elseif($row->status == "canceled"){
                    $json_return['orders'][] = array(
                    "id" => $row->id,
                    "fast_id" => $row->id,
                    "order_id" => $row->order_id,
                    "cancel_date_clear" => $row->cancel_date,
                    "cancel_date" => $FunctionsAPIController->datetostr(substr($row->cancel_date,0,10))." ".substr($row->cancel_date,11,8),
                    "due_date" => $FunctionsAPIController->datetostr(substr($row->due_date,0,10)),
                    "code_identify" => $row->code,
                    "provider_reference" => $row->id,
                    "amount_solicitation" => $row->amount_solicitation,
                    "amount_confirmed" => $row->amount_confirmed,
                    "code_bank" => $row->code_bank,
                    "bank_name" => $bank_name,
                    "holder" => $holder,
                    "agency" => $agency,
                    "type_account" => $type_account,
                    "account" => $account,
                    "status" => $row->status,
                    "link_invoice" => $link_invoice,
                    "receipt" => $receipt,
                    "comission" => $row->comission,
                    );
                }

            }

            return $json_return;
        }else{
            return ["message" => "empty"];
        }

        return response()->json($json_return);

    }

    public function create(Request $request){

        $FunctionsAPIController = new FunctionsAPIController();

        $method = $request->method;

        try{

        switch($method){
            case"invoice": $return = $this->toInvoice($request);  break;
            case"pix": $return = $this->toPIX($request);  break;
            case"ted": $return = $this->toTED($request);  break;
            case"creditcard": $return = $this->toCREDIT($request);  break;
        }

            return $return;

        }catch(Exception $e){

            $path_name = "fastlogs-pix-timeout-".date("Y-m-d");

            if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
            }

            $payload = [
                "date" => date("Y-m-d H:i:s"),
                "request" => $request->all(),
                "http" => "404",
                "message" => "Erro ao criar transação pix, tente novamente..."
            ];

            $this->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($payload));

            $ar = array(
                "code" => "558",
                "message" => "Erro ao criar transação pix, tente novamente...",
            );

            return response()->json($ar,402);

        }

    }

    public function delete(Request $request){

        $FunctionsAPIController = new FunctionsAPIController();

        $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/CancelDeposit.txt",json_encode($request->getContent()));

        $Token = $request->header('Token');
        $authentication = Keys::where("authorization","=",$Token)->first();
        $client = $authentication->client;

        $order_id = $request->order_id;

        $transaction = Transactions::where("client_id",$client->id)->where("order_id",$order_id)->first();

        if($transaction->type_transaction == "deposit"){
            $url_callback = $client->key->url_callback;
        }elseif($transaction->type_transaction == "withdraw"){
            $url_callback = $client->key->url_callback_withdraw;
        }

        if(empty($transaction)){
            // Error, Transaction not found
            $json_return = array("message" => "Transaction not found", "reason" => "Illegal Conditions");
            return response()->json($json_return,401,['HTTP/1.0' => 'Unauthorized']);
        }

        switch($transaction->status){
            case"confirmed":
                $json_return = array("message" => "Unable to cancel this transaction as it is already confirmed", "reason" => "Illegal Conditions");
                return response()->json($json_return,401,['HTTP/1.0' => 'Unauthorized']);
            break;
            case"canceled":
                $json_return = array("message" => "This transaction has already been canceled previously", "reason" => "Illegal Conditions");
                return response()->json($json_return,401,['HTTP/1.0' => 'Unauthorized']);
            break;
            case"refund":
                $json_return = array("message" => "Unable to cancel this transaction as it is already refund", "reason" => "Illegal Conditions");
                return response()->json($json_return,401,['HTTP/1.0' => 'Unauthorized']);
            break;
            case"chargeback":
                $json_return = array("message" => "Unable to cancel this transaction as it is already chargeback", "reason" => "Illegal Conditions");
                return response()->json($json_return,401,['HTTP/1.0' => 'Unauthorized']);
            break;
        }

        $date_cancel = date("Y-m-d H:i:s");
        $bank = $transaction->bank;

        if($bank->code == "222"){

            \App\Jobs\CancelDepositPendingShipay::dispatch($transaction->id)->delay(now()->addSeconds('5'));

        }

        DB::beginTransaction();
        try{

            $transaction->update([
                "status" => "canceled",
                "canceled_manual" => "1",
                "cancel_date" => $date_cancel,
                "final_date" => $date_cancel,
            ]);

            DB::commit();

            // set post fields
            $post = [
                "order_id" => $transaction['order_id'],
                "user_id" => $transaction['user_id'],
                "solicitation_date" => $transaction['solicitation_date'],
                "cancel_date" => $date_cancel,
                "code_identify" => $transaction['code'],
                "amount_solicitation" => $transaction['amount_solicitation'],
                "status" => "canceled"
            ];

            $post_var = [
                "date_send_callback" => date("Y-m-d H:i:s",strtotime("-3 hours")),
                "type" => "cancel_manual",
                "order_id" => $transaction['order_id'],
                "user_id" => $transaction['user_id'],
                "solicitation_date" => $transaction['solicitation_date'],
                "cancel_date" => $date_cancel,
                "code_identify" => $transaction['code'],
                "amount_solicitation" => $transaction['amount_solicitation'],
                "status" => "canceled_manually"
            ];

            $post_var = json_encode($post_var);

            $fp = fopen('register-cancel-transaction-manual.txt', 'a');
            fwrite($fp, $post_var."\n");
            fclose($fp);

            $post_field = json_encode($post);

            $ch2 = curl_init("https://webhook.site/1ccde4e1-a73b-4182-a47e-8a5b95a5ab31");
            curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'Token:'.$client->key->authorization));
            curl_setopt($ch2, CURLOPT_POSTFIELDS, $post_field);

            // execute!
            $response2 = curl_exec($ch2);
            $http_status2  = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

            // close the connection, release resources used
            curl_close($ch2);

            $ch = curl_init($url_callback);
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
                DB::beginTransaction();
                try{

                    $transaction->update([
                        "confirmation_callback" => "1"
                    ]);

                    DB::commit();

                }catch(exception $e){
                    DB::rollback();
                }
            }

            return response()->json($post);

        }catch(exception $e){
            DB::rollback();
        }

    }

    public function toInvoice($request){

        $FunctionsAPIController = new FunctionsAPIController();

        $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/CreateDepositInvoice.txt",json_encode($request->getContent()));

        $return;
        $client = Clients::where("id","=",$request->client)->first();
        $authentication = Keys::where("authorization","=",$request->Authorization)->first();

        $user_document = $FunctionsAPIController->clearCPF($request->user_document);

        if($request->amount < $client->tax->min_boleto){
            $json_return = array("message" => "Minimum amount R$ ".number_format($client->tax->min_boleto,2,",","."), "reason" => "Illegal Conditions");
            return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
            exit();
        }
        if($request->amount > $client->tax->max_boleto){
            $json_return = array("message" => "Maximum amount R$ ".number_format($client->tax->max_boleto,2,",","."), "reason" => "Illegal Conditions");
            return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
            exit();
        }

        $check_bank = $client->bankInvoice->id;

        if($check_bank == "9999"){

            $json_return = array("message" => "Method Payment Freeze", "reason" => "Illegal Conditions", "code" => "5589");
            return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
            exit();

        }

        // Check user rules
        $check_rules = $FunctionsAPIController->check_user_rules($client->id,$request->user_id,$user_document,$request->amount,"invoice");

        if(!isset($check_rules['return'])){
            return response()->json($check_rules,422);
        }

        $amount = $request->amount;
        $subs = substr($amount,-3,1);
        if($subs == "."){
            $amount = $amount;
            $amount_fiat_boleto = number_format($amount,2,",","");
        }elseif($subs == ","){
            $amount = $FunctionsAPIController->strtodouble($amount);
            $amount_fiat_boleto = number_format($amount,2,",","");
        }else{
            $amount = number_format($amount,2,".","");
            $amount_fiat_boleto = number_format($amount,2,",","");
        }

        $cont_name = strlen($request->user_name);
        if($cont_name > 40){
            $nus = explode(" ",$request->user_name);
            $name_user = $nus[0]." ".$nus[1];
        }else{
            $name_user = $request->user_name;
        }

        $data_fake = $FunctionsAPIController->get_random_address();

        $user_address = $data_fake['endereco'];
        $user_district = $data_fake['bairro'];
        $user_city = $data_fake['cidade'];
        $user_uf = $data_fake['estado'];
        $user_cep = str_replace("-","",$data_fake['cep']);
        $count_cep = strlen($user_cep);

        switch($count_cep){
            case"5": $user_cep = "000".$user_cep; break;
            case"6": $user_cep = "00".$user_cep; break;
            case"7": $user_cep = "0".$user_cep; break;
        }

        // $days_safe_boleto = $client->days_safe_boleto;
        $days_safe_boleto = 5;

        $pedido = $FunctionsAPIController->gera_pedido($client->id);

        switch($client->bankInvoice->code){
            case"001":
                 // Cria objeto de BBBoletoWebService para consumo de serviço
                $bb = new BBBoletoWebService('eyJpZCI6IiIsImNvZGlnb1B1YmxpY2Fkb3IiOjAsImNvZGlnb1NvZnR3YXJlIjoxMTA5OCwic2VxdWVuY2lhbEluc3RhbGFjYW8iOjF9', 'eyJpZCI6ImRmNzNkYjEtMWJjNC00ZTRjLTgwNWUtNGE0NTVkYzY0NjRjOTJkYzg2NyIsImNvZGlnb1B1YmxpY2Fkb3IiOjAsImNvZGlnb1NvZnR3YXJlIjoxMTA5OCwic2VxdWVuY2lhbEluc3RhbGFjYW8iOjEsInNlcXVlbmNpYWxDcmVkZW5jaWFsIjoxLCJhbWJpZW50ZSI6InByb2R1Y2FvIiwiaWF0IjoxNTgxOTc0ODE5OTc4fQ');

                $dados = "";
                $data_emissao = date("d.m.Y",strtotime("-3 hours"));
                $data_vencimento = date("d.m.Y",strtotime("+".$days_safe_boleto." days"));
                $valor_boleto = $amount_fiat_boleto;
                $cpf = $FunctionsAPIController->limpaCPF_CNPJ($request->user_document);
                $nome_usuario = $FunctionsAPIController->limit_caracter($name_user,15);
                $endereco_usuario = $FunctionsAPIController->limit_caracter($user_address,15);
                $bairro_usuario = $FunctionsAPIController->limit_caracter($user_district,15);
                $cidade_usuario = $FunctionsAPIController->limit_caracter($user_city,15);
                $uf_usuario = $user_uf;
                $cep_usuario = $user_cep;

                // Convenio shopBB
                $id_convenio_bb_boleto = $client->bankBB->id_convenio_bb_boleto;

                $params_array = array(
                    'client_id' => $client->id,
                    'user_id' => $request->user_id,
                    'pedido' => $pedido,
                    'order_id' => $request->order_id,
                    'authorization' => $authentication->authorization,
                    'number_convenio' => $id_convenio_bb_boleto,
                    'data_emissao' => $data_emissao,
                    'data_vencimento' => $data_vencimento,
                    'amount' => $amount,
                    'valor_boleto' => $valor_boleto,
                    'cpf' => $cpf,
                    'nome_usuario' => $nome_usuario,
                    'documento_usuario' => $cpf,
                    'endereco_usuario' => $endereco_usuario,
                    'bairro_usuario' => $bairro_usuario,
                    'cidade_usuario' => $cidade_usuario,
                    'uf_usuario' => $uf_usuario,
                    'cep_usuario' => $cep_usuario
                );

                $return = $FunctionsAPIController->register_boleto_bb($params_array);
            break;

            case"218":

                $dados = "";
                $data_emissao = date("Y-m-d");
                $data_vencimento = date("Y-m-d",strtotime("+".$days_safe_boleto." days"));
                $data_vencimento_bs2 = date("Y-m-d",strtotime("+".$days_safe_boleto." days"))."T00:00:00.334Z";
                $valor_boleto = $amount_fiat_boleto;
                $cpf = $FunctionsAPIController->limpaCPF_CNPJ($request->user_document);
                $nome_usuario = $FunctionsAPIController->limit_caracter($name_user,15);
                $endereco_usuario = $FunctionsAPIController->limit_caracter($user_address,15);
                $bairro_usuario = $FunctionsAPIController->limit_caracter($user_district,15);
                $cidade_usuario = $FunctionsAPIController->limit_caracter($user_city,15);
                $uf_usuario = $user_uf;
                $cep_usuario = $user_cep;

                // Acesso BS2
                $username_bs2 = $client->bankInvoice->username_bs2;
                $password_bs2 = $client->bankInvoice->password_bs2;
                $client_id_bs2 = $client->bankInvoice->client_id_bs2;
                $client_secret_bs2 = $client->bankInvoice->client_secret_bs2;

                $params_array = array(
                    'client_id' => $client->id,
                    'user_id' => $request->user_id,
                    'pedido' => $pedido,
                    'order_id' => $request->order_id,
                    'authorization' => $authentication->authorization,
                    'data_emissao' => $data_emissao,
                    'data_vencimento' => $data_vencimento,
                    'data_vencimento_bs2' => $data_vencimento_bs2,
                    'amount' => $amount,
                    'valor_boleto' => $valor_boleto,
                    'cpf' => $cpf,
                    'nome_usuario' => $nome_usuario,
                    'documento_usuario' => $cpf,
                    'endereco_usuario' => $endereco_usuario,
                    'bairro_usuario' => $bairro_usuario,
                    'cidade_usuario' => $cidade_usuario,
                    'uf_usuario' => $uf_usuario,
                    'cep_usuario' => $cep_usuario,
                    'numero_endereco' => mt_rand(1,1999),
                    'username_bs2' => $username_bs2,
                    'password_bs2' => $password_bs2,
                    'client_id_bs2' => $client_id_bs2,
                    'client_secret_bs2' => $client_secret_bs2,
                );

                $return = $FunctionsAPIController->register_boleto_bs2($params_array);

            break;

            case"461":

                $dados = "";
                $data_emissao = date("Y-m-d");
                $data_vencimento = date("Y-m-d",strtotime("+".$days_safe_boleto." days"));
                $data_vencimento_bs2 = date("Y-m-d",strtotime("+".$days_safe_boleto." days"))."T00:00:00.334Z";
                $valor_boleto = $amount_fiat_boleto;
                $cpf = $FunctionsAPIController->limpaCPF_CNPJ($request->user_document);
                $nome_usuario = $FunctionsAPIController->limit_caracter($name_user,15);
                $endereco_usuario = $FunctionsAPIController->limit_caracter($user_address,15);
                $bairro_usuario = $FunctionsAPIController->limit_caracter($user_district,15);
                $cidade_usuario = $FunctionsAPIController->limit_caracter($user_city,15);
                $uf_usuario = $user_uf;
                $cep_usuario = $user_cep;

                // Acesso Gerencianet
                $access_token_asaas = $client->bankInvoice->access_token_asaas;

                $user_asaas_merchant = RegisterUserMerchant::where("cpfCnpj",$cpf)->first();

                $number_address = mt_rand(1,1999);

                if($user_asaas_merchant !== null){
                    $customer_id = $user_asaas_merchant->customer_id;
                }else{

                    $user_data = [
                        "name" => $nome_usuario,
                        "email" => "",
                        "phone" => "",
                        "mobilePhone" => "",
                        "cpfCnpj" => $cpf,
                        "postalCode" => $cep_usuario,
                        "address" => $endereco_usuario,
                        "addressNumber" => $number_address,
                        "complement" => "",
                        "province" => $bairro_usuario,
                        "externalReference" => "",
                        "notificationDisabled" => "",
                        "additionalEmails" => "",
                        "municipalInscription" => "",
                        "stateInscription" => "",
                        "observations" => "",
                    ];

                    $customer_id = $FunctionsAPIController->registerUserAsaas($user_data,$access_token_asaas);

                }

                $params_array = array(
                    'customer_id' => $customer_id,
                    'client_id' => $client->id,
                    'user_id' => $request->user_id,
                    'pedido' => $pedido,
                    'order_id' => $request->order_id,
                    'authorization' => $authentication->authorization,
                    'data_emissao' => $data_emissao,
                    'data_vencimento' => $data_vencimento,
                    'data_vencimento_bs2' => $data_vencimento_bs2,
                    'amount' => $amount,
                    'valor_boleto' => $valor_boleto,
                    'cpf' => $cpf,
                    'nome_usuario' => $nome_usuario,
                    'documento_usuario' => $cpf,
                    'endereco_usuario' => $endereco_usuario,
                    'bairro_usuario' => $bairro_usuario,
                    'cidade_usuario' => $cidade_usuario,
                    'uf_usuario' => $uf_usuario,
                    'cep_usuario' => $cep_usuario,
                    'numero_endereco' => $number_address,
                    'access_token_asaas' => $access_token_asaas,
                );

                $return = $FunctionsAPIController->register_boleto_asaas($params_array);

            break;

            case"223":

                // Acesso Gerencianet
                $paghiper_api = $client->bankInvoice->paghiper_api;

                $params_array = array(
                    'client_id' => $client->id,
                    'user_id' => $request->user_id,
                    'pedido' => $pedido,
                    'order_id' => $request->order_id,
                    'authorization' => $authentication->authorization,
                    'data_emissao' => $data_emissao,
                    'data_vencimento' => $data_vencimento,
                    'data_vencimento_bs2' => $data_vencimento_bs2,
                    'amount' => $amount,
                    'valor_pix' => $valor_pix,
                    'cpf' => $cpf,
                    'nome_usuario' => $nome_usuario,
                    'documento_usuario' => $cpf,
                    'endereco_usuario' => $endereco_usuario,
                    'bairro_usuario' => $bairro_usuario,
                    'cidade_usuario' => $cidade_usuario,
                    'uf_usuario' => $uf_usuario,
                    'cep_usuario' => $cep_usuario,
                    'numero_endereco' => mt_rand(1,1999),
                    'paghiper_api' => $paghiper_api,
                );

                $return = $FunctionsAPIController->createTransactionINVOICEPagHiper($params_array);

            break;

            default:
                // Error, Client not found
                $json_return = array("message" => "Bank code is not valid from type_request 'invoice'");
                return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
        }

        return $return;
    }

    public function toPIX($request){

        $FunctionsAPIController = new FunctionsAPIController();

        $request_raw = ["request" => $request->all()];

        $path_name = "deposit-pix-request-raw-".date("Y-m-d");

        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
        }

        $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($request_raw));

        $return;
        $client = Clients::where("id","=",$request->client)->first();
        $authentication = $client->key;

        $user_document = $FunctionsAPIController->clearCPF($request->user_document);

        $amount = $request->amount;
        $subs = substr($amount,-3,1);
        if($subs == "."){
            $amount = $amount;
            $amount_fiat_pix = number_format($amount,2,",","");
        }elseif($subs == ","){
            $amount = $FunctionsAPIController->strtodouble($amount);
            $amount_fiat_pix = number_format($amount,2,",","");
        }else{
            $amount = number_format($amount,2,".","");
            $amount_fiat_pix = number_format($amount,2,",","");
        }

        $check_rules = $FunctionsAPIController->check_user_rules($client->id,$request->user_id,$user_document,$request->amount,"pix");

        if(!isset($check_rules['return'])){

            $path_name = "deposit-pix-rules-".date("Y-m-d");

            if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
            }

            $data_internal = [
                "request" => $request->all(),
                "return" => $check_rules
            ];

            $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($data_internal));

            return response()->json($check_rules,422);
        }


        $cont_name = strlen($request->user_name);
        if($cont_name == 0){
            $name_user = "NEXA PAY System";
        }elseif($cont_name > 40){
            $nus = explode(" ",$request->user_name);
            $name_user = $nus[0]." ".$nus[1];
        }else{
            $name_user = $request->user_name;
        }


        $data_fake = $FunctionsAPIController->get_random_address();

        $user_address = $data_fake['endereco'];
        $user_district = $data_fake['bairro'];
        $user_city = $data_fake['cidade'];
        $user_uf = $data_fake['estado'];
        $user_cep = str_replace("-","",$data_fake['cep']);
        $count_cep = strlen($user_cep);

        switch($count_cep){
            case"5": $user_cep = "000".$user_cep; break;
            case"6": $user_cep = "00".$user_cep; break;
            case"7": $user_cep = "0".$user_cep; break;
        }

        $days_safe_pix = $client->days_safe_pix;
        $days_safe_pix += 3;

        $dados = "";
        $data_emissao = date("Y-m-d");
        $data_vencimento = date("Y-m-d",strtotime("+".$days_safe_pix." days"));
        $data_vencimento_bs2 = date("Y-m-d",strtotime("+".$days_safe_pix." days"))."T00:00:00.334Z";
        $valor_pix = $amount_fiat_pix;
        $cpf = $FunctionsAPIController->limpaCPF_CNPJ($request->user_document);

        if($cpf == "45403734000150"){
            $cpf = "43892199876";
        }

        if($cpf == ""){
            $cpf = "40304036978";
        }

        $nome_usuario = $FunctionsAPIController->remove_accents($FunctionsAPIController->limit_caracter($name_user,15));
        $endereco_usuario = $FunctionsAPIController->limit_caracter($user_address,15);
        $bairro_usuario = $FunctionsAPIController->limit_caracter($user_district,15);
        $cidade_usuario = $FunctionsAPIController->limit_caracter($user_city,15);
        $uf_usuario = $user_uf;
        $cep_usuario = $user_cep;
        $bankPixCode = $client->bankPix->code;
        $bankPix = $client->bankPix;

        if($client->method_pix == "estatico"){
            $pedido = $bankPix->prefix.$FunctionsAPIController->gera_pedido($client->id);
        }else{
            $pedido = $bankPix->prefix.$FunctionsAPIController->gera_pedido_pix($client->id);
        }

        if(isset($request->pixkey)){
            $pixkey = $request->pixkey;
        }else{
            $pixkey = "";
        }

        if(isset($request->provider_reference)){
            $provider_reference = $request->provider_reference;
        }else{
            $provider_reference = "";
        }

        if(isset($request->expiration)){
            $time_default = $request->expiration;

            if(is_numeric($time_default)){
                // Declare expiration

                $expiration = $time_default;
            }else{
                // Declare and define two dates
                $date1 = strtotime(date("Y-m-d H:i:s"));
                $date2 = strtotime(date("Y-m-d H:i:s",strtotime($time_default)));

                $seconds = ($date2 - $date1);

                $expiration = $seconds;
            }
        }else{
            $expiration = 900;
        }

        switch($bankPixCode){
            case"218":

                // Acesso BS2
                $client_id_bs2 = $client->bankPix->client_id_bs2;
                $client_secret_bs2 = $client->bankPix->client_secret_bs2;

                $params_array = array(
                    'client_id' => $client->id,
                    'user_id' => $request->user_id,
                    'pedido' => $pedido,
                    'order_id' => $request->order_id,
                    'authorization' => $authentication->authorization,
                    'data_emissao' => $data_emissao,
                    'data_vencimento' => $data_vencimento,
                    'data_vencimento_bs2' => $data_vencimento_bs2,
                    'amount' => $amount,
                    'valor_pix' => $valor_pix,
                    'cpf' => $cpf,
                    'nome_usuario' => $nome_usuario,
                    'documento_usuario' => $cpf,
                    'endereco_usuario' => $endereco_usuario,
                    'bairro_usuario' => $bairro_usuario,
                    'cidade_usuario' => $cidade_usuario,
                    'uf_usuario' => $uf_usuario,
                    'cep_usuario' => $cep_usuario,
                    'numero_endereco' => mt_rand(1,1999),
                    'username_bs2' => "",
                    'password_bs2' => "",
                    'client_id_bs2' => $client_id_bs2,
                    'client_secret_bs2' => $client_secret_bs2,
                    'provider_reference' => $provider_reference
                );

                $return = $FunctionsAPIController->createTransactionPIX($params_array);

            break;

            case"219":

                // Acesso Genial
                $login_genial = $client->bankPix->login_genial;
                $pass_genial = $client->bankPix->pass_genial;

                $params_array = array(
                    'client_id' => $client->id,
                    'user_id' => $request->user_id,
                    'pedido' => $pedido,
                    'order_id' => $request->order_id,
                    'authorization' => $authentication->authorization,
                    'data_emissao' => $data_emissao,
                    'data_vencimento' => $data_vencimento,
                    'data_vencimento_bs2' => $data_vencimento_bs2,
                    'amount' => $amount,
                    'valor_pix' => $valor_pix,
                    'cpf' => $cpf,
                    'nome_usuario' => $nome_usuario,
                    'documento_usuario' => $cpf,
                    'endereco_usuario' => $endereco_usuario,
                    'bairro_usuario' => $bairro_usuario,
                    'cidade_usuario' => $cidade_usuario,
                    'uf_usuario' => $uf_usuario,
                    'cep_usuario' => $cep_usuario,
                    'numero_endereco' => mt_rand(1,1999),
                    'username_bs2' => "",
                    'password_bs2' => "",
                    'login_genial' => $login_genial,
                    'pass_genial' => $pass_genial,
                    'provider_reference' => $provider_reference
                );

                $return = $FunctionsAPIController->createTransactionPIXGenial($params_array);

            break;

            case"220":

                // Acesso Gerencianet
                $client_id_gerencianet = $client->bankPix->client_id_gerencianet;
                $password_gerencianet = $client->bankPix->password_gerencianet;
                $path_gerencianet = $client->bankPix->path_cert_gerencianet;

                $params_array = array(
                    'client_id' => $client->id,
                    'user_id' => $request->user_id,
                    'pedido' => $pedido,
                    'order_id' => $request->order_id,
                    'authorization' => $authentication->authorization,
                    'data_emissao' => $data_emissao,
                    'data_vencimento' => $data_vencimento,
                    'data_vencimento_bs2' => $data_vencimento_bs2,
                    'amount' => $amount,
                    'valor_pix' => $valor_pix,
                    'cpf' => $cpf,
                    'nome_usuario' => $nome_usuario,
                    'documento_usuario' => $cpf,
                    'endereco_usuario' => $endereco_usuario,
                    'bairro_usuario' => $bairro_usuario,
                    'cidade_usuario' => $cidade_usuario,
                    'uf_usuario' => $uf_usuario,
                    'cep_usuario' => $cep_usuario,
                    'numero_endereco' => mt_rand(1,1999),
                    'username_bs2' => "",
                    'password_bs2' => "",
                    'client_id_gerencianet' => $client_id_gerencianet,
                    'password_gerencianet' => $password_gerencianet,
                    'path_gerencianet' => $path_gerencianet,
                    'provider_reference' => $provider_reference
                );

                $return = $FunctionsAPIController->createTransactionPIXGerencianet($params_array);

            break;

            case"221":
                // Acesso OpenPix
                $auth_openpix = $client->bankPix->auth_openpix;

                $params_array = array(
                    'client_id' => $client->id,
                    'user_id' => $request->user_id,
                    'pedido' => $pedido,
                    'order_id' => $request->order_id,
                    'authorization' => $authentication->authorization,
                    'data_emissao' => $data_emissao,
                    'data_vencimento' => $data_vencimento,
                    'data_vencimento_bs2' => $data_vencimento_bs2,
                    'amount' => $amount,
                    'valor_pix' => $valor_pix,
                    'cpf' => $cpf,
                    'nome_usuario' => $nome_usuario,
                    'documento_usuario' => $cpf,
                    'endereco_usuario' => $endereco_usuario,
                    'bairro_usuario' => $bairro_usuario,
                    'cidade_usuario' => $cidade_usuario,
                    'uf_usuario' => $uf_usuario,
                    'cep_usuario' => $cep_usuario,
                    'numero_endereco' => mt_rand(1,1999),
                    'auth_openpix' => $auth_openpix,
                    'provider_reference' => $provider_reference
                );

                $path_name = "deposit-pix-".date("Y-m-d");

                if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                    mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                }

                $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($params_array));

                $return = $FunctionsAPIController->createTransactionPIXOPENPIX($params_array);

            break;

            case"222":

                // Acesso BS2
                $client_id = $client->bankPix->shipay_client_id;
                $access_key = $client->bankPix->shipay_access_key;
                $secret_key = $client->bankPix->shipay_secret_key;
                $shipay_method = $client->bankPix->shipay_method;

                $bank_holder = $client->bankPix->holder;

                $params_array = array(
                    'client_id' => $client->id,
                    'user_id' => $request->user_id,
                    'pedido' => $pedido,
                    'order_id' => $request->order_id,
                    'authorization' => $authentication->authorization_deposit,
                    'data_emissao' => $data_emissao,
                    'data_vencimento' => $data_vencimento,
                    'amount' => $amount,
                    'valor_pix' => $valor_pix,
                    'cpf' => $cpf,
                    'nome_usuario' => $nome_usuario,
                    'documento_usuario' => $cpf,
                    'endereco_usuario' => $endereco_usuario,
                    'bairro_usuario' => $bairro_usuario,
                    'cidade_usuario' => $cidade_usuario,
                    'uf_usuario' => $uf_usuario,
                    'cep_usuario' => $cep_usuario,
                    'numero_endereco' => mt_rand(1,1999),
                    'shipay_client_id' => $client_id,
                    'shipay_access_key' => $access_key,
                    'shipay_secret_key' => $secret_key,
                    'shipay_method' => $shipay_method,
                    'bank_holder' => $bank_holder,
                    'provider_reference' => $provider_reference
                );

                $path_name = "deposit-pix-shipay-".date("Y-m-d");

                if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                    mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                }

                $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($params_array));

                $return = $FunctionsAPIController->createTransactionPIXSHIPAY($params_array);

            break;

            case"223":

                // Acesso Gerencianet
                $paghiper_api = $client->bankPix->paghiper_api;

                $params_array = array(
                    'client_id' => $client->id,
                    'user_id' => $request->user_id,
                    'pedido' => $pedido,
                    'order_id' => $request->order_id,
                    'authorization' => $authentication->authorization,
                    'data_emissao' => $data_emissao,
                    'data_vencimento' => $data_vencimento,
                    'data_vencimento_bs2' => $data_vencimento_bs2,
                    'amount' => $amount,
                    'valor_pix' => $valor_pix,
                    'cpf' => $cpf,
                    'nome_usuario' => $nome_usuario,
                    'documento_usuario' => $cpf,
                    'endereco_usuario' => $endereco_usuario,
                    'bairro_usuario' => $bairro_usuario,
                    'cidade_usuario' => $cidade_usuario,
                    'uf_usuario' => $uf_usuario,
                    'cep_usuario' => $cep_usuario,
                    'numero_endereco' => mt_rand(1,1999),
                    'paghiper_api' => $paghiper_api,
                    'provider_reference' => $provider_reference
                );

                $return = $FunctionsAPIController->createTransactionPIXPagHiper($params_array);

            break;

            case"461":

                // Acesso Gerencianet
                $access_token_asaas = $client->bankPix->access_token_asaas;

                $user_asaas_merchant = RegisterUserMerchant::where("cpfCnpj",$cpf)->first();

                $number_address = mt_rand(1,1999);

                if($user_asaas_merchant !== null){
                    $customer_id = $user_asaas_merchant->customer_id;
                }else{

                    $user_data = [
                        "name" => $nome_usuario,
                        "email" => "",
                        "phone" => "",
                        "mobilePhone" => "",
                        "cpfCnpj" => $cpf,
                        "postalCode" => $cep_usuario,
                        "address" => $endereco_usuario,
                        "addressNumber" => $number_address,
                        "complement" => "",
                        "province" => $bairro_usuario,
                        "externalReference" => "",
                        "notificationDisabled" => "",
                        "additionalEmails" => "",
                        "municipalInscription" => "",
                        "stateInscription" => "",
                        "observations" => "",
                        'provider_reference' => $provider_reference,
                    ];

                    $customer_id = $FunctionsAPIController->registerUserAsaas($user_data,$access_token_asaas);

                }

                $params_array = array(
                    'client_id' => $client->id,
                    'user_id' => $request->user_id,
                    'pedido' => $pedido,
                    'order_id' => $request->order_id,
                    'authorization' => $authentication->authorization,
                    'data_emissao' => $data_emissao,
                    'data_vencimento' => $data_vencimento,
                    'data_vencimento_bs2' => $data_vencimento_bs2,
                    'amount' => $amount,
                    'valor_pix' => $valor_pix,
                    'cpf' => $cpf,
                    'nome_usuario' => $nome_usuario,
                    'documento_usuario' => $cpf,
                    'endereco_usuario' => $endereco_usuario,
                    'bairro_usuario' => $bairro_usuario,
                    'cidade_usuario' => $cidade_usuario,
                    'uf_usuario' => $uf_usuario,
                    'cep_usuario' => $cep_usuario,
                    'numero_endereco' => $number_address,
                    'access_token_asaas' => $access_token_asaas,
                    'customer_id' => $customer_id,
                    'provider_reference' => $provider_reference
                );

                $return = $FunctionsAPIController->createTransactionPIXAsaas($params_array);

            break;

            case"587":
                // Acesso Celcoin
                $client_id_celcoin = $client->bankPix->client_id_celcoin;
                $client_secret_celcoin = $client->bankPix->client_secret_celcoin;
                $access_token_celcoin = $client->bankPix->access_token_celcoin;

                $params_array = array(
                    'client_id' => $client->id,
                    'user_id' => $request->user_id,
                    'pedido' => $pedido,
                    'order_id' => $request->order_id,
                    'authorization' => $authentication->authorization,
                    'data_emissao' => $data_emissao,
                    'data_vencimento' => $data_vencimento,
                    'data_vencimento_bs2' => $data_vencimento_bs2,
                    'amount' => $amount,
                    'valor_pix' => $valor_pix,
                    'cpf' => $cpf,
                    'nome_usuario' => $nome_usuario,
                    'documento_usuario' => $cpf,
                    'endereco_usuario' => $endereco_usuario,
                    'bairro_usuario' => $bairro_usuario,
                    'cidade_usuario' => $cidade_usuario,
                    'uf_usuario' => $uf_usuario,
                    'cep_usuario' => $cep_usuario,
                    'numero_endereco' => mt_rand(1,1999),
                    'client_id_celcoin' => $client_id_celcoin,
                    'client_secret_celcoin' => $client_secret_celcoin,
                    'access_token_celcoin' => $access_token_celcoin,
                    'pixkey' => $pixkey,
                    'provider_reference' => $provider_reference,
                    'expiration' => $expiration,
                );

                $path_name = "deposit-pix-celcoin-".date("Y-m-d");

                if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                    mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                }

                $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($params_array));

                $return = $FunctionsAPIController->createTransactionPIXCELCOIN($params_array);

            break;

            case"588":
                // Acesso Celcoin
                $voluti_basic = $client->bankPix->voluti_basic;

                $params_array = array(
                    'client_id' => $client->id,
                    'user_id' => $request->user_id,
                    'pedido' => $pedido,
                    'order_id' => $request->order_id,
                    'authorization' => $authentication->authorization,
                    'data_emissao' => $data_emissao,
                    'data_vencimento' => $data_vencimento,
                    'data_vencimento_bs2' => $data_vencimento_bs2,
                    'amount' => $amount,
                    'valor_pix' => $valor_pix,
                    'cpf' => $cpf,
                    'nome_usuario' => $nome_usuario,
                    'documento_usuario' => $cpf,
                    'endereco_usuario' => $endereco_usuario,
                    'bairro_usuario' => $bairro_usuario,
                    'cidade_usuario' => $cidade_usuario,
                    'uf_usuario' => $uf_usuario,
                    'cep_usuario' => $cep_usuario,
                    'numero_endereco' => mt_rand(1,1999),
                    'voluti_basic' => $voluti_basic,
                    'pixkey' => $pixkey,
                    'expiration' => $expiration,
                );

                $path_name = "deposit-pix-voluti-".date("Y-m-d");

                if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                    mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                }

                $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($params_array));

                $return = $FunctionsAPIController->createTransactionPIXVOLUTI($params_array);

            break;

            case"787":
                // Acesso SuitPay
                $ci = $client->bankPix->client_id_celcoin;
                $cl = $client->bankPix->client_secret_celcoin;

                $params_array = array(
                    'client_id' => $client->id,
                    'user_id' => $request->user_id,
                    'pedido' => $pedido,
                    'order_id' => $request->order_id,
                    'authorization' => $authentication->authorization,
                    'data_emissao' => $data_emissao,
                    'data_vencimento' => $data_vencimento,
                    'data_vencimento_bs2' => $data_vencimento_bs2,
                    'amount' => $amount,
                    'valor_pix' => $valor_pix,
                    'cpf' => $cpf,
                    'nome_usuario' => $nome_usuario,
                    'documento_usuario' => $cpf,
                    'endereco_usuario' => $endereco_usuario,
                    'bairro_usuario' => $bairro_usuario,
                    'cidade_usuario' => $cidade_usuario,
                    'uf_usuario' => $uf_usuario,
                    'cep_usuario' => $cep_usuario,
                    'numero_endereco' => mt_rand(1,1999),
                    'ci' => $ci,
                    'cl' => $cl,
                    'pixkey' => $pixkey,
                    'expiration' => $expiration,
                );

                $path_name = "deposit-pix-suitpay-".date("Y-m-d");

                if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                    mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                }

                $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($params_array));

                $return = $FunctionsAPIController->createTransactionPIXSUITPAY($params_array);

            break;

            case"844":
                // Acesso Celcoin
                $hubauth = $client->bankPix->access_token_celcoin;

                $pedido = $bankPix->prefix.$FunctionsAPIController->geraSenha(26);

                $params_array = array(
                    'client_id' => $client->id,
                    'user_id' => $request->user_id,
                    'pedido' => $pedido,
                    'order_id' => $request->order_id,
                    'authorization' => $authentication->authorization,
                    'data_emissao' => $data_emissao,
                    'data_vencimento' => $data_vencimento,
                    'data_vencimento_bs2' => $data_vencimento_bs2,
                    'amount' => $amount,
                    'valor_pix' => $valor_pix,
                    'cpf' => $cpf,
                    'nome_usuario' => $nome_usuario,
                    'documento_usuario' => $cpf,
                    'endereco_usuario' => $endereco_usuario,
                    'bairro_usuario' => $bairro_usuario,
                    'cidade_usuario' => $cidade_usuario,
                    'uf_usuario' => $uf_usuario,
                    'cep_usuario' => $cep_usuario,
                    'numero_endereco' => mt_rand(1,1999),
                    'hubauth' => $hubauth,
                    'pixkey' => $pixkey,
                    'expiration' => $expiration,
                );

                $path_name = "deposit-pix-hubapi-".date("Y-m-d");

                if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                    mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                }

                $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($params_array));

                $return = $FunctionsAPIController->createTransactionPIXHUBAPI($params_array);

            break;

            case"845":
                // Acesso Celcoin
                $voluti_clientid = $client->bankPix->voluti_clientid;
                $voluti_clientsecret = $client->bankPix->voluti_clientsecret;
                $voluti_pixkey = $client->bankPix->voluti_pixkey;

                $pedido = $bankPix->prefix.$FunctionsAPIController->geraSenha(26);

                $params_array = array(
                    'client_id' => $client->id,
                    'user_id' => $request->user_id,
                    'pedido' => $pedido,
                    'order_id' => $request->order_id,
                    'authorization' => $authentication->authorization,
                    'data_emissao' => $data_emissao,
                    'data_vencimento' => $data_vencimento,
                    'data_vencimento_bs2' => $data_vencimento_bs2,
                    'amount' => $amount,
                    'valor_pix' => $valor_pix,
                    'cpf' => $cpf,
                    'nome_usuario' => $nome_usuario,
                    'documento_usuario' => $cpf,
                    'endereco_usuario' => $endereco_usuario,
                    'bairro_usuario' => $bairro_usuario,
                    'cidade_usuario' => $cidade_usuario,
                    'uf_usuario' => $uf_usuario,
                    'cep_usuario' => $cep_usuario,
                    'numero_endereco' => mt_rand(1,1999),
                    'voluti_clientid' => $voluti_clientid,
                    'voluti_clientsecret' => $voluti_clientsecret,
                    'voluti_pixkey' => $voluti_pixkey,
                    'pixkey' => $pixkey,
                    'expiration' => $expiration,
                );

                $path_name = "deposit-pix-volutinew-".date("Y-m-d");

                if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                    mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                }

                $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($params_array));

                $return = $FunctionsAPIController->createTransactionPIXVolutiNew($params_array);

            break;

            case"846":
                // Acesso LuxTax
                $luxtax_basic = "Basic MTYyNTgyOTIxNDUzMTY2Mzg6UGFnc21pbGVfc2tfZDUwMWQ1ZGNkNTI5OGQ5N2MwNmUzYjI4YjA2OWZjZmY3NDU5ZjY2NzNiMjFjMTFlYTY3NDM5MDgzOTZkOTYxNQ==";

                $params_array = array(
                    'client_id' => $client->id,
                    'user_id' => $request->user_id,
                    'pedido' => $pedido,
                    'order_id' => $request->order_id,
                    'authorization' => $authentication->authorization,
                    'data_emissao' => $data_emissao,
                    'data_vencimento' => $data_vencimento,
                    'data_vencimento_bs2' => $data_vencimento_bs2,
                    'amount' => $amount,
                    'valor_pix' => $valor_pix,
                    'cpf' => $cpf,
                    'nome_usuario' => $nome_usuario,
                    'documento_usuario' => $cpf,
                    'endereco_usuario' => $endereco_usuario,
                    'bairro_usuario' => $bairro_usuario,
                    'cidade_usuario' => $cidade_usuario,
                    'uf_usuario' => $uf_usuario,
                    'cep_usuario' => $cep_usuario,
                    'numero_endereco' => mt_rand(1,1999),
                    'luxtax_basic' => $luxtax_basic,
                    'pixkey' => $pixkey,
                    'expiration' => $expiration,
                );

                $path_name = "deposit-pix-luxtax-".date("Y-m-d");

                if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                    mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                }

                $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($params_array));

                $return = $FunctionsAPIController->createTransactionPIXLUXTAX($params_array);

            break;
        }

        return $return;
    }

    public function toTED($request){

        $FunctionsAPIController = new FunctionsAPIController();

        $return;
        $client = Clients::where("id","=",$request->client)->first();
        $authentication = $client->key;

        $user_document = $FunctionsAPIController->clearCPF($request->user_document);

        $amount = $request->amount;
        $subs = substr($amount,-3,1);
        if($subs == "."){
            $amount = $amount;
            $amount_fiat = number_format($amount,2,",","");
        }elseif($subs == ","){
            $amount = $FunctionsAPIController->strtodouble($amount);
            $amount_fiat = number_format($amount,2,",","");
        }else{
            $amount = number_format($amount,2,".","");
            $amount_fiat = number_format($amount,2,",","");
        }

        $check_rules = $FunctionsAPIController->check_user_rules($client->id,$request->user_id,$user_document,$request->amount,"ted");

        if(!isset($check_rules['return'])){
            return response()->json($check_rules,422);
        }

        $cont_name = strlen($request->user_name);
        if($cont_name > 40){
            $nus = explode(" ",$request->user_name);
            $name_user = $nus[0]." ".$nus[1];
        }else{
            $name_user = $request->user_name;
        }

        $data_fake = $FunctionsAPIController->get_random_address();

        $user_address = $data_fake['endereco'];
        $user_district = $data_fake['bairro'];
        $user_city = $data_fake['cidade'];
        $user_uf = $data_fake['estado'];
        $user_cep = str_replace("-","",$data_fake['cep']);
        $count_cep = strlen($user_cep);

        switch($count_cep){
            case"5": $user_cep = "000".$user_cep; break;
            case"6": $user_cep = "00".$user_cep; break;
            case"7": $user_cep = "0".$user_cep; break;
        }

        $days_safe_ted = $client->days_safe_ted;
        $days_safe_ted += 3;

        $dados = "";
        $data_emissao = date("Y-m-d");
        $data_vencimento = date("Y-m-d",strtotime("+".$days_safe_ted." days"));
        $valor = $amount_fiat;
        $cpf = $FunctionsAPIController->limpaCPF_CNPJ($request->user_document);
        $nome_usuario = $FunctionsAPIController->remove_accents($FunctionsAPIController->limit_caracter($name_user,15));
        $endereco_usuario = $FunctionsAPIController->limit_caracter($user_address,15);
        $bairro_usuario = $FunctionsAPIController->limit_caracter($user_district,15);
        $cidade_usuario = $FunctionsAPIController->limit_caracter($user_city,15);
        $uf_usuario = $user_uf;
        $cep_usuario = $user_cep;
        $bank_code = $request->bank_code;
        $bankTed = $client->bankTed;

        $pedido = $bankTed->prefix.$FunctionsAPIController->gera_pedido($client->id);

        switch($bank_code){
            case"461":

                $params_array = array(
                    'client_id' => $client->id,
                    'user_id' => $request->user_id,
                    'pedido' => $pedido,
                    'order_id' => $request->order_id,
                    'authorization' => $authentication->authorization,
                    'data_emissao' => $data_emissao,
                    'data_vencimento' => $data_vencimento,
                    'amount' => $amount,
                    'valor' => $valor,
                    'cpf' => $cpf,
                    'nome_usuario' => $nome_usuario,
                    'documento_usuario' => $cpf,
                    'endereco_usuario' => $endereco_usuario,
                    'bairro_usuario' => $bairro_usuario,
                    'cidade_usuario' => $cidade_usuario,
                    'uf_usuario' => $uf_usuario,
                    'cep_usuario' => $cep_usuario,
                    'numero_endereco' => mt_rand(1,1999),
                    'provider_reference' => $request->provider_reference
                );

                $return = $FunctionsAPIController->createTransactionTED($params_array);

            break;
            default: $return = json_encode(["status" => "error", "message" => "Bank code not found"]);
        }

        return $return;

    }

    public function toCC($request){

        $FunctionsAPIController = new FunctionsAPIController();

        $return;
        $client = Clients::where("id","=",$request->client)->first();
        $authentication = $client->key;

        $user_document = $FunctionsAPIController->clearCPF($request->user_document);

        $amount = $request->amount;
        $subs = substr($amount,-3,1);
        if($subs == "."){
            $amount = $amount;
            $amount_fiat_cc = number_format($amount,2,",","");
        }elseif($subs == ","){
            $amount = $FunctionsAPIController->strtodouble($amount);
            $amount_fiat_cc = number_format($amount,2,",","");
        }else{
            $amount = number_format($amount,2,".","");
            $amount_fiat_cc = number_format($amount,2,",","");
        }

        $email_usuario = $request->user_email;
        $telefone_usuario = $FunctionsAPIController->clear_phone($request->user_phone);

        if(!isset($email_usuario)){
            return response()->json(["message" => "error on create transaction", "reason" => "field 'email' empty"],422);
        }
        if(!isset($telefone_usuario)){
            return response()->json(["message" => "error on create transaction", "reason" => "field 'telefone' empty"],422);
        }

        $check_rules = $FunctionsAPIController->check_user_rules($client->id,$request->user_id,$user_document,$request->amount,"creditcard");

        if(!isset($check_rules['return'])){
            return response()->json($check_rules,422);
        }

        $cont_name = strlen($request->user_name);
        if($cont_name > 40){
            $nus = explode(" ",$request->user_name);
            $name_user = $nus[0]." ".$nus[1];
        }else{
            $name_user = $request->user_name;
        }

        // $data_fake = $FunctionsAPIController->get_random_address();

        // User credit card data
        $card_holder = $request->card_holder;
        $credit_card_number = $request->card_number;
        $card_expired = $request->card_expired;
        $card_cvv = $request->card_cvv;

        $user_address = $request->user_address;
        $user_address_number = $request->user_address_number;
        $user_district = $request->user_district;
        $user_city = $request->user_city;
        $user_uf = $request->user_uf;
        $user_cep = preg_replace('/[^0-9]/', '', (string) $request->user_cep);

        if($user_address == "" || $user_address_number == "" || $user_district == "" || $user_city == "" || $user_uf == "" || $user_cep == ""){
            // Error, Brand not found
            $json_return = array("message" => "Address fields empty", "reason" => "Paramters incorrect");
            return response()->json($json_return,401);
            exit();
        }

        if($user_address == "---" || $user_address_number == "---" || $user_district == "---" || $user_city == "---" || $user_uf == "---" || $user_cep == "---"){
            // Error, Brand not found
            $json_return = array("message" => "Address fields error", "reason" => "Paramters incorrect");
            return response()->json($json_return,401);
            exit();
        }

        if($FunctionsAPIController->check_zipcode($user_cep) != "success"){
            $json_return = array("message" => "Invalid address", "reason" => "Paramters incorrect");
            return response()->json($json_return,401);
            exit();
        }

        $check_bank = $client->bankCC->id;

        if($check_bank == "9999"){

            $json_return = array("message" => "Suspended payment method", "reason" => "Paramters incorrect");
            return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
            exit();

        }

        $days_safe_cc = $client->days_safe_cc;
        $days_safe_cc += 3;

        $dados = "";
        $data_emissao = date("Y-m-d");
        $data_vencimento = date("Y-m-d",strtotime("+".$days_safe_cc." days"));
        $data_vencimento_bs2 = date("Y-m-d",strtotime("+".$days_safe_cc." days"))."T00:00:00.334Z";
        $valor_cc = $amount_fiat_cc;
        $cpf = $FunctionsAPIController->limpaCPF_CNPJ($request->user_document);
        $nome_usuario = $FunctionsAPIController->remove_accents($FunctionsAPIController->limit_caracter($name_user,15));
        $endereco_usuario = $FunctionsAPIController->limit_caracter($user_address,15);
        $bairro_usuario = $FunctionsAPIController->limit_caracter($user_district,15);
        $cidade_usuario = $FunctionsAPIController->limit_caracter($user_city,15);
        $uf_usuario = $user_uf;
        $cep_usuario = $user_cep;
        $bankCCCode = $client->bankCC->code;
        $bankCC = $client->bankCC;

        $pedido = $bankCC->prefix.$FunctionsAPIController->gera_pedido($client->id);

        switch($bankCCCode){

            case"461":

                // Acesso Gerencianet
                $access_token_asaas = $client->bankCC->access_token_asaas;

                $user_asaas_merchant = RegisterUserMerchant::where("cpfCnpj",$cpf)->first();

                $number_address = mt_rand(1,1999);

                if($user_asaas_merchant !== null){
                    $customer_id = $user_asaas_merchant->customer_id;
                }else{

                    $user_data = [
                        "name" => $nome_usuario,
                        "email" => "",
                        "phone" => "",
                        "mobilePhone" => "",
                        "cpfCnpj" => $cpf,
                        "postalCode" => $cep_usuario,
                        "address" => $user_address,
                        "addressNumber" => $number_address,
                        "complement" => "",
                        "province" => $user_district,
                        "externalReference" => "",
                        "notificationDisabled" => "",
                        "additionalEmails" => "",
                        "municipalInscription" => "",
                        "stateInscription" => "",
                        "observations" => "",
                    ];

                    $customer_id = $FunctionsAPIController->registerUserAsaas($user_data,$access_token_asaas);

                }

                $params_array = array(
                    'client_id' => $client->id,
                    'user_id' => $request->user_id,
                    'pedido' => $pedido,
                    'order_id' => $request->order_id,
                    'authorization' => $authentication->authorization,
                    'data_emissao' => $data_emissao,
                    'data_vencimento' => $data_vencimento,
                    'data_vencimento_bs2' => $data_vencimento_bs2,
                    'amount' => $amount,
                    'valor_pix' => $valor_cc,
                    'cpf' => $cpf,
                    'nome_usuario' => $nome_usuario,
                    'email_usuario' => $email_usuario,
                    'telefone_usuario' => $telefone_usuario,
                    'documento_usuario' => $cpf,
                    'card_holder' => $card_holder,
                    'credit_card_number' => $credit_card_number,
                    'card_expired' => $card_expired,
                    'card_cvv' => $card_cvv,
                    'endereco_usuario' => $user_address,
                    'bairro_usuario' => $user_district,
                    'cidade_usuario' => $user_city,
                    'uf_usuario' => $uf_usuario,
                    'cep_usuario' => $cep_usuario,
                    'numero_endereco' => $number_address,
                    'access_token_asaas' => $access_token_asaas,
                    'customer_id' => $customer_id
                );

                $return = $FunctionsAPIController->createTransactionCCAsaas($params_array);

            break;

            case"589":

                // Acesso Gerencianet
                $access_token_asaas = $client->bankCC->access_token_asaas;

                $user_asaas_merchant = RegisterUserMerchant::where("cpfCnpj",$cpf)->first();

                $number_address = mt_rand(1,1999);

                if($user_asaas_merchant !== null){
                    $customer_id = $user_asaas_merchant->customer_id;
                }else{

                    $user_data = [
                        "name" => $nome_usuario,
                        "email" => "",
                        "phone" => "",
                        "mobilePhone" => "",
                        "cpfCnpj" => $cpf,
                        "postalCode" => $cep_usuario,
                        "address" => $user_address,
                        "addressNumber" => $number_address,
                        "complement" => "",
                        "province" => $user_district,
                        "externalReference" => "",
                        "notificationDisabled" => "",
                        "additionalEmails" => "",
                        "municipalInscription" => "",
                        "stateInscription" => "",
                        "observations" => "",
                    ];

                    $customer_id = $FunctionsAPIController->registerUserAsaas($user_data,$access_token_asaas);

                }

                $params_array = array(
                    'client_id' => $client->id,
                    'user_id' => $request->user_id,
                    'pedido' => $pedido,
                    'order_id' => $request->order_id,
                    'authorization' => $authentication->authorization,
                    'data_emissao' => $data_emissao,
                    'data_vencimento' => $data_vencimento,
                    'data_vencimento_bs2' => $data_vencimento_bs2,
                    'amount' => $amount,
                    'valor_pix' => $valor_cc,
                    'cpf' => $cpf,
                    'nome_usuario' => $nome_usuario,
                    'email_usuario' => $email_usuario,
                    'telefone_usuario' => $telefone_usuario,
                    'documento_usuario' => $cpf,
                    'card_holder' => $card_holder,
                    'credit_card_number' => $credit_card_number,
                    'card_expired' => $card_expired,
                    'card_cvv' => $card_cvv,
                    'endereco_usuario' => $user_address,
                    'bairro_usuario' => $user_district,
                    'cidade_usuario' => $user_city,
                    'uf_usuario' => $uf_usuario,
                    'cep_usuario' => $cep_usuario,
                    'numero_endereco' => $number_address,
                    'access_token_asaas' => $access_token_asaas,
                    'customer_id' => $customer_id
                );

                $return = $FunctionsAPIController->createTransactionCCAsaas($params_array);

            break;

        }

        return $return;

    }

}
