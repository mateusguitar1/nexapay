<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use Jose\JWT;
use App\Http\Controllers\FunctionsAPIController;
use App\Models\{Clients,Keys,Transactions,Banks,DataAccountBank};


class WithdrawController extends Controller
{
    //
    public function create(Request $request){

        $FunctionsAPIController = new FunctionsAPIController();

        $path_name = "create-withdraw-".date("Y-m-d");

        if (!file_exists('/var/www/html/fastpayments/logs/'.$path_name)) {
            mkdir('/var/www/html/fastpayments/logs/'.$path_name, 0777, true);
        }

        $FunctionsAPIController->registerRecivedsRequests("/var/www/html/fastpayments/logs/".$path_name."/log.txt",$request->getContent());

        $return;
        $client = Clients::where("id","=",$request->client)->first();
        $authentication = Keys::where("authorization_withdraw_a4p","=",$request->authorization)->first();

        if($request->amount < $client->tax->min_withdraw){
            $json_return = array("message" => "Minimum amount R$ ".number_format($client->tax->min_withdraw,2,",","."), "reason" => "Illegal Conditions");
            return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
            exit();
        }
        if($request->amount > $client->tax->max_withdraw){
            $json_return = array("message" => "Maximum amount R$ ".number_format($client->tax->max_withdraw,2,",","."), "reason" => "Illegal Conditions");
            return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
            exit();
        }

        if(isset($request->provider_reference)){
            $provider_reference = $request->provider_reference;
        }else{
            $provider_reference = "";
        }

        $amount = $request->amount;
        $subs = substr($amount,-3,1);
        if($subs == "."){
            $amount = $amount;
        }elseif($subs == ","){
            $amount = $FunctionsAPIController->strtodouble($amount);
        }else{
            $amount = number_format(floatval($amount),2,".","");
        }

        $pixkey = "";
        $typepixkey = "";

        if(isset($request->method)){

            $order_id = $request->order_id;
            $user_id = $request->user_id;
            $user_name = $request->user_name;
            $user_document = $request->user_document;

            $bank_code = "";
            $bank_name = "";
            $agency = "";
            $type_operation = "";
            $account = "";
            $pixkey = "";
            $typepixkey = "";

            $method = $request->method;

            $bank = $client->bankWithdrawPix;
            $client_id_celcoin = $bank->client_id_celcoin;
            $client_secret_celcoin = $bank->client_secret_celcoin;
            $access_token_celcoin = $bank->access_token_celcoin;

            $params_celcoin = [
                'client_id' => $client->id,
                'client_id_celcoin' => $client_id_celcoin,
                'client_secret_celcoin' => $client_secret_celcoin,
                'access_token_celcoin' => $access_token_celcoin
            ];

            if($request->method == "pix"){

                if(!isset($request->pix_key,$request->type_pixkey)){

                    $json_return = array("message" => "PixKey not defined", "reason" => "Illegal Conditions");
                    return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
                    exit();

                }else{

                    if($bank->code == "587"){
                        $pix_key = $request->pix_key;

                        switch($request->type_pixkey){
                            case"telefone":
                                $pix_key = str_replace("(","",$pix_key);
                                $pix_key = str_replace(")","",$pix_key);
                                $pix_key = str_replace(" ","",$pix_key);
                                $pix_key = str_replace("-","",$pix_key);
                                $pix_key = str_replace(".","",$pix_key);
                                $pix_key = str_replace("+","",$pix_key);

                                if(strlen($pix_key) > 11){
                                    if(substr($pix_key,0,2) == "55"){
                                        $pix_key = substr($pix_key,2,11);
                                    }
                                }

                                $pix_key = "+55".$pix_key;
                            break;
                            case"phone":
                                $pix_key = str_replace("(","",$pix_key);
                                $pix_key = str_replace(")","",$pix_key);
                                $pix_key = str_replace(" ","",$pix_key);
                                $pix_key = str_replace("-","",$pix_key);
                                $pix_key = str_replace(".","",$pix_key);
                                $pix_key = str_replace("+","",$pix_key);

                                if(strlen($pix_key) > 11){
                                    if(substr($pix_key,0,2) == "55"){
                                        $pix_key = substr($pix_key,2,11);
                                    }
                                }

                                $pix_key = "+55".$pix_key;
                            break;
                            case"cpf":
                                $pix_key = str_replace(".","",$pix_key);
                                $pix_key = str_replace("-","",$pix_key);
                            break;
                            case"cnpj":
                                $pix_key = str_replace(".","",$pix_key);
                                $pix_key = str_replace("-","",$pix_key);
                                $pix_key = str_replace("/","",$pix_key);
                            break;
                            case"email":
                                $pix_key = strtolower($pix_key);
                            break;
                            case"aleatoria":
                                $pix_key = $pix_key;
                            break;
                            case"random":
                                $pix_key = $pix_key;
                            break;

                        }

                        $token_celcoin = json_decode($this->getTokenCELCOIN($params_celcoin),true);

                        $token = $token_celcoin['access_token'];

                        $getPixInfo = json_decode($this->getPixInfo($pix_key,$token),true);

                        if(!isset($getPixInfo['account'])){

                            $path_name = "celcoin-pixinfo-withdraw-".date("Y-m-d");

                            if (!file_exists('/var/www/html/fastpayments/logs/'.$path_name)) {
                                mkdir('/var/www/html/fastpayments/logs/'.$path_name, 0777, true);
                            }

                            $data = [
                                "pixinfo" => $getPixInfo,
                                "pix_key" => $request->pix_key,
                                "request" => $request->all()
                            ];

                            $FunctionsAPIController->registerRecivedsRequests("/var/www/html/fastpayments/logs/".$path_name."/log.txt",json_encode($data));

                            $json_return = array("message" => $getPixInfo, "reason" => "Illegal Conditions");
                            return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
                            exit();
                        }
                    }elseif($bank->code == "588"){
                        $pix_key = $request->pix_key;

                        switch($request->type_pixkey){
                            case"telefone":
                                $pix_key = str_replace("(","",$pix_key);
                                $pix_key = str_replace(")","",$pix_key);
                                $pix_key = str_replace(" ","",$pix_key);
                                $pix_key = str_replace("-","",$pix_key);
                                $pix_key = "+55".$pix_key;
                            break;
                            case"cpf":
                                $pix_key = str_replace(".","",$pix_key);
                                $pix_key = str_replace("-","",$pix_key);
                            break;
                            case"cnpj":
                                $pix_key = str_replace(".","",$pix_key);
                                $pix_key = str_replace("-","",$pix_key);
                                $pix_key = str_replace("/","",$pix_key);
                            break;
                            case"email":
                                $pix_key = strtolower($pix_key);
                            break;
                            case"aleatoria":
                                $pix_key = $pix_key;
                            break;

                        }

                    }elseif($bank->code == "844"){

                        $pix_key = $request->pix_key;

                        switch($request->type_pixkey){
                            case"telefone":
                                $pix_key = str_replace("(","",$pix_key);
                                $pix_key = str_replace(")","",$pix_key);
                                $pix_key = str_replace(" ","",$pix_key);
                                $pix_key = str_replace("-","",$pix_key);
                                $pix_key = str_replace(".","",$pix_key);
                                $pix_key = str_replace("+","",$pix_key);

                                if(strlen($pix_key) > 11){
                                    if(substr($pix_key,0,2) == "55"){
                                        $pix_key = substr($pix_key,2,11);
                                    }
                                }

                                $pix_key = "+55".$pix_key;
                            break;
                            case"phone":
                                $pix_key = str_replace("(","",$pix_key);
                                $pix_key = str_replace(")","",$pix_key);
                                $pix_key = str_replace(" ","",$pix_key);
                                $pix_key = str_replace("-","",$pix_key);
                                $pix_key = str_replace(".","",$pix_key);
                                $pix_key = str_replace("+","",$pix_key);

                                if(strlen($pix_key) > 11){
                                    if(substr($pix_key,0,2) == "55"){
                                        $pix_key = substr($pix_key,2,11);
                                    }
                                }

                                $pix_key = "+55".$pix_key;
                            break;
                            case"cpf":
                                $pix_key = str_replace(".","",$pix_key);
                                $pix_key = str_replace("-","",$pix_key);
                            break;
                            case"cnpj":
                                $pix_key = str_replace(".","",$pix_key);
                                $pix_key = str_replace("-","",$pix_key);
                                $pix_key = str_replace("/","",$pix_key);
                            break;
                            case"email":
                                $pix_key = strtolower($pix_key);
                            break;
                            case"aleatoria":
                                $pix_key = $pix_key;
                            break;
                            case"random":
                                $pix_key = $pix_key;
                            break;

                        }

                    }elseif($bank->code == "845"){

                        $pix_key = $request->pix_key;

                        $type = strtolower($request->type_pixkey);

                        switch($type){
                            case"telefone":
                                $pix_key = str_replace("(","",$pix_key);
                                $pix_key = str_replace(")","",$pix_key);
                                $pix_key = str_replace(" ","",$pix_key);
                                $pix_key = str_replace("-","",$pix_key);
                                $pix_key = str_replace(".","",$pix_key);
                                $pix_key = str_replace("+","",$pix_key);

                                if(strlen($pix_key) > 11){
                                    if(substr($pix_key,0,2) == "55"){
                                        $pix_key = substr($pix_key,2,11);
                                    }
                                }

                                $pix_key = "+55".$pix_key;
                            break;
                            case"phone":
                                $pix_key = str_replace("(","",$pix_key);
                                $pix_key = str_replace(")","",$pix_key);
                                $pix_key = str_replace(" ","",$pix_key);
                                $pix_key = str_replace("-","",$pix_key);
                                $pix_key = str_replace(".","",$pix_key);
                                $pix_key = str_replace("+","",$pix_key);

                                if(strlen($pix_key) > 11){
                                    if(substr($pix_key,0,2) == "55"){
                                        $pix_key = substr($pix_key,2,11);
                                    }
                                }

                                $pix_key = "+55".$pix_key;
                            break;
                            case"cpf":
                                $pix_key = str_replace(".","",$pix_key);
                                $pix_key = str_replace("-","",$pix_key);
                            break;
                            case"cnpj":
                                $pix_key = str_replace(".","",$pix_key);
                                $pix_key = str_replace("-","",$pix_key);
                                $pix_key = str_replace("/","",$pix_key);
                            break;
                            case"email":
                                $pix_key = strtolower($pix_key);
                            break;
                            case"aleatoria":
                                $pix_key = $pix_key;
                            break;
                            case"random":
                                $pix_key = $pix_key;
                            break;
                            default:
                                $pix_key = $pix_key;
                        }

                    }elseif($bank->code == "846"){

                        $pix_key = $request->pix_key;

                        $type = strtolower($request->type_pixkey);

                        switch($type){
                            case"telefone":
                                $pix_key = str_replace("(","",$pix_key);
                                $pix_key = str_replace(")","",$pix_key);
                                $pix_key = str_replace(" ","",$pix_key);
                                $pix_key = str_replace("-","",$pix_key);
                                $pix_key = str_replace(".","",$pix_key);
                                $pix_key = str_replace("+","",$pix_key);

                                if(strlen($pix_key) > 11){
                                    if(substr($pix_key,0,2) == "55"){
                                        $pix_key = substr($pix_key,2,11);
                                    }
                                }

                                $pix_key = "+55".$pix_key;
                            break;
                            case"phone":
                                $pix_key = str_replace("(","",$pix_key);
                                $pix_key = str_replace(")","",$pix_key);
                                $pix_key = str_replace(" ","",$pix_key);
                                $pix_key = str_replace("-","",$pix_key);
                                $pix_key = str_replace(".","",$pix_key);
                                $pix_key = str_replace("+","",$pix_key);

                                if(strlen($pix_key) > 11){
                                    if(substr($pix_key,0,2) == "55"){
                                        $pix_key = substr($pix_key,2,11);
                                    }
                                }

                                $pix_key = "+55".$pix_key;
                            break;
                            case"cpf":
                                $pix_key = str_replace(".","",$pix_key);
                                $pix_key = str_replace("-","",$pix_key);
                            break;
                            case"cnpj":
                                $pix_key = str_replace(".","",$pix_key);
                                $pix_key = str_replace("-","",$pix_key);
                                $pix_key = str_replace("/","",$pix_key);
                            break;
                            case"email":
                                $pix_key = strtolower($pix_key);
                            break;
                            case"aleatoria":
                                $pix_key = $pix_key;
                            break;
                            case"random":
                                $pix_key = $pix_key;
                            break;
                            default:
                                $pix_key = $pix_key;
                        }

                    }

                }

                $pixkey = $request->pix_key;
                $typepixkey = $request->type_pixkey;

            }elseif($request->method == "ted"){

                if(!isset($request->bank_code,$request->bank_name,$request->agency,$request->type_operation,$request->account)){

                    $json_return = array("message" => "Bank params not defined", "reason" => "Illegal Conditions");
                    return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
                    exit();

                }else{

                    $bank_code = $request->bank_code;
                    $bank_name = $request->bank_name;
                    $agency = $request->agency;
                    $type_operation = $request->type_operation;
                    $account = $request->account;

                }

            }

        }else{

            $json_return = array("message" => "Method not defined", "reason" => "Illegal Conditions");
            return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
            exit();

        }

        $pedido = $FunctionsAPIController->gera_pedido_withdraw($client->id);

        // params Bradesco Shop
        $post_params = array(
            'client_id' => $client->id,
            'user_id' => $user_id,
            'pedido' => $pedido,
            'order_id' => $order_id,
            'authorization' => $authentication->authorization_withdraw,
            'user_name' => $user_name,
            'user_document' => $FunctionsAPIController->clearCPF($user_document),
            'bank_code' => $bank_code,
            'bank_name' => $bank_name,
            'agency' => $agency,
            'type_operation' => $type_operation,
            'account' => $account,
            'pix_key' => $pixkey,
            'type_pixkey' => $typepixkey,
            'amount' => $amount,
            'method' => $method,
            'provider_reference' => $provider_reference,
        );

        $return = $FunctionsAPIController->createWithdraw($post_params);

        return $return;
    }

    public function get(Request $request){

        $FunctionsAPIController = new FunctionsAPIController();

        $Authorization = $request->header('Token');
        $auth = Keys::where("authorization_withdraw_a4p","=",$Authorization)->first();
        $client = $auth->client;
        $transaction = Transactions::where("client_id","=",$client->id)->where("order_id","=",$request->order_id)->first();

        if(empty($transaction)){

            $json_return = array("message" => "Transaction not found", "reason" => "Page not found");
            return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
            exit();

        }

        if(isset($transaction->user_account_data)){

            $user_dt = json_decode(base64_decode($transaction->user_account_data),true);
            if(isset($user_dt['bank_code'])){
                $code_bank = $user_dt['bank_code'];
            }else{
                $code_bank = "";
            }

            $user_data = array(
                "bank_name" => $user_dt['bank_name'],
                "name" => $user_dt['name'],
                "agency" => $user_dt['agency'],
                "operation_bank" => "",
                "account_number" => $user_dt['account_number'],
                "document" => $user_dt['document'],
            );

            if(isset($user_dt['account_type'])){
                $user_data['operation_bank'] = $user_dt['account_type'];
            }elseif(isset($user_dt['operation_bank'])){
                $user_data['operation_bank'] = $user_dt['operation_bank'];
            }

        }else{

            $user_data = array(
                "bank_name" => "",
                "name" => "",
                "agency" => "",
                "operation_bank" => "",
                "account_number" => "",
                "document" => "",
            );

            $code_bank = "";

        }

        $amount_solicitation = number_format($transaction->amount_solicitation,"2",".","");

        $array_return = array(
            "id" => $transaction->id,
            "fast_id" => $transaction->id,
            "order_id" => $transaction->order_id,
            "solicitation_date_clear" => $transaction->solicitation_date,
            "solicitation_date" => $FunctionsAPIController->datetostr(substr($transaction->solicitation_date,0,10))." ".substr($transaction->solicitation_date,11,8),
            "code_identify" => $transaction->code,
            "provider_reference" => $transaction->id,
            "amount_solicitation" => $amount_solicitation,
            "code_bank" => $code_bank,
            "bank_name" => $user_data['bank_name'],
            "holder" => $user_data['name'],
            "agency" => $user_data['agency'],
            "type_account" => $user_data['operation_bank'],
            "account" => $user_data['account_number'],
            "document" => $user_data['document'],
            "status" => $transaction->status,
        );

        return response()->json($array_return);
    }

    public function createWithdrawCelcoin(Request $request){

        $FunctionsAPIController = new FunctionsAPIController();

        $pix_key = $request->pix_key;
        $bank_pix_withdraw = $request->bank_pix_withdraw;
        $amount = $FunctionsAPIController->strtodouble($request->amount_solicitation);

        // Acesso Celcoin
        $bank = Banks::where("id",$bank_pix_withdraw)->first();
        $client_id_celcoin = $bank->client_id_celcoin;
        $client_secret_celcoin = $bank->client_secret_celcoin;
        $access_token_celcoin = $bank->access_token_celcoin;

        $pedido = $FunctionsAPIController->gera_pedido_pix_celcoin("8");

        $paramsToken = [
            "client_id_celcoin" => $client_id_celcoin,
            "client_secret_celcoin" => $client_secret_celcoin,
            "access_token_celcoin" => $access_token_celcoin,
        ];

        $get_token = json_decode($this->getTokenCELCOIN($paramsToken),true);

        if(!isset($get_token)){

            return response()->json([
                "message" => "Error on get Token",
                "return" => $get_token
            ],401);

        }else{
            $token_celcoin = $get_token['access_token'];
        }

        $getPixDict = json_decode($this->getPIXDict($pix_key,$token_celcoin),true);

        if(!isset($getPixDict['keyType'])){
            return response()->json([
                "message" => "Error on get DICT",
                "return" => $getPixDict
            ],401);
        }

        if(!is_null($bank->pixkey)){
            $data_bank_account = DataAccountBank::where("id_bank",$bank->id)->first();

            $debit_account = $data_bank_account->account;
            $debit_branch = $data_bank_account->branch;
            $debit_taxid = $data_bank_account->taxid;
            $debit_accountType = "TRAN";
            $debit_name = $data_bank_account->name;

            $credit_key = $pix_key;
            $credit_bank = $getPixDict['account']['participant'];
            $credit_account = $getPixDict['account']['accountNumber'];
            $credit_branch =  $getPixDict['account']['branch'];
            $credit_taxId = $getPixDict['owner']['taxIdNumber'];
            $credit_accountType = $getPixDict['account']['accountType'];
            $credit_name = $getPixDict['owner']['name'];

            $initiationType = "DICT";
            $remittanceInformation = "Transferencia PIX";
            $paymentType = "IMMEDIATE";
            $urgency = "HIGH";
            $transactionType = "TRANSFER";

            $paramsWithdraw = [
                "amount" => $amount,
                "clientCode" => $pedido,
                "debitParty" => array(
                    "account" => $debit_account,
                    "branch" =>  $debit_branch,
                    "taxId" => $debit_taxid,
                    "accountType" => $debit_accountType,
                    "name" => $debit_name
                ),
                "creditParty" => array(
                    "key" => $credit_key,
                    "bank" => $credit_bank,
                    "account" => $credit_account,
                    "branch" => $credit_branch,
                    "taxId" => $credit_taxId,
                    "accountType" => $credit_accountType,
                    "name" => $credit_name
                ),
                "initiationType" => $initiationType,
                "remittanceInformation" => $remittanceInformation,
                "paymentType" => $paymentType,
                "urgency" => $urgency,
                "transactionType" => $transactionType
            ];

            $executeWithdrawCelcoin = json_decode($this->executeWithdrawDinamicCelcoin($paramsWithdraw,$token_celcoin),true);

        }else{

            $paramsWithdraw = [
                "amount" => $amount,
                "pedido" => $pedido,
                "pix_key" => $getPixDict['key'],
                "participant" => $getPixDict['account']['participant'],
                "accountNumber" => $getPixDict['account']['accountNumber'],
                "branch" => $getPixDict['account']['branch'],
                "accountType" => $getPixDict['account']['accountType'],
                "taxIdNumber" => $getPixDict['owner']['taxIdNumber'],
                "name" => $getPixDict['owner']['name'],
            ];

            $executeWithdrawCelcoin = json_decode($this->executeWithdrawCelcoin($paramsWithdraw,$token_celcoin),true);

        }

        if(!isset($executeWithdrawCelcoin['code'])){
            return response()->json([
                "message" => "Error on create withdraw pix",
                "return" => $executeWithdrawCelcoin
            ],401);
        }

        if($executeWithdrawCelcoin['code'] !== "SUCCESS"){
            return response()->json([
                "message" => "Withdraw pix failed",
                "return" => $executeWithdrawCelcoin
            ],401);
        }

        $receiptText = preg_replace("/\\\\n/", "<br />",$executeWithdrawCelcoin['slip']);
        $receiptText = str_replace("\/", "/",$receiptText);
        $receiptText = str_replace("  ", "",$receiptText);

        $convert_string = print_r($receiptText,true);

        return response()->json(["slip" => $receiptText]);


    }


    public function getPixInfo($pix_key,$access_token_celcoin){

        $data = [
            "payerId" => env('COMPANY_DOCUMENT'),
            "key" => $pix_key
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apicorp.celcoin.com.br/pix/v1/dict/v2/key',
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

    public function getPIXDict($pix_key,$access_token_celcoin){

        $data = [
            "payerId" => env('COMPANY_DOCUMENT'),
            "key" => $pix_key
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apicorp.celcoin.com.br/pix/v1/dict/v2/key',
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

    public function executeWithdrawCelcoin($params = array(),$access_token_celcoin){

        $data = [
            "amount" => $params['amount'],
            "clientCode" => $params['pedido'],
            "debitParty" => [
                "account" => "3794245",
                "branch" => 30,
                "taxId" => "45403734000150",
                "accountType" => "CACC",
                "name" => "Fast Payments Administração de Pagamentos Ltda"
            ],
            "creditParty" => [
                "key" => $params['pix_key'],
                "bank" => $params['participant'],
                "account" => $params['accountNumber'],
                "branch" => $params['branch'],
                "taxId" => $params['taxIdNumber'],
                "accountType" => $params['accountType'],
                "name" => $params['name']
            ],
            "initiationType" => "DICT",
            "remittanceInformation" => "Saque para conta interna",
            "paymentType" => "IMMEDIATE",
            "urgency" => "HIGH",
            "transactionType" => "TRANSFER"
        ];

        // CreditParty para error
        // $data = [
        //     "amount" => $params['amount'],
        //     "clientCode" => $params['pedido'],
        //     "debitParty" => [
        //         "account" => "3794245",
        //         "branch" => 30,
        //         "taxId" => "45403734000150",
        //         "accountType" => "CACC",
        //         "name" => "Fast Payments Administração de Pagamentos Ltda"
        //     ],
        //     "creditParty" => [
        //         "key" => $params['pix_key'],
        //         "bank" => "30306294",
        //         "account" => "10545584",
        //         "branch" => "0",
        //         "taxId" => "11122233344",
        //         "accountType" => "CACC",
        //         "name" => "Celcoin"
        //     ],
        //     "initiationType" => "DICT",
        //     "remittanceInformation" => "Saque para conta interna",
        //     "paymentType" => "IMMEDIATE",
        //     "urgency" => "HIGH",
        //     "transactionType" => "TRANSFER"
        // ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apicorp.celcoin.com.br/pix/v1/payment',
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

    public function executeWithdrawDinamicCelcoin($params = array(),$access_token_celcoin){

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apicorp.celcoin.com.br/pix/v1/payment',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($params,true),
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Content-Type: application/json-patch+json',
            'Authorization: Bearer '.$access_token_celcoin
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

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

}
