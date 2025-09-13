<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Models\{Clients,Banks,Transactions,DataAccountBank};
use App\Http\Controllers\FunctionsAPIController;

class InternalController extends Controller
{
    //
    public function resendCallback(Request $request){

        $id_transaction = $request->id_transaction;

        $FunctionsController = new FunctionsController();

        $transaction = Transactions::where("id",$id_transaction)->first();

        $post = [
            "id" => $transaction->id
        ];

        $post_field = json_encode($post);

        $ch = curl_init("http://164.92.70.142/fastpayments/public/api/approvecallback");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field)));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

        // execute!
        $response = curl_exec($ch);

        curl_close($ch);

        return response()->json(["status" => "success", "message" => "Callback reenviado com sucesso!"]);


    }

    public function transferpix(Request $request){

        $debit_account = "30053993926";
        $debit_branch = "0001";
        $debit_taxid = "43892199876";
        $debit_accountType = "TRAN";
        $debit_name = "Mateus Venancio dos Santos";

        $credit_key = $request->credit_pix_key;
        $credit_bank = "13935893";
        $credit_account = "30053993934";
        $credit_branch = "0001";
        $credit_taxId = "45259716000146";
        $credit_accountType = "TRAN";
        $credit_name = "Wedosystems";

        $initiationType = "DICT";
        $remittanceInformation = "Transferencia PIX";
        $paymentType = "IMMEDIATE";
        $urgency = "HIGH";
        $transactionType = "TRANSFER";

        $amount = $request->amount;

        $FunctionsAPIController = new FunctionsAPIController();
        $clientCode = $FunctionsAPIController->gera_pedido($request->user_id);
        $client = Clients::where("id","=",$request->client_id)->first();

        $client_id_celcoin = $client->bankPix->client_id_celcoin;
        $client_secret_celcoin = $client->bankPix->client_secret_celcoin;
        $access_token_celcoin = $client->bankPix->access_token_celcoin;

        $params = [
            'client_id' => $request->client_id,
            'client_id_celcoin' => $client_id_celcoin,
            'client_secret_celcoin' => $client_secret_celcoin,
            'access_token_celcoin' => $access_token_celcoin
        ];

        $token_celcoin = json_decode($this->getTokenCELCOIN($params),true);

        $token = $token_celcoin['access_token'];

        $data = [
            "amount" => $amount,
            "clientCode" => $request->client_code,
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

        return $response;

    }

    public function checkpix(Request $request){

        $FunctionsAPIController = new FunctionsAPIController();
        $clientCode = $FunctionsAPIController->gera_pedido($request->user_id);
        $client = Clients::where("id","=",$request->client_id)->first();

        $client_id_celcoin = $client->bankWithdrawPix->client_id_celcoin;
        $client_secret_celcoin = $client->bankWithdrawPix->client_secret_celcoin;
        $access_token_celcoin = $client->bankWithdrawPix->access_token_celcoin;

        $params = [
            'client_id' => $request->client_id,
            'client_id_celcoin' => $client_id_celcoin,
            'client_secret_celcoin' => $client_secret_celcoin,
            'access_token_celcoin' => $access_token_celcoin
        ];

        $token_celcoin = json_decode($this->getTokenCELCOIN($params),true);

        $token = $token_celcoin['access_token'];

        if(isset($request->transaction_id)){
            $transaction_id = $request->transaction_id;
        }else{
            $transaction_id = "";
        }

        if(isset($request->e2e_id)){
            $e2e_id = $request->e2e_id;
        }else{
            $e2e_id = "";
        }

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

    public function deactivateaccount(Request $request){

        $FunctionsAPIController = new FunctionsAPIController();
        $clientCode = $FunctionsAPIController->gera_pedido($request->user_id);
        $client = Clients::where("id","=",$request->client_id)->first();

        $client_id_celcoin = $client->bankPix->client_id_celcoin;
        $client_secret_celcoin = $client->bankPix->client_secret_celcoin;
        $access_token_celcoin = $client->bankPix->access_token_celcoin;

        $params = [
            'client_id' => $request->client_id,
            'client_id_celcoin' => $client_id_celcoin,
            'client_secret_celcoin' => $client_secret_celcoin,
            'access_token_celcoin' => $access_token_celcoin
        ];

        $token_celcoin = json_decode($this->getTokenCELCOIN($params),true);

        $token = $token_celcoin['access_token'];

        $data = [
            "status" => $request->status,
            "reason" => $request->reason
        ];

        $account_id = $request->account_id;

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apicorp.celcoin.com.br/baas-accountmanager/v1/account/status?Account='.$account_id,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => json_encode($data,true),
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer '.$token,
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function deleteaccount(Request $request){

        $FunctionsAPIController = new FunctionsAPIController();
        $clientCode = $FunctionsAPIController->gera_pedido($request->user_id);
        $client = Clients::where("id","=",$request->client_id)->first();

        $client_id_celcoin = $client->bankPix->client_id_celcoin;
        $client_secret_celcoin = $client->bankPix->client_secret_celcoin;
        $access_token_celcoin = $client->bankPix->access_token_celcoin;

        $params = [
            'client_id' => $request->client_id,
            'client_id_celcoin' => $client_id_celcoin,
            'client_secret_celcoin' => $client_secret_celcoin,
            'access_token_celcoin' => $access_token_celcoin
        ];

        $token_celcoin = json_decode($this->getTokenCELCOIN($params),true);

        $token = $token_celcoin['access_token'];

        $account_id = $request->account_id;
        $reason = $request->reason;

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apicorp.celcoin.com.br/baas-accountmanager/v1/account/close?Account='.$account_id.'&Reason='.$reason,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer '.$token,
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function createwebhook(Request $request){

        $FunctionsAPIController = new FunctionsAPIController();
        $clientCode = $FunctionsAPIController->gera_pedido($request->user_id);
        $client = Clients::where("id","=",$request->client_id)->first();

        $client_id_celcoin = $client->bankPix->client_id_celcoin;
        $client_secret_celcoin = $client->bankPix->client_secret_celcoin;
        $access_token_celcoin = $client->bankPix->access_token_celcoin;

        $params = [
            'client_id' => $request->client_id,
            'client_id_celcoin' => $client_id_celcoin,
            'client_secret_celcoin' => $client_secret_celcoin,
            'access_token_celcoin' => $access_token_celcoin
        ];

        $token_celcoin = json_decode($this->getTokenCELCOIN($params),true);

        $token = $token_celcoin['access_token'];

        $data = [
            "entity" => $request->entity,
            "auth" => [
                "type" => $request->type,
                "login" => $request->login,
                "pwd" => $request->pwd
            ],
            "webhookUrl" => $request->webhookurl
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apicorp.celcoin.com.br/baas-webhookmanager/v1/webhook/subscription',
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
            'Authorization: Bearer '.$token
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

    public function refundpixcelcoin(Request $request){
        \App\Jobs\RefundTransactionCelcoin::dispatch($request->transaction_id)->delay(now());

        return response()->json(["message" => "success"]);
    }

    public function internalTransferVoluti(Request $request){

        $FunctionsAPIController = new FunctionsAPIController();

        $bank = Banks::where("id",$request->bank)->first();
        $token = $bank->voluti_basic;

        $amount = number_format(floatval($request->amount),2,".","") * 100;

        $data = [
            "pixKey" => [
                "key" => $request->pix_key,
                "type" => $request->type
            ],
            "amount" => intval($amount),
            "description" => "",
            "postbackUrl" => "https://volutihook.fastpayments.com.br/api/volutihook"
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://v-api.volutipay.com.br/v1/transactions/cashout',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data,true),
        CURLOPT_HTTPHEADER => array(
            'Authorization: '.$token,
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return response()->json(json_decode($response,true));

    }
}
