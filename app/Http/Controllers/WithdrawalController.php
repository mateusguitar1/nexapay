<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use \App\Models\{Extract,Clients,Transactions,Taxes,Keys,Banks,Logs,HistoryTax,User};
use App\Http\Controllers\FunctionsController;

class WithdrawalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $transactions = Transactions::where('type_transaction', 'withdraw')
            ->where("status","pending")
            ->orderby('id', 'DESC')
            ->get();

        $withdrawal_list = [];
        foreach($transactions as $transaction){
            $withdrawal_list[] = $transaction->id;
        }

        $transactionAmount = $transactions->sum('amount_solicitation');

        if(auth()->user()->level == "master"){
            $clients = Clients::orderBy("name","ASC")->get();
        }elseif(auth()->user()->level == "merchant"){
            $clients = Clients::where("id",auth()->user()->client->id)->get();
        }

        $data = [
            'transactionAmount' =>$transactionAmount,
            'transactions' => $transactions,
            // paginate(10),
            'transactionsAll' => $transactions->count(),
            'clients' => $clients,
            "banks" => Banks::all(),
            'request' => "",
            'model' => null,
            'title' => 'Create Transaction',
            'url' => url('/transactions'),
            'button' => 'Save',
            'withdrawal_list' => $withdrawal_list
        ];

        return view('withdrawal.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function newWithdraw(Request $request)
    {

        $FunctionsController = new FunctionsController();

        $method = $request->method;
        $order_id = $FunctionsController->gera_pedido($request->client_id);
        $date = date("Y-m-d H:i:s");

        if($method == "bank_info"){

            DB::beginTransaction();
            try {

                $user = array(
                    'name' =>$request->name,
                    'document' =>$request->document,
                    'bank_name' =>$request->name_bank,
                    'agency' =>$request->agency,
                    'account_number' =>$request->bank_account,
                    'account_type' =>$request->type_operation,
                );
                $user_account_data = base64_encode(json_encode($user,true));

                $transaction = Transactions::create([
                    'solicitation_date' => $date,
                    'final_date' => $date,
                    'order_id' => $order_id,
                    'client_id' => $request->client_id,
                    'amount_solicitation' => $FunctionsController->strtodouble($request->amount_solicitation),
                    'comission' => 0.00,
                    'user_id' => $request->user_id,
                    'user_account_data' => $user_account_data,
                    'user_name' => $request->name,
                    'user_document' => $request->document,
                    'status' => 'pending',
                    'type_transaction' => 'withdraw',
                    'method_transaction' => 'TEF',
                    'id_bank' => '14'
                ]);

                Logs::create([
                    'user_id' =>  auth()->user()->id,
                    'client_id' =>  $request->client_id,
                    'type' =>  'system',
                    'action' => 'User '.auth()->user()->name.' create manually withdraw order_id: '.$request->order_id,
                ]);

                DB::commit();

                return json_encode(array('status' => 'success', 'message' => 'Withdraw Created Successfully'),true);

            }catch (Exception $e) {
                DB::rollback();
                return json_encode(array('status' => 'error', 'message' => 'Server Error'),true);

            }

        }elseif($method == "crypto"){

            DB::beginTransaction();
            try {

                $user = array(
                    'hash_btc' =>$request->hash_btc,
                );

                $user_account_data = base64_encode(json_encode($user,true));

                $transaction = Transactions::create([
                    'solicitation_date' => $date,
                    'final_date' => $date,
                    'order_id' => $order_id,
                    'client_id' => $request->client_id,
                    'amount_solicitation' => $FunctionsController->strtodouble($request->amount_solicitation),
                    'comission' => 0.00,
                    'user_account_data' => $user_account_data,
                    'status' => 'pending',
                    'type_transaction' => 'withdraw',
                    'method_transaction' => 'TEF',
                    'id_bank' => '14',
                    'hash_btc' => $request->hash_btc,
                ]);

                DB::commit();

                return json_encode(array('status' => 'success', 'message' => 'Withdraw Created Successfully'),true);

            }catch (Exception $e) {
                DB::rollback();
                return json_encode(array('status' => 'error', 'message' => 'Server Error'),true);

            }

        }elseif($method == "pix"){

            DB::beginTransaction();
            try {

                $user = array(
                    'hash_btc' =>$request->pix_key,
                );

                $user_account_data = base64_encode(json_encode($user,true));

                $transaction = Transactions::create([
                    'solicitation_date' => $date,
                    'final_date' => $date,
                    'order_id' => $order_id,
                    'client_id' => $request->client_id,
                    'amount_solicitation' => $FunctionsController->strtodouble($request->amount_solicitation),
                    'comission' => 0.00,
                    'user_account_data' => $user_account_data,
                    'status' => 'pending',
                    'type_transaction' => 'withdraw',
                    'method_transaction' => 'TEF',
                    'id_bank' => '14',
                    'hash_btc' => $request->pix_key,
                ]);

                DB::commit();

                return json_encode(array('status' => 'success', 'message' => 'Withdraw Created Successfully'),true);

            }catch (Exception $e) {
                DB::rollback();
                return json_encode(array('status' => 'error', 'message' => 'Server Error'),true);

            }

        }elseif($method == "usdt"){

            DB::beginTransaction();
            try {

                $user = array(
                    'hash_usdt' =>$request->hash_usdt,
                );

                $user_account_data = base64_encode(json_encode($user,true));

                $transaction = Transactions::create([
                    'solicitation_date' => $date,
                    'final_date' => $date,
                    'order_id' => $order_id,
                    'client_id' => $request->client_id,
                    'amount_solicitation' => $FunctionsController->strtodouble($request->amount_solicitation),
                    'comission' => 0.00,
                    'user_account_data' => $user_account_data,
                    'status' => 'pending',
                    'type_transaction' => 'withdraw',
                    'method_transaction' => 'usdt-erc20',
                    'id_bank' => '14',
                    'hash_btc' => $request->hash_btc,
                ]);

                DB::commit();

                return json_encode(array('status' => 'success', 'message' => 'Withdraw Created Successfully'),true);

            }catch (Exception $e) {
                DB::rollback();
                return json_encode(array('status' => 'error', 'message' => 'Server Error'),true);

            }

        }

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


        $totalPage = 50;
        $currentPage = ( !empty($data['page']) ? $data['page'] : Null );
        $page = ( !empty($currentPage) ? $currentPage * $totalPage : 0 );

        $transactions = Transactions::
        where('type_transaction', '=', 'withdraw')->
        // where('method_transaction', '=', 'TEF')->
        whereBetween("final_date",[$start,$end])->
        getSearch($data['search'])->
        GetClientID($data['client_id'])->
        GetStatus($data['status'])->
        with(['client','bank'])->
        orderBy("solicitation_date","DESC")->
        offset($page)->
        limit($totalPage)->
        get();

        $withdrawal_list = [];
        foreach($transactions as $transaction){
            $withdrawal_list[] = $transaction->id;
        }

        $transactionsAll = Transactions::
        where('type_transaction', '=', 'withdraw')->
        // where('method_transaction', '=', 'TEF')->
        whereBetween("final_date",[$start,$end])->
        getSearch($data['search'])->
        GetClientID($data['client_id'])->
        GetStatus($data['status'])->
        count();

        $transactionAmount = $transactions->sum('amount_solicitation');


        $data = [
            'transactionAmount' =>$transactionAmount,
            'transactions' => $transactions,
            'clients' => Clients::all()->sortBy("name"),
            'request' => $request,
            'request_date' => $request->date,
            'banks' => Banks::all(),
            'request' => "",
            'model' => null,
            'title' => 'Create Transaction',
            'url' => url('/transactions'),
            'button' => 'Save',
            'transactionsAll' => ( !empty($transactionsAll) ? $transactionsAll : 0 ),
            'totalPage' => ( !empty($transactions['totalPage']) ? $transactions['totalPage'] : 50 ),
            'currentPage' => $currentPage,
            'current_search' => $request->except('_token'),
            'withdrawal_list' => $withdrawal_list,
        ];

        return view('withdrawal.index')->with('data',$data);
    }

    public function approve(Request $request)
    {
        $data = $request->all();
        if($request->hasFile('arquivo')){

            if($request->file('arquivo')->isValid()){
                $nome = md5(date('Y-m-d H:i:s'));
                $extensao = $request->arquivo->extension();
                $nameFile = "{$nome}.{$extensao}";

                $upload = $request->arquivo->storeAs('public/upload/upcomprovante', $nameFile);
                if(!$upload){
                    return back()->with('error', 'Upload File Error');
                }
            }
        }

        $nameFile = ( !empty($nameFile) ? $nameFile : Null );

        $FunctionsController = new FunctionsController();

        $transaction = Transactions::where('type_transaction', '=', 'withdraw')
            ->where('order_id', '=', $data['order_id'])
            ->where('client_id', '=', $data['client_id'])->first();

        $client = Clients::where("id","=",$data['client_id'])->first();

        // Taxas
        $tax = $client->tax;

        $cotacao_dolar_markup = "1";
        $cotacao_dolar = "1";
        $spread_deposit = "0";

        $final_amount = $transaction->amount_solicitation;
        $percent_fee = ($final_amount * ($tax->withdraw_percent / 100));
        $fixed_fee = $tax->withdraw_absolute;
        if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
        $comission = ($percent_fee + $fixed_fee);
        if($comission < $tax->min_fee_withdraw){ $comission = $tax->min_fee_withdraw; $min_fee = $tax->min_fee_withdraw; }else{ $min_fee = "NULL"; }

        $date = date('Y-m-d H:i:s');
        $date_disponibilization = date('Y-m-d 00:00:00');

        DB::beginTransaction();
        try {

            //Do update
            $transaction->update([
                'amount_confirmed' => $transaction->amount_solicitation,
                'final_amount' => $transaction->amount_solicitation,
                'paid_date' => $date,
                'final_date' => $date,
                'disponibilization_date' => $date_disponibilization,
                'status' => 'confirmed',
                'receipt' => $nameFile,
                'quote' => $cotacao_dolar,
                'percent_markup' => $spread_deposit,
                'quote_markup' => $cotacao_dolar_markup,
                'fixed_fee' => $fixed_fee,
                'percent_fee' => $percent_fee,
                'comission' => $comission,
                'min_fee' => $min_fee,
                'id_bank' => '14',
                'confirmation_callback' => '1',
            ]);

            if(($transaction->hash_btc != null)){
                $description = "Saque de saldo em BTC";
                $description_fee = "Taxa sobre saque de saldo em BTC";
            }else{
                $description = "Saque de saldo em BRL";
                $description_fee = "Taxa sobre saque de saldo em BRL";
            }

            Extract::create([
                "transaction_id" => $transaction->id,
                "order_id" => $transaction->order_id,
                "client_id" => $transaction->client_id,
                "user_id" => $transaction->user_id,
                "type_transaction_extract" => "cash-out",
                "description_code" => "MD03",
                "description_text" => $description,
                "cash_flow" => $transaction->amount_solicitation,
                "final_amount" => ($transaction->amount_solicitation * (-1)),
                "quote" => $cotacao_dolar,
                "quote_markup" => $cotacao_dolar_markup,
                "receita" => 0.00,
                "disponibilization_date" => $date_disponibilization,
            ]);

            Extract::create([
                "transaction_id" => $transaction->id,
                "order_id" => $transaction->order_id,
                "client_id" => $transaction->client_id,
                "user_id" => $transaction->user_id,
                "type_transaction_extract" => "cash-out",
                "description_code" => "CM03",
                "description_text" => $description_fee,
                "cash_flow" => $comission,
                "final_amount" => ($comission * (-1)),
                "quote" => $cotacao_dolar,
                "quote_markup" => $cotacao_dolar_markup,
                "receita" => 0.00,
                "disponibilization_date" => $date_disponibilization,
            ]);

            DB::commit();

           // set post fields
           $post = [
                "order_id" => $transaction->order_id,
                "user_id" => $transaction->user_id,
                "solicitation_date" => $transaction->solicitation_date,
                "paid_date" => $transaction->paid_date,
                "code_identify" => $transaction->code,
                "amount_solicitation" => $transaction->amount_solicitation,
                "amount_send" => $transaction->amount_confirmed,
                "status" => "confirmed"
            ];

            $post_field = json_encode($post);
            $hash_hmac_fiat = $client->key->authorization_withdraw;

            $url_callback = $client->key->url_callback_withdraw;

            $ch = curl_init($url_callback);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'Token:'.$hash_hmac_fiat));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

            // execute!
            $response = curl_exec($ch);
            $http_status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);


            if($http_status == "200"){

                $transaction->update([
                    "confirmation_callback" => "1"
                ]);

                DB::commit();

            }

            // return json_encode(array('status' => 'success', 'message' => 'Withdraw Approved Successfully'),true);
            return redirect('withdrawal')->with('success', 'Withdraw Approved Successfully');


        }catch (Exception $e) {
            DB::rollback();
            return back()->with('error', 'Server error');

        }
    }

    public function updateComission(Request $request){

        $FunctionsController = new FunctionsController();

        $data = $request->all();

        $transaction = Transactions::where('type_transaction', 'withdraw')
        ->where('id', '=', $data['id'])->first();

        $atual_status = $transaction->status;

        $client = Clients::where("id","=",$transaction->client_id)->first();

        if($request->hasFile('arquivo')){

            if($request->file('arquivo')->isValid()){
                $nome = md5(date('Y-m-d H:i:s'));
                $extensao = $request->arquivo->extension();
                $nameFile = "{$nome}.{$extensao}";

                $upload = $request->arquivo->storeAs('upcomprovante/', $nameFile, 's3');

                // if(!$upload){
                //     return back()->with('error', 'Upload File Error');
                // }
            }
        }

        $nameFile = ( !empty($nameFile) ? $nameFile : Null );

        $final_amount = $data['amount_solicitation'];
        $amount_solicitation = $data['amount_solicitation'];
        $percent_fee = $data['percent_fee'];
        $fixed_fee = $data['fixed_fee'];
        if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
        $comission = ($percent_fee + $fixed_fee);

        $date_disponibilization = date('Y-m-d 00:00:00');

        DB::beginTransaction();
        try {

            switch($data['status']){
                case 'pending':
                    //Do update
                    $transaction->update([
                        'percent_fee' => $percent_fee,
                        'fixed_fee' => $fixed_fee,
                        'comission' => $comission,
                        'amount_solicitation' => $amount_solicitation,
                        'final_amount' => $final_amount,
                        'receipt' => $nameFile,
                        'final_date' =>date('Y-m-d H:i:s'),
                        'status' => 'pending',
                        'id_bank' => $client->bankWithdrawPix->id
                    ]);
                    break;
                case 'canceled':
                    //Do update
                    $transaction->update([
                        'percent_fee' => $percent_fee,
                        'fixed_fee' => $fixed_fee,
                        'comission' => $comission,
                        'amount_solicitation' => $amount_solicitation,
                        'final_amount' => $final_amount,
                        'receipt' => $nameFile,
                        'cancel_date' =>date('Y-m-d H:i:s'),
                        'final_date' =>date('Y-m-d H:i:s'),
                        'status' => 'canceled',
                        'id_bank' => $client->bankWithdrawPix->id
                    ]);
                    break;
                case 'confirmed':

                    //Do update
                    $transaction->update([
                        'percent_fee' => $percent_fee,
                        'fixed_fee' => $fixed_fee,
                        'comission' => $comission,
                        'amount_solicitation' => $amount_solicitation,
                        'final_amount' => $final_amount,
                        'receipt' => $nameFile,
                        'paid_date' =>date('Y-m-d H:i:s'),
                        'final_date' =>date('Y-m-d H:i:s'),
                        'status' => 'confirmed',
                        'disponibilization_date' => $date_disponibilization,
                        'id_bank' => $client->bankWithdrawPix->id
                    ]);

                    if(strtolower($transaction->method_transaction) == "ted" || strtolower($transaction->method_transaction) == "tef"){
                        $description_text_first = "Saque TED";
                        $description_text_second = "Comissão sobre Saque TED";
                        $description_code_first = "MS01";
                        $description_code_second = "CM04";
                    }elseif(strtolower($transaction->method_transaction) == "pix"){
                        $description_text_first = "Saque PIX";
                        $description_text_second = "Comissão sobre Saque PIX";
                        $description_code_first = "MS02";
                        $description_code_second = "CM04";
                    }elseif(strtolower($transaction->method_transaction) == "usdt-erc20"){
                        $description_text_first = "Saque USDT-ERC20";
                        $description_text_second = "Comissão sobre Saque USDT-ERC20";
                        $description_code_first = "MS03";
                        $description_code_second = "CM05";
                    }

                    if($atual_status != "confirmed"){

                        // Deposit
                        Extract::create([
                            "transaction_id" => $transaction->id,
                            "order_id" => $transaction->order_id,
                            "client_id" => $transaction->client_id,
                            "user_id" => $transaction->user_id,
                            "bank_id" => $transaction->id_bank,
                            "type_transaction_extract" => "cash-out",
                            "description_code" => $description_code_first,
                            "description_text" => $description_text_first,
                            "cash_flow" => ($transaction->amount_solicitation  * (-1)),
                            "final_amount" => ($transaction->final_amount  * (-1)),
                            "quote" => $transaction->quote,
                            "quote_markup" => $transaction->quote_markup,
                            "receita" => 0.00,
                            "disponibilization_date" => $date_disponibilization,
                            'bank_id' => $client->bankWithdrawPix->id
                        ]);

                        // Comission
                        Extract::create([
                            "transaction_id" => $transaction->id,
                            "order_id" => $transaction->order_id,
                            "client_id" => $transaction->client_id,
                            "user_id" => $transaction->user_id,
                            "bank_id" => $transaction->id_bank,
                            "type_transaction_extract" => "cash-out",
                            "description_code" => $description_code_second,
                            "description_text" => $description_text_second,
                            "cash_flow" => ($transaction->comission * (-1)),
                            "final_amount" => ($transaction->comission * (-1)),
                            "quote" => $transaction->quote,
                            "quote_markup" => $transaction->quote_markup,
                            "receita" => 0.00,
                            "disponibilization_date" => $date_disponibilization,
                            'bank_id' => $client->bankWithdrawPix->id
                        ]);

                    }

                    break;
            }

            Logs::create([
                'user_id' =>  auth()->user()->id,
                'client_id' =>  $transaction->client_id,
                'type' =>  'system',
                'action' => 'User '.auth()->user()->name.' update manually withdraw order_id: '.$transaction->order_id,
            ]);

            DB::commit();

            if($data['status'] == "confirmed" && $atual_status != "confirmed"){

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

            }

            // return json_encode(array('status' => 'success', 'message' => 'Withdraw Approved Successfully'),true);
            return redirect('withdrawal')->with('success', 'Withdraw Approved Successfully');


        }catch (Exception $e) {
            DB::rollback();
            return back()->with('error', 'Server error');

        }
    }

    public function reportError(Request $request){
        $data = $request->all();

        DB::beginTransaction();
        try {
            // $transaction = Transactions::where('type_transaction', '=', 'withdraw')
            //     ->where('order_id', '=', $request->order_id)
            //     ->where('client_id', '=', $request->client_id)->first();

            $transaction = Transactions::find($data['order_id']);


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


            $ch2 = curl_init("https://webhook.site/9e77b96e-b25a-4fd5-b096-f48078d95565");
            curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'token:'.$authentication));
            curl_setopt($ch2, CURLOPT_POSTFIELDS, $post_field);

            // execute!
            $response2 = curl_exec($ch2);

            // close the connection, release resources used
            curl_close($ch2);

            return redirect('/withdrawal')->with('success', 'Withdraw Canceled Successfully');
            return json_encode(array('status' => 'success', 'message' => 'Withdraw Canceled Successfully'),true);

        }catch (Exception $e) {
            DB::rollback();
            return redirect('/withdrawal')->with('error', 'Server Error');
            return json_encode(array('status' => 'error', 'message' => 'Server Error'),true);

        }
    }

    public function cancel(Request $request)
    {
        DB::beginTransaction();
        try {
            $transaction = Transactions::where('type_transaction', '=', 'withdraw')
                ->where('order_id', '=', $request->order_id)
                ->where('client_id', '=', $request->client_id)->first();

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
            $hash_hmac_fiat = $client->key->authorization_deposit;

            // if($transaction->method_transaction == 'invoice'){
            //     $url_callback = $client->urlcallback_invoice;
            // }else{
            //     $url_callback = $client->urlcallback_card;
            // }
            // $ch = curl_init($url_callback);
            // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'authorization:'.$hash_hmac_fiat));
            // curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

            // // execute!
            // $response = curl_exec($ch);

            // $ch2 = curl_init("https://core.zappapay.com/cron/recive-callback-boleto.php");
            $ch2 = curl_init("https://webhook.site/268f7f7c-904e-4ea3-979d-e3560728e7c6");
            curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'authorization:'.$hash_hmac_fiat));
            curl_setopt($ch2, CURLOPT_POSTFIELDS, $post_field);

            // execute!
            $response2 = curl_exec($ch2);

            // close the connection, release resources used
            curl_close($ch2);

            return json_encode(array('status' => 'success', 'message' => 'Withdraw Canceled Successfully'),true);

        }catch (Exception $e) {
            DB::rollback();
            return json_encode(array('status' => 'error', 'message' => 'Server Error'),true);

        }
    }

    public function delete(Request $request)
    {
       $FunctionsController = new FunctionsController();


       DB::beginTransaction();
       try {

        $id = $request->order_id;

           $transaction = Transactions::where('id', $id)->delete();
           Logs::create([
               'user_id' =>  auth()->user()->id,
               'type' =>  'system',
               'action' => 'User '.auth()->user()->name.' deleted withdraw order_id: '.$request->order_id,
           ]);

           DB::commit();

           return json_encode(array('status' => 'success', 'message' => 'Withdraw Created Successfully'),true);

       }catch (Exception $e) {
           DB::rollback();
           return json_encode(array('status' => 'error', 'message' => 'Server Error'),true);

       }
    }

    public function split(Request $request){

        $FunctionsController = new FunctionsController();
        $total = 0;
        $total_fee = 0;



        foreach($request->amount_new as $item){
            $total = $total + $FunctionsController->strtodouble($item);
        }

        foreach($request->fee_new as $item){
            $total_fee = $total_fee + $item;
        }

        $amount = $FunctionsController->strtodouble($request->amount);


         if($amount != number_format($total, 2, '.', '')){
             return json_encode(array('status' => 'error', 'message' => 'The amount_solicitation values must be equal.'),true);
         }

         if($request->fee != $total_fee){
            return json_encode(array('status' => 'error', 'message' => 'The fee values must be equal.'),true);
        }




        $id = $request->transaction;
        $transaction = Transactions::find($id);
        $order_id = $transaction->order_id;
        $user_id = '-';

        $trupdate = 0;

        if($transaction->user_id === null || $transaction->user_id == ''){
            $user_id = '-';
        }else{
            $user_id = $transaction->user_id;
        }

        dd($user_id);

        DB::beginTransaction();
        try {

            for($i = 0; $i < count($request->fee_new); $i++){
                $order = $order_id . "." .($i + 1);
                if($trupdate == 0){
                    $trupdate++;

                    $transaction->update([
                        'amount_solicitation' => $FunctionsController->strtodouble($request->amount_new[$i]),
                        'final_amount' => $FunctionsController->strtodouble($request->amount_new[$i]),
                        'comission' => $FunctionsController->strtodouble($request->fee_new[$i]),
                        'order_id' => $order,
                        'user_id' => $user_id,
                    ]);

                }else{


                    Transactions::create([
                        'solicitation_date' => $transaction->solicitation_date,
                        'final_date' => $transaction->final_date,
                        'order_id' => $order,
                        'client_id' => $transaction->client_id,
                        'user_document' => $transaction->user_document,
                        'amount_solicitation' => $FunctionsController->strtodouble($request->amount_new[$i]),
                        'final_amount' => $FunctionsController->strtodouble($request->amount_new[$i]),
                        'comission' => $FunctionsController->strtodouble($request->fee_new[$i]),
                        'user_id' => $user_id,
                        'user_account_data' => $transaction->user_account_data,
                        'code' => $transaction->code,
                        'quote' => $transaction->quote,
                        'percent_fee' => $transaction->percent_fee,
                        'fixed_fee' => $transaction->fixed_fee,
                        'status' => $transaction->status,
                        'created_at' => $transaction->created_at,
                        'updated_at' => $transaction->updated_at,
                        'type_transaction' => 'withdraw',
                        'method_transaction' => 'TEF',
                        'id_bank' => '14'
                    ]);
                }
            }



            Logs::create([
                'user_id' =>  auth()->user()->id,
                'client_id' =>  $transaction->client_id,
                'type' =>  'system',
                'action' => 'User '.auth()->user()->name.' split the transaction order_id: '.$transaction->order_id,
            ]);

            DB::commit();

            return json_encode(array('status' => 'success', 'message' => 'Split Successfully'),true);


        }catch (Exception $e) {
            DB::rollback();
            return back()->with('error', 'Server error');

        }


    }

    //pdf
    public function pdf(Request $request){

        $dados = json_decode($request->transactions_pdf[0], true);


        $date = date('d-m-Y');
        $title_page = "PDF Withdraw - ".$date;

        $mpdf = new Mpdf(['mode' => 'utf-8','format' => 'A4-L','margin_left' => 0,'margin_right' => 0,'margin_top' => 0,'margin_bottom' => 0,'margin_header' => 0,'margin_footer' => 0]);

        $html = "<!DOCTYPE html><html lang='pt'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>".$title_page."</title></head><body>";

        $html .= "
        <table style='margin-bottom:0 !important;'>
            <thead>
                <tr>
                    <td style='border:none !important;background-color:#000;color:#fff;width: 8% !important; font-family:Arial;text-align:center';>CLIENT</td>
                    <td style='border:none !important;background-color:#000;color:#fff;width: 8% !important; font-family:Arial;text-align:center';>ID A4P</td>
                    <td style='border:none !important;background-color:#000;color:#fff;width: 12% !important; font-family:Arial;text-align:center';>BANK</td>
                    <td style='border:none !important;background-color:#000;color:#fff;width: 5% !important; font-family:Arial;text-align:center';>AGENCY</td>
                    <td style='border:none !important;background-color:#000;color:#fff;width: 7% !important; font-family:Arial;text-align:center';>ACCOUNT</td>
                    <td style='border:none !important;background-color:#000;color:#fff;width: 9% !important; font-family:Arial;text-align:center';>DOCUMENT</td>
                    <td style='border:none !important;background-color:#000;color:#fff;width: 15% !important; font-family:Arial;text-align:center';>NAME</td>
                    <td style='border:none !important;background-color:#000;color:#fff;width: 10% !important; font-family:Arial;text-align:center';>AMOUNT</td>
                </tr>
            </thead>
        ";

        // -------------------------------------- //

        foreach($dados as $statement){

            $client_name = Clients::select('name')
                ->where("id", "=", $statement['client_id'])
                ->get()->first();

            if($statement['user_account_data']){
                $array_user = json_decode(base64_decode($statement['user_account_data']),true);

                $account_number = '';
                $user_name = '';
                $user_document = '';
                $bank_name = '';
                $agency = '';

                $user_name = $array_user['name'];
                $user_document = $array_user['document'];
                $bank_name = $array_user['bank_name'];
                $agency = $array_user['agency'];
                $account_number = $array_user['account_number'];

            }else{

                $account_number = '';
                $user_name = '';
                $user_document = '';
                $bank_name = '';
                $agency = '';

            }
            $html .= "<tr>";
            $html .= "
                <td style='border:1px solid #dedede;font-size:14px;padding:1px 0;font-family:Arial;text-align:center'>".$client_name->name."</td>
                <td style='border:1px solid #dedede;font-size:14px;padding:1px 0;font-family:Arial;text-align:center'>".$statement['id']."</td>
                <td style='border:1px solid #dedede;font-size:14px;padding:1px 0;font-family:Arial;text-align:center'>".$bank_name."</td>
                <td style='border:1px solid #dedede;font-size:14px;padding:1px 0;font-family:Arial;text-align:center'>".$agency."</td>
                <td style='border:1px solid #dedede;font-size:14px;padding:1px 0;font-family:Arial;text-align:center'>".$account_number."</td>
                <td style='border:1px solid #dedede;font-size:14px;padding:1px 0;font-family:Arial;text-align:center'>".$user_document."</td>
                <td style='border:1px solid #dedede;font-size:14px;padding:1px 0;font-family:Arial;text-align:center'>".$user_name."</td>
            ";

            if(doubletostrH($statement['amount_solicitation'])  < 0){
                $html .= "<td style='border:1px solid #dedede;font-size:12px;padding:1px 0;font-family:Arial;text-align:center;color:#000;'>".doubletostrH($statement['amount_solicitation'])."</td>";
            }else{
                $html .= "<td style='border:1px solid #dedede;font-size:12px;padding:1px 0;font-family:Arial;text-align:center;color:#000;'>".doubletostrH($statement['amount_solicitation'])."</td>";
            }
            $html .= "</tr>";
        }


        // End table
        $html .= "</tbody></table>";

        $html .= "</body></html>";



        $mpdf->WriteHTML($html);

        $filename = "pdf-withdraw-".$date.".pdf";

        $mpdf->Output($filename, 'D');
        //$mpdf->Output();
    }

    public function approvePixBatch(Request $request){

        $withdrawal_list = json_decode($request->withdrawal_list,true);
        $data = [];

        foreach($withdrawal_list as $withdrawal){
            $list[] = array($withdrawal);
        }

        $FunctionsController = new FunctionsController();

        $transactions = Transactions::whereIn('id', $list)->get();

        foreach($transactions as $transaction){

            $client = Clients::where("id","=",$transaction->client_id)->first();

            $final_amount = $transaction->amount_solicitation;
            $amount_solicitation = $transaction->amount_solicitation;
            $percent_fee = $transaction->percent_fee;
            $fixed_fee = $transaction->fixed_fee;
            if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
            $comission = $transaction->comission;
            $date_disponibilization = date('Y-m-d 00:00:00');

            DB::beginTransaction();
            try {

                //Do update
                $transaction->update([
                    'percent_fee' => $percent_fee,
                    'fixed_fee' => $fixed_fee,
                    'comission' => $comission,
                    'amount_solicitation' => $amount_solicitation,
                    'final_amount' => $final_amount,
                    'paid_date' =>date('Y-m-d H:i:s'),
                    'final_date' =>date('Y-m-d H:i:s'),
                    'status' => 'confirmed',
                    'disponibilization_date' => $date_disponibilization,
                    'id_bank' => $client->bankWithdrawPix->id
                ]);

                if(strtolower($transaction->method_transaction) == "ted" || strtolower($transaction->method_transaction) == "tef"){
                    $description_text_first = "Saque TED";
                    $description_text_second = "Comissão sobre Saque TED";
                    $description_code_first = "MS01";
                    $description_code_second = "CM04";
                }elseif(strtolower($transaction->method_transaction) == "pix"){
                    $description_text_first = "Saque PIX";
                    $description_text_second = "Comissão sobre Saque PIX";
                    $description_code_first = "MS02";
                    $description_code_second = "CM04";
                }elseif(strtolower($transaction->method_transaction) == "usdt-erc20"){
                    $description_text_first = "Saque USDT-ERC20";
                    $description_text_second = "Comissão sobre Saque USDT-ERC20";
                    $description_code_first = "MS03";
                    $description_code_second = "CM05";
                }

                // Deposit
                Extract::create([
                    "transaction_id" => $transaction->id,
                    "order_id" => $transaction->order_id,
                    "client_id" => $transaction->client_id,
                    "user_id" => $transaction->user_id,
                    "bank_id" => $transaction->id_bank,
                    "type_transaction_extract" => "cash-out",
                    "description_code" => $description_code_first,
                    "description_text" => $description_text_first,
                    "cash_flow" => ($transaction->amount_solicitation  * (-1)),
                    "final_amount" => ($transaction->final_amount  * (-1)),
                    "quote" => $transaction->quote,
                    "quote_markup" => $transaction->quote_markup,
                    "receita" => 0.00,
                    "disponibilization_date" => $date_disponibilization,
                    'bank_id' => $client->bankWithdrawPix->id
                ]);

                // Comission
                Extract::create([
                    "transaction_id" => $transaction->id,
                    "order_id" => $transaction->order_id,
                    "client_id" => $transaction->client_id,
                    "user_id" => $transaction->user_id,
                    "bank_id" => $transaction->id_bank,
                    "type_transaction_extract" => "cash-out",
                    "description_code" => $description_code_second,
                    "description_text" => $description_text_second,
                    "cash_flow" => ($transaction->comission * (-1)),
                    "final_amount" => ($transaction->comission * (-1)),
                    "quote" => $transaction->quote,
                    "quote_markup" => $transaction->quote_markup,
                    "receita" => 0.00,
                    "disponibilization_date" => $date_disponibilization,
                    'bank_id' => $client->bankWithdrawPix->id
                ]);

                Logs::create([
                    'user_id' =>  auth()->user()->id,
                    'client_id' =>  $transaction->client_id,
                    'type' =>  'system',
                    'action' => 'User '.auth()->user()->name.' update manually withdraw order_id: '.$transaction->order_id,
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


            }catch (Exception $e) {
                DB::rollback();
                return back()->with('error', 'Server error');

            }

        }

        return response()->json(["status" => "success", "message" => "Withdraw process successfully!"]);

    }

    public function sendPixBatch(Request $request){

        $withdrawal_list = json_decode($request->withdrawal_list,true);
        $data = [];

        foreach($withdrawal_list as $withdrawal){
            $list[] = array($withdrawal);
        }

        $FunctionsController = new FunctionsController();

        $transactions = Transactions::whereIn('id', $list)->get();

        foreach($transactions as $transaction){

            $client = Clients::where("id","=",$transaction->client_id)->first();
            $bank_withdraw = Banks::where("id","9")->first();

            \App\Jobs\PerformWithdrawalPaymentPIX::dispatch($transaction->id,$bank_withdraw->id)->delay(now()->addSeconds('5'));

        }

        return response()->json(["status" => "success", "message" => "Withdraw sending successfully!"]);

    }
}
