<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\FunctionsController;
use App\Models\{Clients,Transactions,Taxes,Keys,Banks,Logs,BankClientsAccount};

class WithdrawManualController extends Controller
{
    //
    public function index(){

        $first_date = date('Y-m-d 00:00:00');
        $last_date = date('Y-m-d 23:59:59');
        $transactions = Transactions::whereBetween('solicitation_date',[$first_date,$last_date])
                ->where('type_transaction', '=', 'withdraw')
                ->where('client_id', '=', auth()->user()->client_id)
                ->get();

        $banks_user = BankClientsAccount::all();

        $data = [
            'transactions' => $transactions,
            'banks_user' => $banks_user,
        ];
        return view('merchants.withdrawManual')->with('data',$data);
    }


    public function getWithdraw($id){
        $data = Transactions::find($id);
        // return '/upcomprovante/'.$data->receipt;
        $imagem = Storage::url('/upload/upcomprovante/'.$data->receipt);
        return view('merchants.openPaymentReceipt', compact('imagem'));
    }


    public function search(Request $request)
    {
        if($request->minall == '' ){
            $first_date = date('Y-m-d 00:00:00');
            $last_date = date('Y-m-d 23:59:59');
        }else{
            $first_date = date('Y-m-d 00:00:00',strtotime($request->minall));
            $last_date = date('Y-m-d 23:59:59',strtotime($request->maxall));
        }

        if($request->search != ''){
            $transactions = Transactions::where('order_id', '=', $request->search)
                ->where('type_transaction', '=', 'withdraw')
                ->where('client_id', '=', auth()->user()->client_id)
                ->get();
        }else{
            $transactions = Transactions::whereBetween('solicitation_date',[$first_date,$last_date])
                ->where('type_transaction', '=', 'withdraw')
                ->where('client_id', '=', auth()->user()->client_id)
                ->get();
        }

        $banks_user = BankClientsAccount::all();

        $data = [
            'transactions' => $transactions,
            'banks_user' => $banks_user,
        ];
        return view('merchants.withdrawManual')->with('data',$data);
    }

    public function newWithdraw(Request $request)
    {

        $FunctionsController = new FunctionsController();
        $FunctionsAPIController = new FunctionsAPIController();

        if(isset($request->order_id)){
            $check_order = Transactions::where("client_id",$request->client_id)
            ->where("type_transaction","=","withdraw")
            ->where("order_id","=",$request->order_id)
            ->count();
        }else{
            $check_order = 0;
        }

        if($check_order >= 1){
            return json_encode(array('status' => 'alert', 'message' => 'order_id already exists'));
        }else{

            $method = $request->method;
            $amount = str_replace('.','',$request->amount_solicitation);
            $amount = str_replace(',','.',$amount);

            $client = Clients::where("id",$request->client_id)->first();

            $rule_hour_min = strtotime(date("Y-m-d 09:00:00"));
            $rule_hour_max = strtotime(date("Y-m-d 20:00:00"));
            $now_hour = date("Y-m-d H:i:s");

            if($now_hour < $rule_hour_min && $now_hour > $rule_hour_max){
                if($amount > 1000){
                    $json_return = array("status" => "error", "message" => "Amount greater than that allowed by the schedule");
                    return json_encode(array( 'status'=> 'error', 'message' => 'Error'));
                }
            }

            if(floatval($amount) < floatval($client->tax->min_withdraw)){
                $json_return = array("status" => "error", "message" => "Minimum amount R$ ".number_format($client->tax->min_withdraw,2,",","."));
                return json_encode(array( 'status'=> 'error', 'message' => 'Error'));
            }
            if(floatval($amount) > floatval($client->tax->max_withdraw)){
                $json_return = array("status" => "error", "message" => "Maximum amount R$ ".number_format($client->tax->max_withdraw,2,",","."));
                return json_encode(array( 'status'=> 'error', 'message' => 'Error'));
            }

            $result_check = $FunctionsAPIController->checkBalanceWithdraw($client->id,$amount);

            if($result_check['message'] != "success"){
                return json_encode(array( 'status'=> 'error', 'message' => 'Amount greater than current balance'));
                exit();
            }

            switch($method){
                case"bank_info":

                    //user
                    $user_name = $request->name;
                    $user_document = clearDocument($request->document);
                    $bank_name = $request->name_bank;
                    $agency = $request->agency;
                    $account_number = $request->bank_account;
                    $operation_bank = $request->type_operation;

                    $client = Clients::where("id",$request->client_id)->first();

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

                    $client = Clients::where("id",$request->client_id)->first();

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

                    $client = Clients::where("id",$request->client_id)->first();

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
                    $user_name = "Rafael Bruno Marcelo Barbosa";
                    $user_document = "40521421012";
                    $bank_name = "";
                    $agency = "";
                    $account_number = "";
                    $operation_bank = "";
                    $pix_key = $request->pix_key;
                    $type_pixkey = $request->type_pixkey;

                    $client = Clients::where("id",$request->client_id)->first();

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
                    $percent_fee = ($final_amount * ($tax->withdraw_percent / 100));
                    $fixed_fee = $tax->withdraw_absolute;
                    if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                    $comission = ($percent_fee + $fixed_fee);
                    if($comission < $tax->min_fee_withdraw){ $comission = $tax->min_fee_withdraw; $min_fee = $tax->min_fee_withdraw; }else{ $min_fee = "NULL"; }

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

                if($client->withdraw_permition === true){
                    $id_bank_withdraw = $client->bank_withdraw_permition;

                    $bank_withdraw = Banks::where("id",$id_bank_withdraw)->first();

                    if($bank_withdraw){
                        if($bank_withdraw->withdraw_permition === true){

                            if(floatval($amount) < floatval($client->tax->min_withdraw)){
                                $json_return = array("status" => "error", "message" => "Minimum amount R$ ".number_format($client->tax->min_withdraw,2,",","."));
                                return json_encode(array( 'status'=> 'error', 'message' => 'Error'));
                            }
                            if(floatval($amount) > floatval($client->tax->max_withdraw)){
                                $json_return = array("status" => "error", "message" => "Maximum amount R$ ".number_format($client->tax->max_withdraw,2,",","."));
                                return json_encode(array( 'status'=> 'error', 'message' => 'Error'));
                            }

                            // $result_check = $FunctionsAPIController->checkBalanceWithdraw($client->id,$amount);

                            // if($result_check['message'] != "success"){
                            //     return json_encode(array( 'status'=> 'error', 'message' => 'Amount greater than current balance'));
                            // }else{
                                if(in_array($client->id,['8','18','22','34'])){
                                    if($method_transaction == "pix" && $bank_withdraw->code == "588"){
                                        \App\Jobs\PerformWithdrawalPIXVolutiManual::dispatch($transaction->id,$bank_withdraw->id)->delay(now()->addSeconds('0'));
                                    }
                                }
                            // }

                        }
                    }

                }

                return json_encode(array( 'status'=> 'success', 'message' => 'Success'));

            }catch (Exception $e) {

                DB::rollback();
                return json_encode(array( 'status'=> 'error', 'message' => 'Server Error Please Try Again'));

            }


        }

    }

    public function newWithdrawBatch(Request $request){

        $FunctionsController = new FunctionsController();

        $user_id = $request->user_id;
        $client_id = $request->client_id;
        $order_id = $request->order_id;
        $amount_solicitation = $FunctionsController->strtodouble($request->amount_solicitation);
        $name = $request->name;
        $document = $FunctionsController->clearCPF($request->document);

        $result_check = $FunctionsController->checkBalanceWithdraw($client_id,$amount_solicitation);

        if($result_check['code'] != "200"){

            $data = [
                "status" => "error",
                "message" => $result_check['message'],
            ];

            return $data;
        }

        $date = date("Y-m-d H:i:s");

        $client = Clients::where("id",$client_id)->first();
        $taxes = $client->tax;

        if($amount_solicitation < $taxes->min_withdraw_pix){

            $data = [
                "status" => "error",
                "message" => "Unable to request withdrawal. Amount requested less than the minimum of R$ ".number_format($taxes->min_withdraw_pix,2,",","."),
            ];

            return $data;

        }

        $checkAmountSolicitationWithdrawPIXUser = Transactions::where("client_id",$client_id)
            ->where("user_id",$user_id)
            ->where("type_transaction","withdraw")
            ->where("method_transaction","pix")
            ->whereIn("status",['pending','confirmed'])
            ->whereBetween("solicitation_date",[date("Y-m-d 00:00:00"),date("Y-m-d H:i:s")])
            ->sum("amount_solicitation");

        $checkAmountSolicitationWithdrawPIXmerchant = Transactions::where("client_id",$client_id)
            ->where("type_transaction","withdraw")
            ->where("method_transaction","pix")
            ->whereIn("status",['pending','confirmed'])
            ->whereBetween("solicitation_date",[date("Y-m-d 00:00:00"),date("Y-m-d H:i:s")])
            ->sum("amount_solicitation");

        if(($amount_solicitation + $checkAmountSolicitationWithdrawPIXUser) > $taxes->withdraw_pix_user_limit_day){

            $data = [
                "status" => "error",
                "message" => "User's daily PIX withdrawal limit reached",
            ];

            return $data;

        }

        if(($amount_solicitation + $checkAmountSolicitationWithdrawPIXmerchant) > $taxes->withdraw_pix_merchant_limit_day){

            $data = [
                "status" => "error",
                "message" => "Merchant's daily PIX withdrawal limit reached"
            ];

            return $data;

        }


        $array_user = array(
            'name' => $name,
            'document' => $document,
            "method" => "payment_pix",
            'bank_name' => "---",
            'holder' => auth()->user()->client->name,
            'agency' => "---",
            'account_number' => "---",
            'operation_bank' => "---",
            'user_id' => $user_id,
            'client_id' => $client_id,
            'address' => '---',
            'district' => '---',
            'city' => '---',
            'uf' => '---',
            'cep' => '---',
        );

        $user_account_data = base64_encode(json_encode($array_user));

        DB::beginTransaction();
        try{

            Transactions::create([
                "solicitation_date" => $date,
                "final_date" => $date,
                "order_id" => $order_id,
                "user_id" => $user_id,
                "client_id" => $client_id,
                "id_bank" => $client->bankWithdrawPix->id,
                "amount_solicitation" => $amount_solicitation,
                "user_account_data" => $user_account_data,
                "user_document" => $document,
                "type_transaction" => "withdraw",
                "method_transaction" => "pix",
                "status" => "pending"
            ]);

            DB::commit();

            $data = [
                "status" => "success",
                "message" => "Withdraw created successfully"
            ];

            return $data;

        }catch(Exception $e){
            DB::rollback();

            $data = [
                "status" => "error",
                "message" => "Unable to register withdrawal, server failure"
            ];

            return $data;
        }

    }

    public function approveWithdrawBatch(Request $request){

        $client_id = auth()->user()->client_id;
        $client = Clients::where("id",$client_id)->first();

        $transactions = Transactions::where("type_transaction","withdraw")
            ->where("method_transaction","pix")
            ->where("status","pending")
            ->where("client_id",$client_id)
            ->get();

        foreach($transactions as $transaction){

            \App\Jobs\PerformWithdrawalPaymentFront::dispatch($transaction->id,$client->bank_pix)->delay(now()->addSeconds('5'));

        }

        $data = [
            "status" => "success",
            "message" => "Payments entered the approval queue! View approvals on dashboard page."
        ];

        return $data;

    }
}
