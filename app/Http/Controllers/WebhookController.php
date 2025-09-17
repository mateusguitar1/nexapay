<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use App\Http\Controllers\FunctionsController;
use App\Http\Controllers\FunctionsAPIController;
use App\Models\{Clients,Transactions,DataInvoice,Api,Banks,Webhook,ReceiptCelcoin,Extract};

class WebhookController extends Controller
{
    //

    public function webhookBS2(Request $request){

        $FunctionsController = new FunctionsController();

        $payload = json_decode($request->getContent(),true);

        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/webhook-bs2.txt",json_encode($payload));

        $row = Transactions::where("data_bank","=",$payload['NossoNumeroBoleto'])
            ->first();

        if(!empty($row)){

            $client = Clients::where("id","=",$row->client_id)->first();

            DB::beginTransaction();
            try{

                $webhook = Webhook::create([
                    "client_id" => $client->id,
                    "order_id" => $row->order_id,
                    "type_register" => "invoice",
                    "body" => json_encode($payload,true),
                ]);

                DB::commit();

                $webhook_id = $webhook->id;

                \App\Jobs\CheckHookInvoiceBS2::dispatch($webhook_id,$client->id,$row->order_id)->delay(now()->addSeconds('5'));

                return response()->json(["message" => "success"]);

            }catch(exception $e){
                DB::rollback();
            }

        }


    }

    public function webhookBS2Ted(Request $request){

        $FunctionsController = new FunctionsController();

        $payload = json_decode($request->getContent(),true);

        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/webhook-bs2-ted.txt",json_encode($payload));

        return response()->json(array("message" => "success", "body" => $payload));

    }

    public function webhookBS2Extrato(Request $request){

        $FunctionsController = new FunctionsController();

        $payload = json_decode($request->getContent(),true);

        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/webhook-bs2-extrato.txt",json_encode($payload));

        return response()->json(array("message" => "success", "body" => $payload));

    }

    public function pixBS2(Request $request){

        $FunctionsController = new FunctionsController();

        $payload = json_decode($request->getContent(),true);

        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/pixbs2.txt",json_encode($payload));

        if(isset($payload['pix'][0])){

            $row = Transactions::where("type_transaction","deposit")->where("method_transaction","pix")->where("code","=",$payload['pix'][0]['txid'])->first();

            if(!empty($row)){

                $client = Clients::where("id","=",$row->client_id)->first();

                DB::beginTransaction();
                try{

                    $row->update([
                        "payment_id" => $payload['pix'][0]['EndToEndId']
                    ]);

                    $webhook = Webhook::create([
                        "client_id" => $client->id,
                        "order_id" => $row->order_id,
                        "type_register" => "pix",
                        "body" => json_encode($payload,true),
                    ]);

                    DB::commit();

                    $transaction_id = $row->id;
                    $webhook_id = $webhook->id;

                    \App\Jobs\CheckHookPIXBS2::dispatch($transaction_id,$webhook_id)->delay(now()->addSeconds('5'));

                    return response()->json(["message" => "success"]);

                }catch(exception $e){
                    DB::rollback();
                }

            }

        }

    }

    public function openPixWebhook(Request $request){

        $FunctionsController = new FunctionsController();

        $payload = json_decode($request->getContent(),true);

        $path_name = "fastlogs-".date("Y-m-d");

        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
        }

        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/openpixwebhook.txt",json_encode($payload));

        if(isset($payload['event'])){
            if($payload['event'] == "OPENPIX:TRANSACTION_RECEIVED"){

                $transaction = Transactions::where("type_transaction","deposit")->where("code","=",$payload['pix']['charge']['correlationID'])->first();

                if(!empty($transaction)){

                    $client = Clients::where("id",$transaction->client_id)->first();

                    if($transaction->method_transaction == "ted"){

                        if($transaction->status != "confirmed"){

                            DB::beginTransaction();
                            try{

                                $transaction->update([
                                    "payment_id" => $payload['pix']['charge']['globalID']
                                ]);

                                $webhook = Webhook::create([
                                    "client_id" => $client->id,
                                    "order_id" => $transaction->order_id,
                                    "type_register" => "ted",
                                    "body" => json_encode($payload,true),
                                ]);

                                DB::commit();

                                $transaction_id = $transaction->id;
                                $webhook_id = $webhook->id;

                                \App\Jobs\CheckHookPIXOPENPIXTED::dispatch($transaction_id,$webhook_id)->delay(now());

                                return response()->json(["message" => "success"]);

                            }catch(exception $e){
                                DB::rollback();
                            }

                        }

                    }elseif($transaction->method_transaction == "pix"){

                        if($transaction->status != "confirmed"){

                            DB::beginTransaction();
                            try{

                                $transaction->update([
                                    "payment_id" => $payload['pix']['charge']['globalID']
                                ]);

                                $webhook = Webhook::create([
                                    "client_id" => $client->id,
                                    "order_id" => $transaction->order_id,
                                    "type_register" => "pix",
                                    "body" => json_encode($payload,true),
                                ]);

                                DB::commit();

                                $transaction_id = $transaction->id;
                                $webhook_id = $webhook->id;

                                \App\Jobs\CheckHookPIXOPENPIX::dispatch($transaction_id,$webhook_id)->delay(now());

                                return response()->json(["message" => "success"]);

                            }catch(exception $e){
                                DB::rollback();
                            }

                        }

                    }

                }

            }
        }

    }

    public function asaasWebhook(Request $request){

        $FunctionsController = new FunctionsController();

        $payload = json_decode($request->getContent(),true);

        $path_name = "asas-".date("Y-m-d");

        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
        }

        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($payload));

        if(isset($payload['event'])){
            if($payload['event'] == "PAYMENT_RECEIVED"){

                $transaction = Transactions::where("type_transaction","deposit")->where("code","=",$payload['payment']['externalReference'])->first();

                if(!empty($transaction)){

                    $client = Clients::where("id",$transaction->client_id)->first();

                    DB::beginTransaction();
                    try{

                        $transaction->update([
                            "payment_id" => $payload['payment']['id']
                        ]);

                        $webhook = Webhook::create([
                            "client_id" => $client->id,
                            "order_id" => $transaction->order_id,
                            "type_register" => "pix",
                            "body" => json_encode($payload,true),
                        ]);

                        DB::commit();

                        $transaction_id = $transaction->id;
                        $webhook_id = $webhook->id;

                        \App\Jobs\CheckHookPIXASAAS::dispatch($transaction_id,$webhook_id)->delay(now()->addSeconds('5'));

                        return response()->json(["message" => "success"]);

                    }catch(exception $e){
                        DB::rollback();
                    }

                }

            }
        }

    }

    public function volutiWebhook(Request $request){

        $FunctionsController = new FunctionsController();

        $payload = json_decode($request->getContent(),true);

        $path_name = "get-webhook-voluti-".date("Y-m-d");

        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
        }

        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($payload));

            if(isset($payload['data']['operation'])){
                if($payload['data']['operation'] == "cashin"){

                    if(isset($payload['data']['conciliationId'])){
                        $transaction = Transactions::where("type_transaction","deposit")->where("status","pending")->where("payment_id","=",$payload['data']['conciliationId'])->first();

                        if(!empty($transaction)){


                            $user_data = json_decode(base64_decode($transaction->user_account_data),true);

                            // if($transaction->client_id != "17"){
                            $client = Clients::where("id",$transaction->client_id)->first();

                            if($transaction->method_transaction == "pix"){

                                if($transaction->status != "confirmed"){

                                    DB::beginTransaction();
                                    try{

                                        $webhook = Webhook::create([
                                            "client_id" => $client->id,
                                            "order_id" => $transaction->order_id,
                                            "type_register" => "pix",
                                            "body" => json_encode($payload,true),
                                        ]);

                                        DB::commit();

                                        $transaction_id = $transaction->id;
                                        $webhook_id = $webhook->id;

                                        \App\Jobs\CheckHookPIXVoluti::dispatch($transaction_id,$webhook_id)->delay(now("30"));

                                        return response()->json(["message" => "success"]);

                                    }catch(exception $e){
                                        DB::rollback();
                                    }

                                }

                            }else{
                                return response()->json(["message" => "transaction not found"]);
                            }

                        }
                    }else{
                        return response()->json(["message" => "out other conditions", "content" => "dont have conciliationId"]);
                    }

                }elseif($payload['data']['operation'] == "cashout"){

                    $transaction = Transactions::where("type_transaction","withdraw")->where("status","pending")->where("payment_id","=",$payload['data']['conciliationId'])->first();

                    if(isset($transaction)){

                        $client = $transaction->client;

                        $path_name = "voluti-webhook-payment-".date("Y-m-d");

                        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                        }

                        $data_request = [
                            "payload" => $payload,
                            "created_at" => date("Y-m-d H:i:s"),
                            "transaction_id" => $transaction->id,
                            "order_id" => $transaction->order_id,
                            "client_id" => $client->id
                        ];

                        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($data_request));

                        if($payload['data']['status'] == "paid"){

                            $webhook = Webhook::create([
                                "client_id" => $client->id,
                                "order_id" => $transaction->order_id,
                                "type_register" => "pix",
                                "body" => json_encode($payload,true),
                            ]);

                            DB::commit();

                            $transaction_id = $transaction->id;
                            $webhook_id = $webhook->id;
                            \App\Jobs\ConfirmPaymentVoluti::dispatch($transaction_id)->delay(now()->addSeconds('5'));
                        }

                        return response()->json(["message" => "success", "content" => "received successfully"]);

                    }

                    return response()->json(["message" => "error", "content" => "Transaction not found"]);


                }else{
                    return response()->json(["message" => "out other conditions", "content" => "not cashin"]);
                }

            }

            return response()->json(["message" => "sei la"]);

    }

    public function luxtakWebhook(Request $request){

        $FunctionsController = new FunctionsController();

        $payload = json_decode($request->getContent(),true);

        $path_name = "get-webhook-luxtak-".date("Y-m-d");

        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
        }

        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($payload));

        if(isset($payload['out_trade_no'])){

            if($payload['trade_status'] == "SUCCESS"){
                $transaction = Transactions::where("status","pending")->where("code","=",$payload['out_trade_no'])->first();

                if(!empty($transaction)){


                    $user_data = json_decode(base64_decode($transaction->user_account_data),true);

                    // if($transaction->client_id != "17"){
                    $client = Clients::where("id",$transaction->client_id)->first();

                    if($transaction->method_transaction == "pix"){

                        if($transaction->status != "confirmed"){

                            DB::beginTransaction();
                            try{

                                $webhook = Webhook::create([
                                    "client_id" => $client->id,
                                    "order_id" => $transaction->order_id,
                                    "type_register" => "pix",
                                    "body" => json_encode($payload,true),
                                ]);

                                DB::commit();

                                $transaction_id = $transaction->id;
                                $webhook_id = $webhook->id;

                                \App\Jobs\CheckHookPIXLuxTakNEW::dispatch($transaction_id,$webhook_id)->delay(now("5"));

                                return response()->json(["message" => "success", "transaction" => $transaction_id, "webhook" => $webhook_id]);

                            }catch(exception $e){
                                DB::rollback();
                            }

                        }

                    }else{
                        return response()->json(["message" => "method not found"]);
                    }

                }else{
                    return response()->json(["message" => "transaction not found"]);
                }
            }
        }else{
            return response()->json(["message" => "out other conditions", "content" => "dont have conciliationId"]);
        }

    }

    public function suitpayWebhook(Request $request){

        $FunctionsController = new FunctionsController();

        $payload = json_decode($request->getContent(),true);

        $path_name = "get-webhook-suitpay-".date("Y-m-d");

        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
        }

        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($payload));

        if(isset($payload['idTransaction'])){

            if($payload['statusTransaction'] == "PAID_OUT"){
                $transaction = Transactions::where("status","pending")->where("payment_id","=",$payload['idTransaction'])->first();

                if(!empty($transaction)){

                    $user_data = json_decode(base64_decode($transaction->user_account_data),true);

                    // if($transaction->client_id != "17"){
                    $client = Clients::where("id",$transaction->client_id)->first();

                    if($transaction->method_transaction == "pix"){

                        if($transaction->status != "confirmed"){

                            DB::beginTransaction();
                            try{

                                $webhook = Webhook::create([
                                    "client_id" => $client->id,
                                    "order_id" => $transaction->order_id,
                                    "type_register" => "pix",
                                    "body" => json_encode($payload,true),
                                ]);

                                DB::commit();

                                $transaction_id = $transaction->id;
                                $webhook_id = $webhook->id;

                                \App\Jobs\CheckHookPIXSuitPayNew::dispatch($transaction_id,$webhook_id)->delay(now("2"));

                                return response()->json(["message" => "success", "transaction" => $transaction_id, "webhook" => $webhook_id]);

                            }catch(exception $e){
                                DB::rollback();
                            }

                        }

                    }else{
                        return response()->json(["message" => "method not found"]);
                    }

                }else{
                    return response()->json(["message" => "transaction not found"]);
                }
            }
        }else{
            return response()->json(["message" => "out other conditions", "content" => "dont have conciliationId"]);
        }

    }

    public function metapayWebhook(Request $request){
        $FunctionsController = new FunctionsController();

        $payload = json_decode($request->getContent(),true);

        $path_name = "get-webhook-metapay-".date("Y-m-d");

        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
        }

        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($payload));

        if(isset($payload['transaction_id'])){
            $transaction = Transactions::where("status","pending")->where("payment_id","=",$payload['transaction_id'])->first();

            if($payload['type'] == "deposit"){

                if($payload['status'] == "paid"){

                    if(!empty($transaction)){

                        $user_data = json_decode(base64_decode($transaction->user_account_data),true);
                        $client = Clients::where("id",$transaction->client_id)->first();

                        if($transaction->method_transaction == "pix"){

                            if($transaction->status != "confirmed"){

                                DB::beginTransaction();
                                try{

                                    $webhook = Webhook::create([
                                        "client_id" => $client->id,
                                        "order_id" => $transaction->order_id,
                                        "type_register" => "pix",
                                        "body" => json_encode($payload,true),
                                    ]);

                                    DB::commit();

                                    $transaction_id = $transaction->id;
                                    $webhook_id = $webhook->id;

                                    \App\Jobs\CheckHookPIXMetaPay::dispatch($transaction_id,$webhook_id)->delay(now("2"));

                                    return response()->json(["message" => "success", "transaction" => $transaction_id, "webhook" => $webhook_id]);

                                }catch(exception $e){
                                    DB::rollback();
                                }

                            }

                        }else{
                            return response()->json(["message" => "method not found"]);
                        }

                    }else{
                        return response()->json(["message" => "transaction not found"]);
                    }
                }

            }elseif($payload['type'] == "withdraw"){

                if($payload['status'] == "paid"){

                    if(!empty($transaction)){

                        $user_data = json_decode(base64_decode($transaction->user_account_data),true);

                        // if($transaction->client_id != "17"){
                        $client = Clients::where("id",$transaction->client_id)->first();

                        if($transaction->method_transaction == "pix"){

                            if($transaction->status != "confirmed"){

                                DB::beginTransaction();
                                try{

                                    $webhook = Webhook::create([
                                        "client_id" => $client->id,
                                        "order_id" => $transaction->order_id,
                                        "type_register" => "pix",
                                        "body" => json_encode($payload,true),
                                    ]);

                                    DB::commit();

                                    $transaction_id = $transaction->id;
                                    $webhook_id = $webhook->id;

                                    \App\Jobs\CheckHookPIXMetaPayWithdraw::dispatch($transaction_id,$webhook_id)->delay(now("5"));

                                    return response()->json(["message" => "success"]);

                                }catch(exception $e){
                                    DB::rollback();
                                }

                            }

                        }else{
                            return response()->json(["message" => "method not found"]);
                        }

                    }else{
                        return response()->json(["message" => "transaction not found"]);
                    }
                }

            }


        }else{
            return response()->json(["message" => "out other conditions", "content" => "dont have conciliationId"]);
        }
    }

    public function luxtakWebhookWithdraw(Request $request){

        $FunctionsController = new FunctionsController();

        $payload = json_decode($request->getContent(),true);

        $path_name = "get-webhook-withdraw-luxtak-".date("Y-m-d");

        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
        }

        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($payload));

        // return response()->json(["status" => "success", "message" => "received"]);

        if(isset($payload['custom_code'])){
            $transaction = Transactions::where("status","pending")->where("code","=",$payload['custom_code'])->first();

            if(!empty($transaction)){

                $user_data = json_decode(base64_decode($transaction->user_account_data),true);

                // if($transaction->client_id != "17"){
                $client = Clients::where("id",$transaction->client_id)->first();

                if($transaction->method_transaction == "pix"){

                    if($transaction->status != "confirmed"){

                        DB::beginTransaction();
                        try{

                            $webhook = Webhook::create([
                                "client_id" => $client->id,
                                "order_id" => $transaction->order_id,
                                "type_register" => "pix",
                                "body" => json_encode($payload,true),
                            ]);

                            DB::commit();

                            $transaction_id = $transaction->id;
                            $webhook_id = $webhook->id;

                            \App\Jobs\CheckHookPIXLuxTakWithdrawNew::dispatch($transaction_id,$webhook_id)->delay(now("5"));

                            return response()->json(["message" => "success"]);

                        }catch(exception $e){
                            DB::rollback();
                        }

                    }

                }else{
                    return response()->json(["message" => "method not found"]);
                }

            }else{
                return response()->json(["message" => "transaction not found"]);
            }
        }else{
            return response()->json(["message" => "out other conditions", "content" => "dont have conciliationId"]);
        }

    }

    public function suitpayWebhookWithdraw(Request $request){

        $FunctionsController = new FunctionsController();

        $payload = json_decode($request->getContent(),true);

        $path_name = "get-webhook-withdraw-suitpay-".date("Y-m-d");

        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
        }

        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($payload));

        // return response()->json(["status" => "success", "message" => "received"]);

        if(isset($payload['idTransaction'])){
            $transaction = Transactions::where("status","pending")->where("payment_id","=",$payload['idTransaction'])->first();

            if(!empty($transaction)){

                $user_data = json_decode(base64_decode($transaction->user_account_data),true);

                // if($transaction->client_id != "17"){
                $client = Clients::where("id",$transaction->client_id)->first();

                if($transaction->method_transaction == "pix"){

                    if($transaction->status != "confirmed"){

                        DB::beginTransaction();
                        try{

                            $webhook = Webhook::create([
                                "client_id" => $client->id,
                                "order_id" => $transaction->order_id,
                                "type_register" => "pix",
                                "body" => json_encode($payload,true),
                            ]);

                            DB::commit();

                            $transaction_id = $transaction->id;
                            $webhook_id = $webhook->id;

                            \App\Jobs\CheckHookPIXSuitPayWithdraw::dispatch($transaction_id,$webhook_id)->delay(now("5"));

                            return response()->json(["message" => "success"]);

                        }catch(exception $e){
                            DB::rollback();
                        }

                    }

                }else{
                    return response()->json(["message" => "method not found"]);
                }

            }else{
                return response()->json(["message" => "transaction not found"]);
            }
        }else{
            return response()->json(["message" => "out other conditions", "content" => "dont have conciliationId"]);
        }

    }

    public function hubapihook(Request $request){

        $FunctionsController = new FunctionsController();

        $payload = json_decode($request->getContent(),true);

        $path_name = "get-webhook-voluti-".date("Y-m-d");

        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
        }

        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($payload));

        if(isset($payload['type'])){
            if($payload['type'] == 'transaction'){
                if($payload['data']['operation'] == 'cashin'){

                    $conciliationId = $payload['data']['conciliationId'];
                    $transaction = Transactions::where("type_transaction","deposit")->where("status","pending")->where("payment_id","=",$conciliationId)->first();

                    if(!empty($transaction)){


                        $user_data = json_decode(base64_decode($transaction->user_account_data),true);

                        // if($transaction->client_id != "17"){
                        $client = Clients::where("id",$transaction->client_id)->first();

                        if($transaction->method_transaction == "pix"){

                            if($transaction->status != "confirmed"){

                                DB::beginTransaction();
                                try{

                                    $webhook = Webhook::create([
                                        "client_id" => $client->id,
                                        "order_id" => $transaction->order_id,
                                        "type_register" => "pix",
                                        "body" => json_encode($payload,true),
                                    ]);

                                    DB::commit();

                                    $transaction_id = $transaction->id;
                                    $webhook_id = $webhook->id;

                                    \App\Jobs\CheckHookPIXVoluti::dispatch($transaction_id,$webhook_id)->delay(now("30"));

                                    return response()->json(["message" => "success"]);

                                }catch(exception $e){
                                    DB::rollback();
                                }

                            }

                        }else{
                            return response()->json(["message" => "transaction not found"]);
                        }

                    }

                }elseif($payload['data']['operation'] == 'cashout'){

                    $conciliationId = $payload['data']['conciliationId'];
                    $transaction = Transactions::where("type_transaction","withdraw")->where("status","pending")->where("payment_id","=",$conciliationId)->first();

                    if(isset($transaction)){

                        $client = $transaction->client;

                        $path_name = "hubapi-webhook-payment-".date("Y-m-d");

                        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                        }

                        $data_request = [
                            "payload" => $payload,
                            "created_at" => date("Y-m-d H:i:s"),
                            "transaction_id" => $transaction->id,
                            "order_id" => $transaction->order_id,
                            "client_id" => $client->id
                        ];

                        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($data_request));

                        if($payload['data']['status'] == "paid"){

                            $webhook = Webhook::create([
                                "client_id" => $client->id,
                                "order_id" => $transaction->order_id,
                                "type_register" => "pix",
                                "body" => json_encode($payload,true),
                            ]);

                            DB::commit();

                            $transaction_id = $transaction->id;
                            $webhook_id = $webhook->id;
                            \App\Jobs\ConfirmPaymentHUBAPI::dispatch($transaction_id)->delay(now()->addSeconds('5'));
                        }

                        return response()->json(["message" => "success", "content" => "received successfully"]);

                    }

                    return response()->json(["message" => "error", "content" => "Transaction not found"]);

                }
            }
        }

            if(isset($payload['data']['operation'])){
                if($payload['data']['operation'] == "cashin"){

                    if(isset($payload['data']['conciliationId'])){
                        $transaction = Transactions::where("type_transaction","deposit")->where("status","pending")->where("payment_id","=",$payload['data']['conciliationId'])->first();

                        if(!empty($transaction)){


                            $user_data = json_decode(base64_decode($transaction->user_account_data),true);

                            // if($transaction->client_id != "17"){
                            $client = Clients::where("id",$transaction->client_id)->first();

                            if($transaction->method_transaction == "pix"){

                                if($transaction->status != "confirmed"){

                                    DB::beginTransaction();
                                    try{

                                        $webhook = Webhook::create([
                                            "client_id" => $client->id,
                                            "order_id" => $transaction->order_id,
                                            "type_register" => "pix",
                                            "body" => json_encode($payload,true),
                                        ]);

                                        DB::commit();

                                        $transaction_id = $transaction->id;
                                        $webhook_id = $webhook->id;

                                        \App\Jobs\CheckHookPIXVoluti::dispatch($transaction_id,$webhook_id)->delay(now("30"));

                                        return response()->json(["message" => "success"]);

                                    }catch(exception $e){
                                        DB::rollback();
                                    }

                                }

                            }else{
                                return response()->json(["message" => "transaction not found"]);
                            }

                        }
                    }else{
                        return response()->json(["message" => "out other conditions", "content" => "dont have conciliationId"]);
                    }

                }elseif($payload['data']['operation'] == "cashout"){

                    $transaction = Transactions::where("type_transaction","withdraw")->where("status","pending")->where("payment_id","=",$payload['data']['conciliationId'])->first();

                    if(isset($transaction)){

                        $client = $transaction->client;

                        $path_name = "voluti-webhook-payment-".date("Y-m-d");

                        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                        }

                        $data_request = [
                            "payload" => $payload,
                            "created_at" => date("Y-m-d H:i:s"),
                            "transaction_id" => $transaction->id,
                            "order_id" => $transaction->order_id,
                            "client_id" => $client->id
                        ];

                        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($data_request));

                        if($payload['data']['status'] == "paid"){

                            $webhook = Webhook::create([
                                "client_id" => $client->id,
                                "order_id" => $transaction->order_id,
                                "type_register" => "pix",
                                "body" => json_encode($payload,true),
                            ]);

                            DB::commit();

                            $transaction_id = $transaction->id;
                            $webhook_id = $webhook->id;
                            \App\Jobs\ConfirmPaymentVoluti::dispatch($transaction_id)->delay(now()->addSeconds('5'));
                        }

                        return response()->json(["message" => "success", "content" => "received successfully"]);

                    }

                    return response()->json(["message" => "error", "content" => "Transaction not found"]);


                }else{
                    return response()->json(["message" => "out other conditions", "content" => "not cashin"]);
                }

            }

            return response()->json(["message" => "sei la"]);

    }

    public function volutiNewWebhookDeposit(Request $request){

        $FunctionsController = new FunctionsController();

        $payload = json_decode($request->getContent(),true);

        $path_name = "deposit-webhook-volutinew-".date("Y-m-d");

        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
        }

        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($payload));

        if(isset($payload['data']['txId'])){

            if($payload['data']['status'] == "LIQUIDATED"){

                $transaction = Transactions::where("type_transaction","deposit")->where("status","pending")->where("payment_id",$payload['data']['txId'])->first();

                if(!empty($transaction)){

                    $user_data = json_decode(base64_decode($transaction->user_account_data),true);

                    // if($transaction->client_id != "17"){
                    $client = Clients::where("id",$transaction->client_id)->first();

                    if($transaction->method_transaction == "pix"){

                        if($transaction->status != "confirmed"){

                            DB::beginTransaction();
                            try{

                                $webhook = Webhook::create([
                                    "client_id" => $client->id,
                                    "order_id" => $transaction->order_id,
                                    "type_register" => "pix",
                                    "body" => json_encode($payload,true),
                                ]);

                                DB::commit();

                                $transaction_id = $transaction->id;
                                $webhook_id = $webhook->id;

                                \App\Jobs\CheckHookPIXVolutiNew::dispatch($transaction_id,$webhook_id)->delay(now("10"));

                                return response()->json(["message" => "success"]);

                            }catch(exception $e){
                                DB::rollback();
                            }

                        }

                    }else{
                        return response()->json(["message" => "transaction not found"]);
                    }

                }else{



                }

            }


        }

    }

    public function volutiNewWebhookWithdraw(Request $request){

        $FunctionsController = new FunctionsController();

        $payload = json_decode($request->getContent(),true);

        $path_name = "withdraw-webhook-volutinew-".date("Y-m-d");

        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
        }

        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($payload));

        $conciliationId = $payload['data']['endToEndId'];
        $transaction = Transactions::where("type_transaction","withdraw")->where("status","pending")->where("payment_id",$conciliationId)->first();

        if(isset($transaction)){

            $client = $transaction->client;

            if($payload['data']['status'] == "LIQUIDATED"){

                $webhook = Webhook::create([
                    "client_id" => $client->id,
                    "order_id" => $transaction->order_id,
                    "type_register" => "pix",
                    "body" => json_encode($payload,true),
                ]);

                DB::commit();

                $transaction_id = $transaction->id;
                $webhook_id = $webhook->id;
                \App\Jobs\ConfirmPaymentVolutiNew::dispatch($transaction_id)->delay(now()->addSeconds('5'));
            }

            return response()->json(["message" => "success", "content" => "received successfully"]);

        }

    }

    public function celcoinWebhook(Request $request){

        $hash = $request->header('authorization');

        $FunctionsController = new FunctionsController();

        $payload = json_decode($request->getContent(),true);

        $path_name = "get-webhook-celcoin-new-".date("Y-m-d");

        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
        }

        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($payload));

        if($hash == "Basic Y2VsY29pbndlYmhvb2s6MnFJaEMwVmxMdzFnSW9m"){

            $path_name = "celcoin-authenticated-new-".date("Y-m-d");

            if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
            }

            $data_request_auth = [
                "payload" => $payload,
                "created_at" => date("Y-m-d H:i:s")
            ];

            $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($data_request_auth));

            if(isset($payload['RequestBody']['EndToEndId'])){
                if($payload['RequestBody']['TransactionType'] == "RECEIVEPIX"){

                    if(isset($payload['RequestBody']['transactionIdBRCode'])){
                        $transaction = Transactions::where("type_transaction","deposit")->where("status","pending")->where("payment_id","=",$payload['RequestBody']['transactionIdBRCode'])->first();

                        if(!empty($transaction)){


                            $user_data = json_decode(base64_decode($transaction->user_account_data),true);

                            // if($transaction->client_id != "17"){
                            $client = Clients::where("id",$transaction->client_id)->first();

                            if($transaction->method_transaction == "pix"){

                                if($transaction->status != "confirmed"){

                                    DB::beginTransaction();
                                    try{

                                        $webhook = Webhook::create([
                                            "client_id" => $client->id,
                                            "order_id" => $transaction->order_id,
                                            "type_register" => "pix",
                                            "body" => json_encode($payload,true),
                                        ]);

                                        DB::commit();

                                        $transaction_id = $transaction->id;
                                        $webhook_id = $webhook->id;

                                        \App\Jobs\CheckHookPIXCelcoin::dispatch($transaction_id,$webhook_id)->delay(now());

                                        return response()->json(["message" => "success"]);

                                    }catch(exception $e){
                                        DB::rollback();
                                    }

                                }

                            }else{
                                return response()->json(["message" => "transaction not found"]);
                            }

                        }
                    }else{

                        $client = Clients::where("id","25")->first();

                        if($payload['RequestBody']['CreditParty']['TaxId'] == "40121024000113" && $payload['RequestBody']['CreditParty']['Key'] == "1997fc7e-6e8e-450e-ba8b-25ca2b1c6fbe"){

                            DB::beginTransaction();

                            try{

                                $atual_date = date("Y-m-d H:i:s");
                                // Taxas
                                $tax = $client->tax;
                                $bank = $client->bankPix;

                                $cotacao_dolar = "1";
                                $spread_deposit = "0";
                                $cotacao_dolar_markup = "1";

                                $final_amount = floatval($payload['RequestBody']['Amount']);
                                $percent_fee = ($final_amount * ($tax->pix_percent / 100));
                                $fixed_fee = $tax->pix_absolute;
                                if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                                $comission = ($percent_fee + $fixed_fee);
                                if($comission < $tax->min_fee_pix){ $comission = $tax->min_fee_pix; $min_fee = $tax->min_fee_pix; }else{ $min_fee = 0.00; }

                                $days_safe_pix = $client->days_safe_pix;
                                $date_confirmed_bank = date("Y-m-d",strtotime("+".$days_safe_pix." days"))." 00:00:00";
                                $valor_pago = $final_amount;

                                $receita_comission = ($comission * $cotacao_dolar);
                                $receita_spread_deposito = ($valor_pago / $cotacao_dolar - $final_amount) * $cotacao_dolar;

                                $user_data = array(
                                    "name" => $payload['RequestBody']['DebitParty']['Name'],
                                    "document" => $payload['RequestBody']['DebitParty']['TaxId'],
                                    "bank_name" => $payload['RequestBody']['DebitParty']['Bank'],
                                    "holder" => "",
                                    "agency" => $payload['RequestBody']['DebitParty']['Branch'],
                                    "account_number" => $payload['RequestBody']['DebitParty']['Account'],
                                    "user_id" => "999999",
                                );

                                $user_account_data = base64_encode(json_encode($user_data));

                                $transaction = Transactions::create([
                                    "client_id" => "25",
                                    "status" => "confirmed",
                                    "user_id" => "999999",
                                    "order_id" => "999999",
                                    "user_name" => $payload['RequestBody']['DebitParty']['Name'],
                                    "user_account_data" => $user_account_data,
                                    "type_transaction" => "deposit",
                                    "method_transaction" => "pix",
                                    "amount_solicitation" => $final_amount,
                                    "amount_confirmed" => $final_amount,
                                    "final_amount" => $final_amount,
                                    "quote" => $cotacao_dolar,
                                    "percent_markup" => $spread_deposit,
                                    "quote_markup" => $cotacao_dolar_markup,
                                    "fixed_fee" => $fixed_fee,
                                    "percent_fee" => $percent_fee,
                                    "comission" => $comission,
                                    "min_fee" => $min_fee,
                                    "confirmed_bank" => "1",
                                    "solicitation_date" => $atual_date,
                                    "paid_date" => $atual_date,
                                    "final_date" => $atual_date,
                                    "disponibilization_date" => $date_confirmed_bank,
                                    "receita_spread" => $receita_spread_deposito,
                                    "receita_comission" => $receita_comission,
                                ]);

                                // // Deposit
                                Extract::create([
                                    "transaction_id" => $transaction->id,
                                    "order_id" => $transaction->order_id,
                                    "client_id" => $transaction->client_id,
                                    "user_id" => $transaction->user_id,
                                    "bank_id" => $transaction->id_bank,
                                    "type_transaction_extract" => "cash-in",
                                    "description_code" => "MD02",
                                    "description_text" => "Depósito por Pix",
                                    "cash_flow" => $transaction->amount_solicitation,
                                    "final_amount" => $transaction->final_amount,
                                    "quote" => $transaction->quote,
                                    "quote_markup" => $transaction->quote_markup,
                                    "receita" => 0.00,
                                    "disponibilization_date" => $transaction->disponibilization_date,
                                ]);
                                // // Comission
                                Extract::create([
                                    "transaction_id" => $transaction->id,
                                    "order_id" => $transaction->order_id,
                                    "client_id" => $transaction->client_id,
                                    "user_id" => $transaction->user_id,
                                    "bank_id" => $transaction->id_bank,
                                    "type_transaction_extract" => "cash-out",
                                    "description_code" => "CM03",
                                    "description_text" => "Comissão sobre Depósito de Pix",
                                    "cash_flow" => ($transaction->comission * (-1)),
                                    "final_amount" => ($transaction->comission * (-1)),
                                    "quote" => $transaction->quote,
                                    "quote_markup" => $transaction->quote_markup,
                                    "receita" => 0.00,
                                    "disponibilization_date" => $transaction->disponibilization_date,
                                ]);

                                DB::commit();

                                return response()->json(["message" => "success"]);

                            }catch(Exception $e){
                                return DB::rollBack();
                            }

                        }else{
                            return response()->json(["message" => "error", "content" => "received"]);
                        }

                    }

                    return response()->json(["message" => "success", "content" => "received"]);

                }

                if($payload['RequestBody']['TransactionType'] == "PAYMENT"){

                    if($payload['RequestBody']['StatusCode']['StatusId'] == "3"){
                        return response()->json(["message" => "success", "content" => "received successfully"]);
                    }

                    $receiptCelcoin = ReceiptCelcoin::where("receipt",$payload['RequestBody']['EndToEndId'])->first();
                    if(isset($receiptCelcoin)){
                        $transaction = Transactions::where("id",$receiptCelcoin->transaction_id)->first();
                        $client = $transaction->client;
                        $id_bank_withdraw = $client->bank_withdraw_permition;
                        $bank_withdraw = Banks::where("id",$id_bank_withdraw)->first();

                        $path_name = "celcoin-webhook-payment-".date("Y-m-d");

                        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                        }

                        $data_request = [
                            "payload" => $payload,
                            "created_at" => date("Y-m-d H:i:s"),
                            "transaction_id" => $transaction->id,
                            "order_id" => $transaction->order_id,
                            "client_id" => $client->id
                        ];

                        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($data_request));

                        if($payload['RequestBody']['StatusCode']['StatusId'] == "2"){

                            if(isset($transaction)){

                                \App\Jobs\ConfirmPaymentPIXCelcoin::dispatch($transaction->id,$bank_withdraw->id)->delay(now()->addSeconds('5'));

                            }

                        }elseif($payload['RequestBody']['StatusCode']['StatusId'] == "3"){

                            if(isset($transaction)){

                                \App\Jobs\CancelPaymentPIXCelcoin::dispatch($transaction->id,$bank_withdraw->id,$payload['RequestBody']['Error']['Description'],$payload['endToEndId'])->delay(now()->addSeconds('5'));

                            }

                        }
                    }else{

                        return response()->json(["message" => "success", "content" => "received successfully"]);

                    }

                }

                return response()->json(["message" => "out other conditions", "content" => "received"]);
            }

            return response()->json(["message" => "sei la"]);

        }else{
            $path_name = "celcoin-unauthenticated-".date("Y-m-d");

            if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
            }

            $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($payload));

            return response()->json(["message" => "error", "reason" => "Basic Authentication Invalid"]);
        }

    }

    public function shipayWebhook(Request $request){

        $FunctionsController = new FunctionsController();

        $payload = json_decode($request->getContent(),true);

        $path_name = "webhook-shipay-".date("Y-m-d");

        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
        }

        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($payload));

        $payment_id = $payload['order_id'];

        $transaction = Transactions::where("payment_id",$payment_id)->first();

        if(!isset($transaction)){
            return response()->json(["message" => "content not found"]);
        }

        $old_status = $transaction->status;

        $client = Clients::where("id","=",$transaction->client_id)->first();

        $get_status = json_decode($this->getStatusShipay($transaction->id_bank,$transaction->payment_id),true);

        $path_name = "status-shipay-".date("Y-m-d");

        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
        }

        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($get_status));

        if($get_status['status'] == "approved"){

            DB::beginTransaction();

            try{

                $webhook = Webhook::create([
                    "client_id" => $client->id,
                    "order_id" => $transaction->order_id,
                    "type_register" => "pix",
                    "body" => json_encode($payload,true),
                ]);

                DB::commit();

                $transaction_id = $transaction->id;
                $webhook_id = $webhook->id;

                \App\Jobs\ApproveHookPIXShipay::dispatch($transaction_id,$webhook_id)->delay(now()->addSeconds('5'));

                return response()->json(["message" => "success"]);

            }catch(Exception $e){
                DB::rollback();
            }

        }elseif($get_status['status'] == "refunded"){

            DB::beginTransaction();

            $date = date("Y-m-d H:i:s");

            try{

                if($old_status == "confirmed"){
                    $transaction->update([
                        "status" => "refund",
                        "refund_date" => $date,
                        "final_date" => $date
                    ]);
                }elseif($old_status == "pending"){
                    $transaction->update([
                        "status" => "canceled",
                        "canceled_date" => $date,
                        "final_date" => $date
                    ]);
                }

                DB::commit();

                if($old_status == "pending"){
                    $post = [
                        "order_id" => $transaction->order_id,
                        "user_id" => $transaction->user_id,
                        "solicitation_date" => $transaction->solicitation_date,
                        "cancel_date" => $date,
                        "code_identify" => $transaction->code,
                        "amount_solicitation" => $transaction->amount_solicitation,
                        "status" => "canceled",
                    ];
                }elseif($old_status == "confirmed"){
                    $post = [
                        "order_id" => $transaction->order_id,
                        "user_id" => $transaction->user_id,
                        "solicitation_date" => $transaction->solicitation_date,
                        "refund_date" => $date,
                        "code_identify" => $transaction->code,
                        "amount_solicitation" => $transaction->amount_solicitation,
                        "status" => "refund",
                    ];
                }


                $post_field = json_encode($post);

                $ch2 = curl_init("https://webhook.site/8585f53e-f752-46f0-a3b2-2a636f950f8d");

                curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch2, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'Token:'.$client->key->authorization_deposit));
                curl_setopt($ch2, CURLOPT_POSTFIELDS, $post_field);

                // execute!
                $response2 = curl_exec($ch2);
                $http_status2  = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

                // close the connection, release resources used
                curl_close($ch2);

            }catch(Excelption $e){
                DB::rollback();
            }

        }elseif($get_status['status'] == "cancelled"){

            DB::beginTransaction();

            $cancel_date = date("Y-m-d H:i:s");

            try{

                $transaction->update([
                    "status" => "canceled",
                    "cancel_date" => $cancel_date,
                    "final_date" => $cancel_date
                ]);

                DB::commit();

                $post = [
                    "order_id" => $transaction->order_id,
                    "user_id" => $transaction->user_id,
                    "solicitation_date" => $transaction->solicitation_date,
                    "cancel_date" => $cancel_date,
                    "code_identify" => $transaction->code,
                    "amount_solicitation" => $transaction->amount_solicitation,
                    "status" => "canceled",
                ];

                $post_field = json_encode($post);

                $ch2 = curl_init("https://webhook.site/8585f53e-f752-46f0-a3b2-2a636f950f8d");

                curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch2, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'Token:'.$client->key->authorization_deposit));
                curl_setopt($ch2, CURLOPT_POSTFIELDS, $post_field);

                // execute!
                $response2 = curl_exec($ch2);
                $http_status2  = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

                // close the connection, release resources used
                curl_close($ch2);

            }catch(Excelption $e){
                DB::rollback();
            }

        }

    }

    public function getStatusShipay($bank_id,$payment_id){

        $FunctionsAPIController = new FunctionsAPIController();

        $bank = Banks::where("id",$bank_id)->first();

        $response_token = json_decode($FunctionsAPIController->getTokenShipay($bank->shipay_client_id,$bank->shipay_access_key,$bank->shipay_secret_key),true);
        $token = $response_token['access_token'];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.shipay.com.br/order/'.$payment_id,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer '.$token
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function dia_semana($date){
        // Array com os dias da semana
        $diasemana = array('dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sab');

        // Varivel que recebe o dia da semana (0 = Domingo, 1 = Segunda ...)
        $diasemana_numero = date('w', strtotime($date));

        return $diasemana[$diasemana_numero];
    }
}
