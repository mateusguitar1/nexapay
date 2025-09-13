<?php

namespace App\Http\Controllers\REST;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\FunctionsController;
use \App\Models\{Extract,Clients,Transactions,Taxes,Keys,Banks,Logs,HistoryTax,User};

class WithdrawControllerAPI extends Controller
{
    //
    public function index()
    {
        //
        $transactions = Transactions::where('type_transaction', 'withdraw')
            ->where("status","pending")
            ->orderby('id', 'DESC')
            ->get();

        $transactionAmount = $transactions->sum('amount_solicitation');

        if(auth()->user()->level == "master"){
            $clients = Clients::orderBy("name","ASC")->get();
        }elseif(auth()->user()->level == "merchant"){
            $clients = Clients::where("id",auth()->user()->client->id)->get();
        }

        $data = [
            'transactionAmount' => $transactionAmount,
            'transactions' => $transactions,
            'transactionsAll' => $transactions->count(),
            'clients' => $clients,
            "banks" => Banks::all(),
        ];

        return response()->json($data);
    }

    public function search(Request $request)
    {
        $data = $request->all();
        $data['search'] = onlyText($data['search']);
        $data['status'] = ( !empty($data['status']) ? $data['status'] : Null );
        // return $data;
        $year = substr($request->date,0,4);
        $month = substr($request->date,5,2);

        $start = $request->date.'-01 00:00:00';

        $days_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $end = date($request->date."-".$days_month." 23:59:59");

        $transactions = Transactions::
        where('type_transaction', '=', 'withdraw')->
        whereBetween("final_date",[$start,$end])->
        getSearch($data['search'])->
        GetClientID($data['client_id'])->
        GetStatus($data['status'])->
        with(['client','bank'])->
        get();

        $transactionsAll = $transactions->count();
        $transactionAmount = $transactions->sum('amount_solicitation');

        $data = [
            'transactionAmount' =>$transactionAmount,
            'transactions' => $transactions,
            'transactionsAll' => ( !empty($transactionsAll) ? $transactionsAll : 0 ),
            'clients' => Clients::all()->sortBy("name"),
            'banks' => Banks::all()
        ];

        return response()->json($data);
    }

    public function create(Request $request)
    {

        $FunctionsController = new FunctionsController();

        if(isset($request->order_id)){
            $check_order = Transactions::where("client_id",$request->client_id)
            ->where("type_transaction","=","withdraw")
            ->where("order_id","=",$request->order_id)
            ->count();
        }else{
            $check_order = 0;
        }

        $client = Clients::where("id",$request->client_id)->first();

        if(!isset($client)){
            return response()->json(["status" => "error", "message" => "Client not found"]);
            exit();
        }

        if($check_order >= 1){
            return response()->json(array('status' => 'error', 'message' => 'order_id already exists'));
        }else{

            $method = $request->method;
            $amount = str_replace('.','',$request->amount_solicitation);
            $amount = str_replace(',','.',$amount);

            switch($method){
                case"bank_info":

                    //user
                    $user_name = $request->name;
                    $user_document = clearDocument($request->document);
                    $bank_name = $request->name_bank;
                    $agency = $request->agency;
                    $account_number = $request->bank_account;
                    $operation_bank = $request->type_operation;

                    $array_user = array(
                        'name' => $user_name,
                        'document' => $user_document,
                        'bank_name' => $bank_name,
                        'holder' => $client->name,
                        'agency' => $agency,
                        'account_number' => $account_number,
                        'operation_bank' => $operation_bank,
                        'user_id' => $request->user_id,
                        'client_id' => $request->client_id,
                        'address' => '---',
                        'district' => '---',
                        'city' => '---',
                        'uf' => '---',
                        'cep' => '---',
                    );

                    // Taxas
                    $tax = $client->tax;

                    $quote_markup = "1";
                    $quote = "1";
                    $percent_markup = "0";

                    $final_amount = $amount;
                    $percent_fee = ($final_amount * ($tax->withdraw_percent / 100));
                    $fixed_fee = $tax->withdraw_absolute;
                    if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                    $comission = ($percent_fee + $fixed_fee);
                    if($comission < $tax->min_fee_withdraw){ $comission = $tax->min_fee_withdraw; $min_fee = $tax->min_fee_withdraw; }else{ $min_fee = "NULL"; }

                    $method_transaction = "ted";

                break;
                case"crypto":

                    //user
                    $user_name = "";
                    $user_document = "";
                    $bank_name = "";
                    $agency = "";
                    $account_number = "";
                    $operation_bank = "";
                    $hash_btc = $request->hash_btc;

                    $array_user = array(
                        'name' => $user_name,
                        'document' => $user_document,
                        'bank_name' => $bank_name,
                        'holder' => $client->name,
                        'agency' => $agency,
                        'account_number' => $account_number,
                        'operation_bank' => $operation_bank,
                        'user_id' => $request->user_id,
                        'client_id' => $request->client_id,
                        'address' => '---',
                        'district' => '---',
                        'city' => '---',
                        'uf' => '---',
                        'cep' => '---',
                        "hash_btc" => $hash_btc
                    );

                    // Taxas
                    $tax = $client->tax;

                    $quote_markup = "1";
                    $quote = "1";
                    $percent_markup = "0";

                    $final_amount = $amount;
                    $percent_fee = ($final_amount * ($tax->withdraw_percent / 100));
                    $fixed_fee = $tax->withdraw_absolute;
                    if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                    $comission = ($percent_fee + $fixed_fee);
                    if($comission < $tax->min_fee_withdraw){ $comission = $tax->min_fee_withdraw; $min_fee = $tax->min_fee_withdraw; }else{ $min_fee = "NULL"; }

                    $method_transaction = "crypto";

                break;
                case"usdt":

                    //user
                    $user_name = "";
                    $user_document = "";
                    $bank_name = "";
                    $agency = "";
                    $account_number = "";
                    $operation_bank = "";
                    $hash_usdt = $request->hash_usdt;

                    $array_user = array(
                        'name' => $user_name,
                        'document' => $user_document,
                        'bank_name' => $bank_name,
                        'holder' => $client->name,
                        'agency' => $agency,
                        'account_number' => $account_number,
                        'operation_bank' => $operation_bank,
                        'user_id' => $request->user_id,
                        'client_id' => $request->client_id,
                        'address' => '---',
                        'district' => '---',
                        'city' => '---',
                        'uf' => '---',
                        'cep' => '---',
                        "hash_usdt" => $hash_usdt
                    );

                    // Taxas
                    $tax = $client->tax;

                    $quote_markup = "1";
                    $quote = "1";
                    $percent_markup = "0";

                    $final_amount = $amount;
                    $percent_fee = ($final_amount * ($tax->withdraw_percent / 100));
                    $fixed_fee = $tax->withdraw_absolute;
                    if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                    $comission = ($percent_fee + $fixed_fee);
                    if($comission < $tax->min_fee_withdraw){ $comission = $tax->min_fee_withdraw; $min_fee = $tax->min_fee_withdraw; }else{ $min_fee = "NULL"; }

                    $method_transaction = "usdt-erc20";

                break;
                case"pix":

                    //user
                    $user_name = "";
                    $user_document = "";
                    $bank_name = "";
                    $agency = "";
                    $account_number = "";
                    $operation_bank = "";
                    $pix_key = $request->pix_key;
                    $type_pixkey = $request->type_pixkey;

                    $array_user = array(
                        'name' => $user_name,
                        'document' => $user_document,
                        'bank_name' => $bank_name,
                        'holder' => $client->name,
                        'agency' => $agency,
                        'account_number' => $account_number,
                        'operation_bank' => $operation_bank,
                        'user_id' => $request->user_id,
                        'client_id' => $request->client_id,
                        'address' => '---',
                        'district' => '---',
                        'city' => '---',
                        'uf' => '---',
                        'cep' => '---',
                        "pix_key" => $pix_key,
                        "type_pixkey" => $type_pixkey,
                    );

                    // Taxas
                    $tax = $client->tax;

                    $quote_markup = "1";
                    $quote = "1";
                    $percent_markup = "0";

                    $final_amount = $amount;
                    $percent_fee = ($final_amount * ($tax->withdraw_pix_percent / 100));
                    $fixed_fee = $tax->withdraw_pix_absolute;
                    if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                    $comission = ($percent_fee + $fixed_fee);
                    if($comission < $tax->min_fee_withdraw_pix){ $comission = $tax->min_fee_withdraw_pix; $min_fee = $tax->min_fee_withdraw_pix; }else{ $min_fee = "NULL"; }

                    $method_transaction = "pix";

                break;
            }

            $user_account_data = base64_encode(json_encode($array_user));

            DB::beginTransaction();
            try {

                $order_id = $FunctionsController->gera_pedido_withdraw($client->id);
                $user_id = "99999999";

                $transaction = Transactions::create([
                    'order_id' => $order_id,
                    'code' => $order_id,
                    'payment_id' => $order_id,
                    'amount_solicitation' => $amount,
                    'quote' => $quote,
                    'percent_markup' => $percent_markup,
                    'quote_markup' => $quote_markup,
                    'percent_fee' => $percent_fee,
                    'fixed_fee' => $fixed_fee,
                    'comission' => $comission,
                    'solicitation_date' => date('Y-m-d H:i:s'),
                    'final_date' => date('Y-m-d H:i:s'),
                    'type_transaction' => 'withdraw',
                    'method_transaction' => $method_transaction,
                    'status' => 'pending',
                    'client_id' => $request->client_id,
                    'user_document' => $user_document,
                    'user_id' => $user_id,
                    'user_account_data' => $user_account_data,
                ]);

                Logs::create([
                    'user_id' =>  auth()->user()->id,
                    'client_id' =>  $request->client_id,
                    'type' =>  'add',
                    'action' => 'User '.auth()->user()->name.' create new withdraw order_id: '.$transaction->order_id,
                ]);

                DB::commit();

                return response()->json(array( 'status'=> 'success', 'message' => 'Success'));

            }catch (Exception $e) {

                DB::rollback();
                return response()->json(array( 'status'=> 'error', 'message' => 'Server Error Please Try Again'));

            }


        }

    }

    public function delete(Request $request)
    {
        $FunctionsController = new FunctionsController();

        $transaction = Transactions::where("id",$request->id)->first();

        DB::beginTransaction();
        try {

            $client = $transaction->client;

            if($request->type_error == 'other_error'){
                $observation = $request->description_error;
            }else{
                $observation = "incorrect account data";
            }

            if($request->client_id == "177"){
                //Do update
                $transaction->update([
                    'final_amount' => $transaction->amount_solicitation,
                    'cancel_date' =>date('Y-m-d H:i:s'),
                    'final_date' =>date('Y-m-d H:i:s'),
                    'observation' => $observation,
                    'comission' => '0.00',
                    'status' => 'canceled',
                ]);
            }else{
                //Do update
                $transaction->update([
                    'final_amount' => $transaction->amount_solicitation,
                    'cancel_date' =>date('Y-m-d H:i:s'),
                    'final_date' =>date('Y-m-d H:i:s'),
                    'observation' => $observation,
                    'status' => 'canceled',
                ]);
            }

            Logs::create([
                'user_id' =>  auth()->user()->id,
                'client_id' =>  $request->client_id,
                'type' =>  'system',
                'action' => 'User '.auth()->user()->name.' cancel manually withdraw order_id: '.$transaction->order_id,
            ]);

            DB::commit();

            // set post fields
            $post = [
                "order_id" => $transaction->order_id,
                "user_id" => $transaction->user_id,
                "solicitation_date" => $transaction->solicitation_date,
                "cancel_date" => $transaction->cancel_date,
                "code_identify" => $transaction->code,
                "amount_solicitation" => number_format($transaction->amount_solicitation,2,',','.'),
                "status" => "canceled",
                "description" => $transaction->observation,
            ];



            $post_field = json_encode($post);
            $authentication = $client->key->authorization;

            if($transaction->type_transaction == "deposit"){
            $url_callback = $client->key->url_callback;
            }elseif($transaction->type_transaction == "withdraw"){
            $url_callback = $client->key->url_callback_withdraw;
            }

            $ch = curl_init($url_callback);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'token:'.$authentication));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

            // execute!
            $response = curl_exec($ch);


            $ch2 = curl_init("https://webhook.site/1ccde4e1-a73b-4182-a47e-8a5b95a5ab31");
            curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'token:'.$authentication));
            curl_setopt($ch2, CURLOPT_POSTFIELDS, $post_field);

            // execute!
            $response2 = curl_exec($ch2);

            // close the connection, release resources used
            curl_close($ch2);

            return response()->json(array('status' => 'success', 'message' => 'Withdraw Canceled Successfully'),true);

        }catch (Exception $e) {
            DB::rollback();
            return response()->json(array('status' => 'error', 'message' => 'Server Error'),true);

        }
    }
}
