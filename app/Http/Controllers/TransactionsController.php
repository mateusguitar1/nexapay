<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\FunctionsController;
use \App\Models\{Extract,Clients,Transactions,Taxes,Keys,Banks,Logs,HistoryTax,User,Blocklist,Whitelist};

class TransactionsController extends Controller
{
    //

    public function index(Request $request){

        $FunctionsController = new FunctionsController();

        $clients_ids = [];
        $type_transactions = [];

        if(isset($request->date_start)){
            $first_date = $FunctionsController->strtodate($request->date_start)." 00:00:00";
        }else{
            $first_date = date('Y-m-d 00:00:00');
        }

        if(isset($request->date_end)){
            $last_date = $FunctionsController->strtodate($request->date_end)." 23:59:59";
        }else{
            $last_date = date("Y-m-d 23:59:59");
        }

        $today = date("Y-m-d 00:00:00");

        if(isset($request->client_id)){
            if($request->client_id != "all"){

                array_push($clients_ids,$request->client_id);

            }else{

                $clients = Clients::all();
                foreach($clients as $cli){
                    array_push($clients_ids,$cli->id);
                }

            }
        }

        if(isset($request->type_transaction)){
            if($request->type_transaction != "all"){

                array_push($type_transactions,$request->type_transaction);

            }else{

                array_push($type_transactions,"deposit");
                array_push($type_transactions,"withdraw");

            }
        }else{
            array_push($type_transactions,"deposit");
            array_push($type_transactions,"withdraw");
        }

        if(auth()->user()->level == "payment"){
            return redirect('approveWithdraw')->with('warning', 'You are not authorized to access this page');
        }

        if(auth()->user()->level == "payment"){
            return redirect('approveWithdraw')->with('warning', 'You are not authorized to access this page');
        }

        $p = 1;
        // Defina aqui a quantidade máxima de registros por página.
        $qnt = 50;
        // O sistema calcula o início da seleção calculando:
        // (página atual * quantidade por página) - quantidade por página
        $inicio = ($p*$qnt) - $qnt;

        if(auth()->user()->level == 'master'){

            $transactions = Transactions::whereIn('status', ['confirmed', 'pending', 'canceled', 'refund', 'chargeback', 'freeze'])
            ->whereIn("type_transaction",$type_transactions)
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
                            ->orWhere('user_document', '=', $request->search)
                            ->orWhere('payment_id', '=', $request->search)
                            ->orWhere('user_name', 'LIKE', '%'.$request->search.'%');
                        }else{
                            $query->where('amount_solicitation', '=', $request->search)
                            ->orWhere('user_document', '=', $request->search);
                        }
                    }else{
                        $query->where('order_id', '=', $request->search)
                        ->orWhere('user_id', '=', $request->search)
                        ->orWhere('code', '=', $request->search)
                        ->orWhere('bank_data', '=', $request->search)
                        ->orWhere('payment_id', '=', $request->search)
                        ->orWhere('user_document', '=', $request->search)
                        ->orWhere('user_name', 'LIKE', '%'.$request->search.'%');
                    }
                }
            })
            ->orderBy("solicitation_date","DESC")->paginate(50);

            // $transactions_confirmed = Transactions::where('status', '=', 'confirmed')
            // ->whereBetween("final_date", [$first_date, $last_date])
            // ->orderBy("solicitation_date","DESC")->get();

            $all_transactions = Transactions::where("type_transaction","withdraw")->where('status', "pending")->count();

            $client = Clients::all();
            $currency = 'R$ ';

        }else{

            $transactions = Transactions::where('client_id', '=', auth()->user()->client_id)
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
                            ->orWhere('user_document', '=', $request->search)
                            ->orWhere('payment_id', '=', $request->search)
                            ->orWhere('user_name', 'LIKE', '%'.$request->search.'%');
                        }else{
                            $query->where('amount_solicitation', '=', $request->search)
                            ->orWhere('user_document', '=', $request->search);
                        }
                    }else{
                        $query->where('order_id', '=', $request->search)
                        ->orWhere('user_id', '=', $request->search)
                        ->orWhere('code', '=', $request->search)
                        ->orWhere('bank_data', '=', $request->search)
                        ->orWhere('payment_id', '=', $request->search)
                        ->orWhere('user_document', '=', $request->search)
                        ->orWhere('user_name', 'LIKE', '%'.$request->search.'%');
                    }
                }
            })
            ->orderBy("solicitation_date","DESC")->paginate(50);

            // $transactions_confirmed = Transactions::where('client_id', '=', auth()->user()->client_id)
            // ->where('status', '=', 'confirmed')
            // ->whereBetween("final_date", [$first_date, $last_date])
            // ->orderBy("solicitation_date","DESC")->get();


            $all_transactions = Transactions::where('client_id', '=', auth()->user()->client_id)
            ->where('status', "pending")
            ->where("type_transaction","withdraw")
            ->count();

            $client = Clients::where('id', '=', auth()->user()->client_id)->get();
            $currency = 'R$ ';
        }


        // $first_date = date('Y-m-d 00:00:00');
        // $last_date = date('Y-m-d').' 23:59:59';

        $clients_ids = [];
        foreach($client as $cl){
            array_push($clients_ids,$cl->id);
        }

        // $invoices = DataInvoice::where('client_id', '=', $client->id)->whereBetween('created_at',[$first_date,$last_date])->get();
        // $errors = DB::table('api_logs')->where('order_id', '!=','')->get();
        $banks_account = Banks::all()->sortBy("name");
        $all_users_blocked = Blocklist::whereIn('client_id', $clients_ids)->get();
        // if(!isset($all_users_blocked)){
        //     $all_users_blocked = [];
        // }

        $array_parametes = array(
            'status' => ['confirmed', 'pending'],
            'first_date' => $first_date,
            'last_date' => $last_date,
        );

        $data = [
            'clients' => $client,
            'banks' => $banks_account,
            'transactions' => $transactions,
            'all_users_blocked' => $all_users_blocked,
            'client' => $client,
            'all_transactions' => $all_transactions,
            'array_parameters' => $array_parametes,
            'request' => null
        ];

        // if(auth()->user()->level == 'master'){
        //     return $data;
        // }

        return view('transactions.index')->with('data',$data);

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

        $p = 1;
        // Defina aqui a quantidade máxima de registros por página.
        $qnt = 50;
        // O sistema calcula o início da seleção calculando:
        // (página atual * quantidade por página) - quantidade por página
        $inicio = ($p*$qnt) - $qnt;

        if(auth()->user()->level == 'master'){

            $first_date = $start;
            $last_date = $end;

            $transactions = Transactions::whereIn("client_id",$clients_ids)
            ->whereIn("type_transaction",$type_transactions)
            ->whereIn("status", ['confirmed', 'pending', 'canceled', 'refund', 'chargeback', 'freeze'])
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
                            ->orWhere('user_document', '=', $request->search)
                            ->orWhere('payment_id', '=', $request->search)
                            ->orWhere('user_name', 'LIKE', '%'.$request->search.'%');
                        }else{
                            $query->where('amount_solicitation', '=', $request->search)
                            ->orWhere('user_document', '=', $request->search);
                        }
                    }else{
                        $query->where('order_id', '=', $request->search)
                        ->orWhere('user_id', '=', $request->search)
                        ->orWhere('code', '=', $request->search)
                        ->orWhere('bank_data', '=', $request->search)
                        ->orWhere('payment_id', '=', $request->search)
                        ->orWhere('user_document', '=', $request->search)
                        ->orWhere('user_name', 'LIKE', '%'.$request->search.'%');
                    }
                }
            })
            ->orderBy("solicitation_date", "DESC")->get();

            $transactions_paginate = Transactions::whereIn("client_id",$clients_ids)
            ->whereIn("type_transaction",$type_transactions)
            ->whereIn("status", ['confirmed', 'pending', 'canceled', 'refund', 'chargeback', 'freeze'])
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
                            ->orWhere('payment_id', '=', $request->search)
                            ->orWhere('user_document', '=', $request->search)
                            ->orWhere('user_name', 'LIKE', '%'.$request->search.'%');

                        }else{
                            $query->where('amount_solicitation', '=', $request->search)
                            ->orWhere('user_document', '=', $request->search);
                        }
                    }else{
                        $query->where('order_id', '=', $request->search)
                        ->orWhere('user_id', '=', $request->search)
                        ->orWhere('code', '=', $request->search)
                        ->orWhere('bank_data', '=', $request->search)
                        ->orWhere('payment_id', '=', $request->search)
                        ->orWhere('user_document', '=', $request->search)
                        ->orWhere('user_name', 'LIKE', '%'.$request->search.'%');
                    }
                }
            })
            ->orderBy("solicitation_date", "DESC")
            ->offset($inicio)
            ->limit($qnt)->get();

            $client = Clients::all();
            $currency = 'R$ ';

        }else{

            $first_date = $start;
            $last_date = $end;

            $transactions = Transactions::where('client_id', auth()->user()->client_id)
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
                            ->orWhere('payment_id', '=', $request->search)
                            ->orWhere('user_document', '=', $request->search)
                            ->orWhere('user_name', 'LIKE', '%'.$request->search.'%');

                        }else{
                            $query->where('amount_solicitation', '=', $request->search)
                            ->orWhere('user_document', '=', $request->search);
                        }
                    }else{
                        $query->where('order_id', '=', $request->search)
                        ->orWhere('user_id', '=', $request->search)
                        ->orWhere('code', '=', $request->search)
                        ->orWhere('bank_data', '=', $request->search)
                        ->orWhere('payment_id', '=', $request->search)
                        ->orWhere('user_document', '=', $request->search)
                        ->orWhere('user_name', 'LIKE', '%'.$request->search.'%');
                    }
                }
            })
            ->orderBy("solicitation_date", "DESC")->get();

            $transactions_paginate = Transactions::where('client_id', auth()->user()->client_id)
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
                            ->orWhere('user_document', '=', $request->search)
                            ->orWhere('payment_id', '=', $request->search);

                        }else{
                            $query->where('amount_solicitation', '=', $request->search)
                            ->orWhere('user_document', '=', $request->search);
                        }
                    }else{
                        $query->where('order_id', '=', $request->search)
                        ->orWhere('user_id', '=', $request->search)
                        ->orWhere('code', '=', $request->search)
                        ->orWhere('bank_data', '=', $request->search)
                        ->orWhere('user_document', '=', $request->search)
                        ->orWhere('payment_id', '=', $request->search);
                    }
                }
            })
            ->orderBy("solicitation_date", "DESC")
            ->offset($inicio)
            ->limit($qnt)->get();

            $client = Clients::where('id', auth()->user()->client_id)->get();
            $currency = 'R$ ';
        }


        $first_date = $start;
        $last_date = $end;

        // $invoices = DataInvoice::where('client_id', '=', $client->id)->whereBetween('created_at',[$first_date,$last_date])->get();
        // $errors = DB::table('api_logs')->where('order_id', '!=','')->get();
        $banks_account = Banks::all()->sortBy("name");
        $all_users_blocked = Blocklist::whereIn('client_id', $clients_ids)->get();

        if(!empty($request->search)){
            $array_parametes = array(
                'search' => $request->search,
                'status' => ['confirmed', 'pending'],
                'clients' => $clients_ids,
                'types' => $type_transactions,
                'first_date' => $first_date,
                'last_date' => $last_date,
            );
        }else{
            $array_parametes = array(
                'clients' => $clients_ids,
                'types' => $type_transactions,
                'first_date' => $first_date,
                'last_date' => $last_date,
            );
        }

        $data = [
            'clients' => $client,
            'banks' => $banks_account,
            'transactions' => $transactions_paginate,
            'all_users_blocked' => $all_users_blocked,
            'client' => $client,
            'all_transactions' => $transactions->count(),
            'array_parameters' => $array_parametes,
            'request' => [
                "client_id" => $request->client_id,
                "date_start" => $request->date_start,
                "date_end" => $request->date_end,
                "search" => $request->search,
                "type_transaction" => $request->type_transaction,
            ]

        ];
        return view('transactions.index')->with('data',$data);

    }

    public function searchfind(Request $request){

        $FunctionsController = new FunctionsController();

        $clients_ids = [];
        $type_transactions = [];

        if(!empty($request->first_date)){
            $start = $FunctionsController->strtodate($request->first_date)." 00:00:00";
        }else{
            $start = "";
        }

        if(!empty($request->last_date)){
            $end = $FunctionsController->strtodate($request->last_date)." 23:59:59";
        }else{
            $end = "";
        }

        $today = date("Y-m-d 00:00:00");

        if(!isset($request->clients)){

            $clients = Clients::all();
            foreach($clients as $cli){
                array_push($clients_ids,$cli->id);
            }

        }else{

            foreach($request->clients as $num => $cli){
                array_push($clients_ids,$cli);
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

        $p = 1;
        // Defina aqui a quantidade máxima de registros por página.
        $qnt = 50;
        // O sistema calcula o início da seleção calculando:
        // (página atual * quantidade por página) - quantidade por página
        $inicio = ($p*$qnt) - $qnt;

        if(auth()->user()->level == 'master'){

            $first_date = $start;
            $last_date = $end;

            $transactions = Transactions::whereIn("client_id",$clients_ids)
            ->whereIn("type_transaction",$type_transactions)
            ->whereIn("status", ['confirmed', 'pending', 'canceled', 'refund', 'chargeback', 'freeze'])
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
                            ->orWhere('payment_id', '=', $request->search)
                            ->orWhere('user_name', 'LIKE', '%'.$request->search.'%');
                        }else{
                            $query->where('amount_solicitation', '=', $request->search);
                        }
                    }else{
                        $query->where('order_id', '=', $request->search)
                        ->orWhere('user_id', '=', $request->search)
                        ->orWhere('code', '=', $request->search)
                        ->orWhere('bank_data', '=', $request->search)
                        ->orWhere('payment_id', '=', $request->search)
                        ->orWhere('user_name', 'LIKE', '%'.$request->search.'%');
                    }
                }
            })
            ->orderBy("solicitation_date", "DESC")->get();

            $transactions_paginate = Transactions::whereIn("client_id",$clients_ids)
            ->whereIn("type_transaction",$type_transactions)
            ->whereIn("status", ['confirmed', 'pending', 'canceled', 'refund', 'chargeback', 'freeze'])
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
                            ->orWhere('payment_id', '=', $request->search)
                            ->orWhere('user_name', 'LIKE', '%'.$request->search.'%');

                        }else{
                            $query->where('amount_solicitation', '=', $request->search);
                        }
                    }else{
                        $query->where('order_id', '=', $request->search)
                        ->orWhere('user_id', '=', $request->search)
                        ->orWhere('code', '=', $request->search)
                        ->orWhere('bank_data', '=', $request->search)
                        ->orWhere('payment_id', '=', $request->search)
                        ->orWhere('user_name', 'LIKE', '%'.$request->search.'%');
                    }
                }
            })
            ->orderBy("solicitation_date", "DESC")
            ->offset($inicio)
            ->limit($qnt)->get();

            $client = Clients::all();
            $currency = 'R$ ';

        }else{

            $first_date = $start;
            $last_date = $end;

            $transactions = Transactions::where('client_id', auth()->user()->client_id)
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
                            ->orWhere('payment_id', '=', $request->search)
                            ->orWhere('user_name', 'LIKE', '%'.$request->search.'%');

                        }else{
                            $query->where('amount_solicitation', '=', $request->search);
                        }
                    }else{
                        $query->where('order_id', '=', $request->search)
                        ->orWhere('user_id', '=', $request->search)
                        ->orWhere('code', '=', $request->search)
                        ->orWhere('bank_data', '=', $request->search)
                        ->orWhere('payment_id', '=', $request->search)
                        ->orWhere('user_name', 'LIKE', '%'.$request->search.'%');
                    }
                }
            })
            ->orderBy("solicitation_date", "DESC")->get();

            $transactions_paginate = Transactions::where('client_id', auth()->user()->client_id)
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
            ->orderBy("solicitation_date", "DESC")
            ->offset($inicio)
            ->limit($qnt)->get();

            $client = Clients::where('id', auth()->user()->client_id)->get();
            $currency = 'R$ ';
        }


        $first_date = $start;
        $last_date = $end;

        // $invoices = DataInvoice::where('client_id', '=', $client->id)->whereBetween('created_at',[$first_date,$last_date])->get();
        // $errors = DB::table('api_logs')->where('order_id', '!=','')->get();
        $banks_account = Banks::all()->sortBy("name");
        $all_users_blocked = Blocklist::whereIn('client_id', $clients_ids)->get();

        if(!empty($request->search)){
            $array_parametes = array(
                'search' => $request->search,
                'status' => ['confirmed', 'pending'],
                'clients' => $clients_ids,
                'types' => $type_transactions,
                'first_date' => $first_date,
                'last_date' => $last_date,
            );
        }else{
            $array_parametes = array(
                'clients' => $clients_ids,
                'types' => $type_transactions,
                'first_date' => $first_date,
                'last_date' => $last_date,
            );
        }

        $data = [
            'clients' => $client,
            'banks' => $banks_account,
            'transactions' => $transactions_paginate,
            'all_users_blocked' => $all_users_blocked,
            'client' => $client,
            'all_transactions' => $transactions->count(),
            'array_parameters' => $array_parametes,
            'request' => [
                "client_id" => $request->client_id,
                "date_start" => $request->date_start,
                "date_end" => $request->date_end,
                "search" => $request->search,
                "type_transaction" => $request->type_transaction,
            ]

        ];

        return $data;

        return view('transactions.tableSearch')->with('data',$data);

    }
}
