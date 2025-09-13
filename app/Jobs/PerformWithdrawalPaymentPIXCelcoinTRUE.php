<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\{Transactions,User,Clients,Banks,DataAccountBank,ReceiptCelcoin,Extract};
use DB;

use App\Http\Controllers\FunctionsAPIController;

class PerformWithdrawalPaymentPIXCelcoinTRUE implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transaction_id;
    protected $bank_withdraw_id;
    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($transaction_id,$bank_withdraw_id)
    {
        //
        $this->transaction_id = $transaction_id;
        $this->bank_withdraw_id = $bank_withdraw_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        $FunctionsAPIController = new FunctionsAPIController();

        $transaction = Transactions::where("id",$this->transaction_id)->first();
        $bank = Banks::where("id",$this->bank_withdraw_id)->first();
        $client = Clients::where("id",$transaction->client_id)->first();
        $data_bank_account = DataAccountBank::where("id_bank",$bank->id)->first();

        if(isset($transaction->payment_id)){
            $payment_id = $transaction->payment_id;
        }else{
            $payment_id = "";
        }

        $checkConfirmedPix = json_decode($this->celcoinCheckPixKey($client->id,$payment_id),true);

        if(isset($checkConfirmedPix['status'])){
            if($checkConfirmedPix['status'] == "CONFIRMED"){
                DB::beginTransaction();
                try{

                    //Do update
                    $receiptCelcoin = ReceiptCelcoin::create([
                        "transaction_id" => $transaction->id,
                        "receipt" => $checkConfirmedPix['endToEndId']
                    ]);

                    DB::commit();

                    // set post fields
                    $post = [
                        "id" => $transaction->id,
                    ];

                    $post_field = json_encode($post);

                    $merchant_host = env('MEMCACHED_HOST');
                    $ch = curl_init("http://".$merchant_host."/fastpayments/public/api/approvecallback");
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field)));
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

                    // execute!
                    $response = curl_exec($ch);

                    curl_close($ch);

                }catch(Exception $e){
                    DB::roolback();
                }
            }else{

                $clientCode = $FunctionsAPIController->gera_pedido($transaction->client_id);

                $client_id_celcoin = $bank->client_id_celcoin;
                $client_secret_celcoin = $bank->client_secret_celcoin;
                $access_token_celcoin = $bank->access_token_celcoin;

                $params = [
                    'client_id' => $transaction->client_id,
                    'client_id_celcoin' => $client_id_celcoin,
                    'client_secret_celcoin' => $client_secret_celcoin,
                    'access_token_celcoin' => $access_token_celcoin
                ];

                $token_celcoin = json_decode($this->getTokenCELCOIN($params),true);

                $path_name = "celcoin-token-execute-withdraw".date("Y-m-d");

                if (!file_exists('/var/www/html/fastpayments/logs/'.$path_name)) {
                    mkdir('/var/www/html/fastpayments/logs/'.$path_name, 0777, true);
                }

                $FunctionsAPIController->registerRecivedsRequests("/var/www/html/fastpayments/logs/".$path_name."/log.txt",json_encode($token_celcoin));

                $token = $token_celcoin['access_token'];

                $user_account_data = json_decode(base64_decode($transaction->user_account_data),true);

                $pix_key = $user_account_data['pix_key'];

                switch($user_account_data['type_pixkey']){
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

                $pixInfo = json_decode($this->getPixInfo($pix_key,$token),true);

                $return_info = [
                    "pixInfo" => $pixInfo,
                    "pix_key" => $pix_key,
                    "account_data" => $user_account_data
                ];

                $path_name = "celcoin-return-info-new-".date("Y-m-d");

                if (!file_exists('/var/www/html/fastpayments/logs/'.$path_name)) {
                    mkdir('/var/www/html/fastpayments/logs/'.$path_name, 0777, true);
                }

                $FunctionsAPIController->registerRecivedsRequests("/var/www/html/fastpayments/logs/".$path_name."/log.txt",json_encode($return_info));

                if(isset($pixInfo['account'])){

                    $debit_account = $data_bank_account->account;
                    $debit_branch = $data_bank_account->branch;
                    $debit_taxid = $data_bank_account->taxid;
                    $debit_accountType = "TRAN";
                    $debit_name = $data_bank_account->name;

                    $credit_key = $pix_key;
                    $credit_bank = $pixInfo['account']['participant'];
                    $credit_account = $pixInfo['account']['accountNumber'];
                    $credit_branch =  $pixInfo['account']['branch'];
                    $credit_taxId = $pixInfo['owner']['taxIdNumber'];
                    $credit_accountType = $pixInfo['account']['accountType'];
                    $credit_name = $pixInfo['owner']['name'];

                    $initiationType = "DICT";
                    $remittanceInformation = "Transferencia PIX";
                    $paymentType = "IMMEDIATE";
                    $urgency = "HIGH";
                    $transactionType = "TRANSFER";

                    $amount = $transaction->amount_solicitation;

                    $data = [
                        "amount" => $amount,
                        "clientCode" => $clientCode,
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
                    CURLOPT_POSTFIELDS => json_encode($data,true),
                    CURLOPT_HTTPHEADER => array(
                        'Accept: application/json',
                        'Content-Type: application/json-patch+json',
                        'Authorization: Bearer '.$token
                    ),
                    ));

                    $response = curl_exec($curl);

                    curl_close($curl);

                    $get_response = json_decode($response,true);

                    $data_response = [
                        "response" => $get_response,
                        "pix_key" => $pix_key,
                        "account_data" => $user_account_data
                    ];

                    $path_name = "celcoin-cashout-".date("Y-m-d");

                    if (!file_exists('/var/www/html/fastpayments/logs/'.$path_name)) {
                        mkdir('/var/www/html/fastpayments/logs/'.$path_name, 0777, true);
                    }

                    $FunctionsAPIController->registerRecivedsRequests("/var/www/html/fastpayments/logs/".$path_name."/log.txt",json_encode($data_response));

                    if(isset($get_response['transactionId'])){

                        DB::beginTransaction();
                        try{

                            //Do update
                            $transaction->update([
                                "payment_id" => $get_response['transactionId']
                            ]);

                            $receiptCelcoin = ReceiptCelcoin::create([
                                "transaction_id" => $transaction->id,
                                "receipt" => $get_response['endToEndId']
                            ]);

                            DB::commit();

                            // set post fields
                            $post = [
                                "id" => $transaction->id,
                            ];

                            $post_field = json_encode($post);

                            $merchant_host = env('MEMCACHED_HOST');
                            $ch = curl_init("http://".$merchant_host."/fastpayments/public/api/approvecallback");
                            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field)));
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

                            // execute!
                            $response = curl_exec($ch);

                            curl_close($ch);

                        }catch(Exception $e){
                            DB::roolback();
                        }

                    }

                }else{

                    $path_name = "celcoin-pixinfo-withdraw-".date("Y-m-d");

                    if (!file_exists('/var/www/html/fastpayments/logs/'.$path_name)) {
                        mkdir('/var/www/html/fastpayments/logs/'.$path_name, 0777, true);
                    }

                    $data = [
                        "pixinfo" => $pixInfo,
                        "pix_key" => $user_account_data['pix_key'],
                        "account_data" => $user_account_data
                    ];

                    $FunctionsAPIController->registerRecivedsRequests("/var/www/html/fastpayments/logs/".$path_name."/log.txt",json_encode($data));

                }
            }
        }else{
            $clientCode = $FunctionsAPIController->gera_pedido($transaction->client_id);

            $client_id_celcoin = $bank->client_id_celcoin;
            $client_secret_celcoin = $bank->client_secret_celcoin;
            $access_token_celcoin = $bank->access_token_celcoin;

            $params = [
                'client_id' => $transaction->client_id,
                'client_id_celcoin' => $client_id_celcoin,
                'client_secret_celcoin' => $client_secret_celcoin,
                'access_token_celcoin' => $access_token_celcoin
            ];

            $token_celcoin = json_decode($this->getTokenCELCOIN($params),true);

            $path_name = "celcoin-token-execute-withdraw".date("Y-m-d");

            if (!file_exists('/var/www/html/fastpayments/logs/'.$path_name)) {
                mkdir('/var/www/html/fastpayments/logs/'.$path_name, 0777, true);
            }

            $FunctionsAPIController->registerRecivedsRequests("/var/www/html/fastpayments/logs/".$path_name."/log.txt",json_encode($token_celcoin));

            $token = $token_celcoin['access_token'];

            $user_account_data = json_decode(base64_decode($transaction->user_account_data),true);

            $pix_key = $user_account_data['pix_key'];
            $type_pixkey = $user_account_data['type_pixkey'];

            switch($type_pixkey){
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

            if($type_pixkey == "phone" || $type_pixkey == "telefone"){
                if(substr($pix_key,0,2) == "55"){
                    $pix_key = "+".$pix_key;
                }
            }

            $pixInfo = json_decode($this->getPixInfo($pix_key,$token),true);

            $return_info = [
                "pixInfo" => $pixInfo,
                "pix_key" => $pix_key,
                "account_data" => $user_account_data
            ];

            $path_name = "celcoin-return-info-new-".date("Y-m-d");

            if (!file_exists('/var/www/html/fastpayments/logs/'.$path_name)) {
                mkdir('/var/www/html/fastpayments/logs/'.$path_name, 0777, true);
            }

            $FunctionsAPIController->registerRecivedsRequests("/var/www/html/fastpayments/logs/".$path_name."/log.txt",json_encode($return_info));

            if(isset($pixInfo['account'])){

                $debit_account = $data_bank_account->account;
                $debit_branch = $data_bank_account->branch;
                $debit_taxid = $data_bank_account->taxid;
                $debit_accountType = "TRAN";
                $debit_name = $data_bank_account->name;

                $credit_key = $pix_key;
                $credit_bank = $pixInfo['account']['participant'];
                $credit_account = $pixInfo['account']['accountNumber'];
                $credit_branch =  $pixInfo['account']['branch'];
                $credit_taxId = $pixInfo['owner']['taxIdNumber'];
                $credit_accountType = $pixInfo['account']['accountType'];
                $credit_name = $pixInfo['owner']['name'];

                $initiationType = "DICT";
                $remittanceInformation = "Transferencia PIX";
                $paymentType = "IMMEDIATE";
                $urgency = "HIGH";
                $transactionType = "TRANSFER";

                $amount = $transaction->amount_solicitation;

                $data = [
                    "amount" => $amount,
                    "clientCode" => $clientCode,
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
                CURLOPT_POSTFIELDS => json_encode($data,true),
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Content-Type: application/json-patch+json',
                    'Authorization: Bearer '.$token
                ),
                ));

                $response = curl_exec($curl);

                curl_close($curl);

                $get_response = json_decode($response,true);

                $data_response = [
                    "response" => $get_response,
                    "pix_key" => $pix_key,
                    "account_data" => $user_account_data
                ];

                $path_name = "celcoin-cashout-".date("Y-m-d");

                if (!file_exists('/var/www/html/fastpayments/logs/'.$path_name)) {
                    mkdir('/var/www/html/fastpayments/logs/'.$path_name, 0777, true);
                }

                $FunctionsAPIController->registerRecivedsRequests("/var/www/html/fastpayments/logs/".$path_name."/log.txt",json_encode($data_response));

                if(isset($get_response['transactionId'])){

                    DB::beginTransaction();
                    try{

                        //Do update
                        $transaction->update([
                            "payment_id" => $get_response['transactionId']
                        ]);

                        $receiptCelcoin = ReceiptCelcoin::create([
                            "transaction_id" => $transaction->id,
                            "receipt" => $get_response['endToEndId']
                        ]);

                        DB::commit();

                        // set post fields
                        $post = [
                            "id" => $transaction->id,
                        ];

                        $post_field = json_encode($post);

                        $merchant_host = env('MEMCACHED_HOST');
                        $ch = curl_init("http://".$merchant_host."/fastpayments/public/api/approvecallback");
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field)));
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

                        // execute!
                        $response = curl_exec($ch);

                        curl_close($ch);

                    }catch(Exception $e){
                        DB::roolback();
                    }

                }

            }else{

                $path_name = "celcoin-pixinfo-withdraw-".date("Y-m-d");

                if (!file_exists('/var/www/html/fastpayments/logs/'.$path_name)) {
                    mkdir('/var/www/html/fastpayments/logs/'.$path_name, 0777, true);
                }

                $data = [
                    "pixinfo" => $pixInfo,
                    "pix_key" => $user_account_data['pix_key'],
                    "account_data" => $user_account_data
                ];

                $FunctionsAPIController->registerRecivedsRequests("/var/www/html/fastpayments/logs/".$path_name."/log.txt",json_encode($data));

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

    public function celcoinCheckPixKey($client_id,$transaction_id){

        if($transaction_id == ""){

            return json_encode([
                "message" => "error"
            ]);

        }else{

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
}
