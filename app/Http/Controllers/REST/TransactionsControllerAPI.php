<?php

namespace App\Http\Controllers\REST;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FunctionsController;
use \App\Models\{Extract,Clients,Transactions,Taxes,Keys,Banks,Logs,HistoryTax,User,Blocklist,Whitelist};

class TransactionsControllerAPI extends Controller
{
    //

    public function index(Request $request){

        $FunctionsController = new FunctionsController();

        if(auth()->user()->level == "payment"){
            return redirect('approveWithdraw')->with('warning', 'You are not authorized to access this page');
        }

        if(auth()->user()->level == 'master'){

            $first_date = date('Y-m-d 00:00:00');
            $last_date = date('Y-m-d').' 23:59:59';

            $get_transactions = Transactions::whereIn('status', ['confirmed', 'pending', 'canceled', 'refund', 'chargeback', 'freeze'])
            ->whereBetween("final_date", [$first_date, $last_date])
            ->orderBy("solicitation_date","DESC")->get();

            $transactions = [];

            foreach($get_transactions as $register){

                if($register->type_transaction == "withdraw"){
                    if($register->receipt != ''){
                        $receipt = \Storage::disk('s3')->url('upcomprovante/'.$register->receipt);
                    }else{
                        if($register->status == "confirmed"){
                            $receipt = "https://admin.fastpayments.com.br/comprovantePix/".$register->id;
                        }
                    }
                }else{
                    $receipt = "";
                }

                $transactions[] = [
                    "id" => $register->id,
                    "solicitation_date" => $register->solicitation_date,
                    "paid_date" => $register->paid_date,
                    "cancel_date" => $register->cancel_date,
                    "refund_date" => $register->refund_date,
                    "freeze_date" => $register->freeze_date,
                    "chargeback_date" => $register->chargeback_date,
                    "final_date" => $register->final_date,
                    "disponibilization_date" => $register->disponibilization_date,
                    "due_date" => $register->due_date,
                    "code" => $register->code,
                    "client_id" => $register->client->name,
                    "order_id" => $register->order_id,
                    "user_id" => $register->user_id,
                    "user_account_data" => $register->user_account_data,
                    "user_document" => $register->user_document,
                    "code_bank" => $register->code_bank,
                    "bank_name" => $register->bank->name,
                    "bank_data" => $register->bank_data,
                    "type_transaction" => $register->type_transaction,
                    "method_transaction" => $register->method_transaction,
                    "amount_solicitation" => $register->amount_solicitation,
                    "final_amount" => $register->final_amount,
                    "percent_fee" => $register->percent_fee,
                    "fixed_fee" => $register->fixed_fee,
                    "min_fee" => $register->min_fee,
                    "comission" => $register->comission,
                    "status" => $register->status,
                    "receipt" => $receipt,
                    "observation" => $register->observation,
                    "confirmation_callback" => $register->confirmation_callback,
                    "payment_id" => $register->payment_id,
                    "link_callback_bank" => $register->link_callback_bank,
                    "deep_link" => $register->deep_link,
                    "canceled_manual" => $register->canceled_manual,
                    "url_retorna" => $register->url_retorna,
                    "confirmed_bank" => $register->confirmed_bank,
                    "credit_card_refunded" => $register->credit_card_refunded,
                    "confirm_callback_refund" => $register->confirm_callback_refund,
                    "response_refund_client" => $register->response_refund_client,
                    "data_invoice_id" => $register->data_invoice_id,
                    "created_at" => $register->created_at,
                    "updated_at" => $register->updated_at,
                    "hash_btc" => $register->hash_btc,
                    "base64_image" => $register->base64_image,
                    "user_name" => $register->user_name,
                    "provider_reference" => $register->provider_reference,
                ];

            }

            $client = Clients::all();
            $currency = 'R$ ';

        }else{

            $first_date = date('Y-m-d 00:00:00');
            $last_date = date('Y-m-d').' 23:59:59';

            $get_transactions = Transactions::where('client_id', '=', auth()->user()->client_id)
            ->whereIn('status', ['confirmed', 'pending', 'canceled', 'refund', 'chargeback', 'freeze'])
            ->whereBetween("final_date", [$first_date, $last_date])
            ->orderBy("solicitation_date","DESC")->get();

            $transactions = [];

            foreach($get_transactions as $register){

                if($register->type_transaction == "withdraw"){
                    if($register->receipt != ''){
                        $receipt = \Storage::disk('s3')->url('upcomprovante/'.$register->receipt);
                    }else{
                        if($register->status == "confirmed"){
                            $receipt = "https://admin.fastpayments.com.br/comprovantePix/".$register->id;
                        }
                    }
                }else{
                    $receipt = "";
                }

                $transactions[] = [
                    "id" => $register->id,
                    "solicitation_date" => $register->solicitation_date,
                    "paid_date" => $register->paid_date,
                    "cancel_date" => $register->cancel_date,
                    "refund_date" => $register->refund_date,
                    "freeze_date" => $register->freeze_date,
                    "chargeback_date" => $register->chargeback_date,
                    "final_date" => $register->final_date,
                    "disponibilization_date" => $register->disponibilization_date,
                    "due_date" => $register->due_date,
                    "code" => $register->code,
                    "client_id" => $register->client->name,
                    "order_id" => $register->order_id,
                    "user_id" => $register->user_id,
                    "user_account_data" => $register->user_account_data,
                    "user_document" => $register->user_document,
                    "code_bank" => $register->code_bank,
                    "bank_name" => $register->bank->name,
                    "bank_data" => $register->bank_data,
                    "type_transaction" => $register->type_transaction,
                    "method_transaction" => $register->method_transaction,
                    "amount_solicitation" => $register->amount_solicitation,
                    "final_amount" => $register->final_amount,
                    "percent_fee" => $register->percent_fee,
                    "fixed_fee" => $register->fixed_fee,
                    "min_fee" => $register->min_fee,
                    "comission" => $register->comission,
                    "status" => $register->status,
                    "receipt" => $receipt,
                    "observation" => $register->observation,
                    "confirmation_callback" => $register->confirmation_callback,
                    "payment_id" => $register->payment_id,
                    "link_callback_bank" => $register->link_callback_bank,
                    "deep_link" => $register->deep_link,
                    "canceled_manual" => $register->canceled_manual,
                    "url_retorna" => $register->url_retorna,
                    "confirmed_bank" => $register->confirmed_bank,
                    "credit_card_refunded" => $register->credit_card_refunded,
                    "confirm_callback_refund" => $register->confirm_callback_refund,
                    "response_refund_client" => $register->response_refund_client,
                    "data_invoice_id" => $register->data_invoice_id,
                    "created_at" => $register->created_at,
                    "updated_at" => $register->updated_at,
                    "hash_btc" => $register->hash_btc,
                    "base64_image" => $register->base64_image,
                    "user_name" => $register->user_name,
                    "provider_reference" => $register->provider_reference,
                ];

            }

            $client = Clients::where('id', '=', auth()->user()->client_id)->get();
            $currency = 'R$ ';
        }


        $first_date = date('Y-m-d 00:00:00');
        $last_date = date('Y-m-d').' 23:59:59';

        $clients_ids = [];
        foreach($client as $cl){
            array_push($clients_ids,$cl->id);
        }

        $banks_account = Banks::orderBy("name","ASC")->get();
        $all_users_blocked = Blocklist::whereIn('client_id', $clients_ids)->get();


        $data = [
            'clients' => $client,
            'banks' => $banks_account,
            'transactions' => $transactions,
            'all_users_blocked' => $all_users_blocked
        ];

        return response()->json($data);

    }

    public function search(Request $request){

        $FunctionsController = new FunctionsController();

        $clients_ids = [];
        $type_transactions = [];

        if(!empty($request->date_start)){
            $start = $FunctionsController->strtodate($request->date_start)." 00:00:00";
        }else{
            $start = "";
        }

        if(!empty($request->date_end)){
            $end = $FunctionsController->strtodate($request->date_end)." 23:59:59";
        }else{
            $end = "";
        }

        $today = date("Y-m-d 00:00:00");

        if($request->client_id != "all"){

            array_push($clients_ids,$request->client_id);

        }else{

            $clients = Clients::all();
            foreach($clients as $cli){
                array_push($clients_ids,$cli->id);
            }

        }

        if($request->type_transaction != "all"){

            array_push($type_transactions,$request->type_transaction);

        }else{

            array_push($type_transactions,"deposit");
            array_push($type_transactions,"withdraw");

        }

        if(auth()->user()->level == "payment"){
            return redirect('approveWithdraw')->with('warning', 'You are not authorized to access this page');
        }

        if(auth()->user()->level == 'master'){

            $first_date = $start;
            $last_date = $end;

            $get_transactions = Transactions::whereIn("client_id",$clients_ids)
            ->whereIn("type_transaction",$type_transactions)
            ->whereIn("status", ['confirmed', 'pending', 'canceled', 'refund', 'chargeback', 'freeze'])
            ->where(function ($query) use ($request,$first_date,$last_date) {
                if(!empty($first_date) && !empty($last_date)){
                    $query->whereBetween("solicitation_date", [$first_date, $last_date]);
                }

                if(!empty($request->search)){
                    if(is_numeric($request->search)){
                        if(filter_var($request->search, FILTER_VALIDATE_INT)){
                            $query->where('id', $request->search)
                            ->orWhere('order_id', $request->search)
                            ->orWhere('user_id', $request->search)
                            ->orWhere('code', $request->search)
                            ->orWhere('bank_data', $request->search)
                            ->orWhere('payment_id', $request->search)
                            ->orWhere('user_name', 'LIKE', '%'.$request->search.'%');

                        }else{
                            $query->where('amount_solicitation', $request->search);
                        }
                    }else{
                        $query->where('order_id', $request->search)
                        ->orWhere('user_id', $request->search)
                        ->orWhere('code', $request->search)
                        ->orWhere('bank_data', $request->search)
                        ->orWhere('payment_id', $request->search)
                        ->orWhere('user_name', 'LIKE', '%'.$request->search.'%');
                    }
                }
            })
            ->orderBy("solicitation_date", "DESC")->get();

            $transactions = [];

            foreach($get_transactions as $register){

                if($register->type_transaction == "withdraw"){
                    if($register->receipt != ''){
                        $receipt = \Storage::disk('s3')->url('upcomprovante/'.$register->receipt);
                    }else{
                        $receipt = "";
                        if($register->status == "confirmed"){
                            $receipt = "https://admin.fastpayments.com.br/comprovantePix/".$register->id;
                        }
                    }
                }else{
                    $receipt = "";
                }

                $transactions[] = [
                    "id" => $register->id,
                    "solicitation_date" => $register->solicitation_date,
                    "paid_date" => $register->paid_date,
                    "cancel_date" => $register->cancel_date,
                    "refund_date" => $register->refund_date,
                    "freeze_date" => $register->freeze_date,
                    "chargeback_date" => $register->chargeback_date,
                    "final_date" => $register->final_date,
                    "disponibilization_date" => $register->disponibilization_date,
                    "due_date" => $register->due_date,
                    "code" => $register->code,
                    "client_id" => $register->client->name,
                    "order_id" => $register->order_id,
                    "user_id" => $register->user_id,
                    "user_account_data" => $register->user_account_data,
                    "user_document" => $register->user_document,
                    "code_bank" => $register->code_bank,
                    "bank_name" => $register->bank->name,
                    "bank_data" => $register->bank_data,
                    "type_transaction" => $register->type_transaction,
                    "method_transaction" => $register->method_transaction,
                    "amount_solicitation" => $register->amount_solicitation,
                    "final_amount" => $register->final_amount,
                    "percent_fee" => $register->percent_fee,
                    "fixed_fee" => $register->fixed_fee,
                    "min_fee" => $register->min_fee,
                    "comission" => $register->comission,
                    "status" => $register->status,
                    "receipt" => $receipt,
                    "observation" => $register->observation,
                    "confirmation_callback" => $register->confirmation_callback,
                    "payment_id" => $register->payment_id,
                    "link_callback_bank" => $register->link_callback_bank,
                    "deep_link" => $register->deep_link,
                    "canceled_manual" => $register->canceled_manual,
                    "url_retorna" => $register->url_retorna,
                    "confirmed_bank" => $register->confirmed_bank,
                    "credit_card_refunded" => $register->credit_card_refunded,
                    "confirm_callback_refund" => $register->confirm_callback_refund,
                    "response_refund_client" => $register->response_refund_client,
                    "data_invoice_id" => $register->data_invoice_id,
                    "created_at" => $register->created_at,
                    "updated_at" => $register->updated_at,
                    "hash_btc" => $register->hash_btc,
                    "base64_image" => $register->base64_image,
                    "user_name" => $register->user_name,
                    "provider_reference" => $register->provider_reference,
                ];

            }

            $client = Clients::all();
            $currency = 'R$ ';

        }else{

            $first_date = $start;
            $last_date = $end;

            $get_transactions = Transactions::where('client_id', auth()->user()->client_id)
            ->whereIn("type_transaction",$type_transactions)
            ->whereIn('status', ['confirmed', 'pending', 'canceled', 'refund', 'chargeback', 'freeze'])
            ->where(function ($query) use ($request,$first_date,$last_date) {
                if(!empty($first_date) && !empty($last_date)){
                    $query->whereBetween("solicitation_date", [$first_date, $last_date]);
                }

                if(!empty($request->search)){
                    if(is_numeric($request->search)){
                        if(filter_var($request->search, FILTER_VALIDATE_INT)){
                            $query->where('order_id', '=', $request->search)
                            ->orWhere('user_id', '=', $request->search)
                            ->orWhere('code', '=', $request->search)
                            ->orWhere('bank_data', '=', $request->search)
                            ->orWhere('payment_id', '=', $request->search);

                        }else{
                            $query->where('amount_solicitation', '=', $request->search);
                        }
                    }else{
                        $query->where('order_id', '=', $request->search)
                        ->orWhere('user_id', '=', $request->search)
                        ->orWhere('code', '=', $request->search)
                        ->orWhere('bank_data', '=', $request->search)
                        ->orWhere('payment_id', '=', $request->search);
                    }
                }
            })
            ->orderBy("solicitation_date", "DESC")->get();

            $transactions = [];

            foreach($get_transactions as $register){

                if($register->type_transaction == "withdraw"){
                    if($register->receipt != ''){
                        $receipt = \Storage::disk('s3')->url('upcomprovante/'.$register->receipt);
                    }else{
                        if($register->status == "confirmed"){
                            $receipt = "https://admin.fastpayments.com.br/comprovantePix/".$register->id;
                        }
                    }
                }else{
                    $receipt = "";
                }

                $transactions[] = [
                    "id" => $register->id,
                    "solicitation_date" => $register->solicitation_date,
                    "paid_date" => $register->paid_date,
                    "cancel_date" => $register->cancel_date,
                    "refund_date" => $register->refund_date,
                    "freeze_date" => $register->freeze_date,
                    "chargeback_date" => $register->chargeback_date,
                    "final_date" => $register->final_date,
                    "disponibilization_date" => $register->disponibilization_date,
                    "due_date" => $register->due_date,
                    "code" => $register->code,
                    "client_id" => $register->client->name,
                    "order_id" => $register->order_id,
                    "user_id" => $register->user_id,
                    "user_account_data" => $register->user_account_data,
                    "user_document" => $register->user_document,
                    "code_bank" => $register->code_bank,
                    "bank_name" => $register->bank->name,
                    "bank_data" => $register->bank_data,
                    "type_transaction" => $register->type_transaction,
                    "method_transaction" => $register->method_transaction,
                    "amount_solicitation" => $register->amount_solicitation,
                    "final_amount" => $register->final_amount,
                    "percent_fee" => $register->percent_fee,
                    "fixed_fee" => $register->fixed_fee,
                    "min_fee" => $register->min_fee,
                    "comission" => $register->comission,
                    "status" => $register->status,
                    "receipt" => $receipt,
                    "observation" => $register->observation,
                    "confirmation_callback" => $register->confirmation_callback,
                    "payment_id" => $register->payment_id,
                    "link_callback_bank" => $register->link_callback_bank,
                    "deep_link" => $register->deep_link,
                    "canceled_manual" => $register->canceled_manual,
                    "url_retorna" => $register->url_retorna,
                    "confirmed_bank" => $register->confirmed_bank,
                    "credit_card_refunded" => $register->credit_card_refunded,
                    "confirm_callback_refund" => $register->confirm_callback_refund,
                    "response_refund_client" => $register->response_refund_client,
                    "data_invoice_id" => $register->data_invoice_id,
                    "created_at" => $register->created_at,
                    "updated_at" => $register->updated_at,
                    "hash_btc" => $register->hash_btc,
                    "base64_image" => $register->base64_image,
                    "user_name" => $register->user_name,
                    "provider_reference" => $register->provider_reference,
                ];

            }

            $client = Clients::where('id', auth()->user()->client_id)->get();
            $currency = 'R$ ';
        }

        $first_date = $start;
        $last_date = $end;

        // $invoices = DataInvoice::where('client_id', '=', $client->id)->whereBetween('created_at',[$first_date,$last_date])->get();
        // $errors = DB::table('api_logs')->where('order_id', '!=','')->get();
        $banks_account = Banks::orderBy("name","ASC")->get();
        $all_users_blocked = Blocklist::whereIn('client_id', $clients_ids)->get();

        $data = [
            'clients' => $client,
            'banks' => $banks_account,
            'transactions' => $transactions,
            'all_users_blocked' => $all_users_blocked
        ];

        return response()->json($data);

    }

}
