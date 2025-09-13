<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use \App\Models\{Transactions,Banks,Clients,Logs,Extract};

class ApproveDepositController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $data = [
            'transactions' => Transactions::where('type_transaction', '=', 'deposit')
                ->where('method_transaction', '!=', 'bank_transfer')
                ->where('status', '=', 'pending')
                ->whereBetween('solicitation_date',[date("Y-m-d 00:00:00"),date("Y-m-d 23:59:59")])
                ->paginate(15),
            'clients' => Clients::all(),
            'banks' => Banks::all(),
            'request' => null
        ];
        return view('approveDeposit.index')->with('data',$data);
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
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function search(Request $request)
    {
        if($request->search == ''){
            $transactions = Transactions::where('type_transaction', '=', 'deposit')
                ->where('method_transaction', '!=', 'bank_transfer')
                ->where('client_id', '=', $request->client_id)->paginate(15);
        }else{
            $transactions = Transactions::where('type_transaction', '=', 'deposit')
                ->where('method_transaction', '!=', 'bank_transfer')
                ->where('order_id', '=', $request->search)
                ->where('client_id', '=', $request->client_id)->paginate(15);

        }

        $data = [
            'transactions' =>  $transactions,
            'clients' => Clients::all(),
            'banks' => Banks::all(),
            'request' => $request
        ];


        return view('approveDeposit.index')->with('data',$data);
    }

    public function aproove(Request $request)
    {

        $FunctionsController = new FunctionsController();

        $transaction = Transactions::where('type_transaction', '=', 'deposit')
            ->where('order_id', '=', $request->order_id)
            ->where('client_id', '=', $request->client_id)->first();

        $client = Clients::where("id",$transaction->client_id)->first();
        $tax = $client->tax;

        $final_amount = $transaction->amount_solicitation;

        $cotacao_dolar_markup = "1";
        $cotacao_dolar = "1";
        $spread_deposit = "0";

        if($transaction->method_transaction == 'invoice'){
            $day = $client->days_safe_boleto;
            $description_deposit_text = "Depósito por Boleto";
            $description_comission_text = "Comissão sobre Depósito Boleto";
            $tax_percent = $client->tax->boleto_percent;
            $tax_fixed = $client->tax->boleto_absolute;
            $tax_min = $client->tax->min_fee_boleto;
        }else if(strtolower($transaction->method_transaction) == 'ted'){
            $day = $client->days_safe_ted;
            $description_deposit_text = "Depósito por TED";
            $description_comission_text = "Comissão sobre Depósito TED";
            $tax_percent = $client->tax->replacement_percent;
            $tax_fixed = $client->tax->replacement_absolute;
            $tax_min = $client->tax->min_replacement;
        }else if($transaction->method_transaction == 'pix'){
            $day = $client->days_safe_pix;
            $description_deposit_text = "Depósito por PIX";
            $description_comission_text = "Comissão sobre Depósito PIX";
            $tax_percent = $client->tax->pix_percent;
            $tax_fixed = $client->tax->pix_absolute;
            $tax_min = $client->tax->min_fee_pix;
        }

        $percent_fee = ($transaction->amount_solicitation * ($tax_percent / 100));
        $fixed_fee = $tax_fixed;
        if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
        $comission = ($percent_fee + $fixed_fee);
        if($comission < $tax_min){ $comission = $tax_min; $min_fee = $tax_min; }else{ $min_fee = "NULL"; }


        DB::beginTransaction();
        try {

            if($request->amount_confirmed == $transaction->amount_solicitation){

                //Do update
                $transaction->update([
                    'final_amount' => $final_amount,
                    'fixed_fee' => $fixed_fee,
                    'percent_fee' => $percent_fee,
                    'comission' => $comission,
                    'paid_date' =>date('Y-m-d H:i:s'),
                    'final_date' =>date('Y-m-d H:i:s'),
                    'disponibilization_date' => date('Y-m-d 00:00:00',strtotime('+'.$day." days")),//setar a disponibilization date
                    'status' => 'confirmed',
                    'confirmed_bank' => 1
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
                    "description_text" => $description_deposit_text,
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
                    "description_text" => $description_comission_text,
                    "cash_flow" => ($transaction->comission * (-1)),
                    "final_amount" => ($transaction->comission * (-1)),
                    "quote" => $transaction->quote,
                    "quote_markup" => $transaction->quote_markup,
                    "receita" => 0.00,
                    "disponibilization_date" => $transaction->disponibilization_date,
                ]);

                Logs::create([
                    'user_id' =>  auth()->user()->id,
                    'client_id' =>  $client->id,
                    'type' =>  'system',
                    'action' => 'User '.auth()->user()->name.' aproove manually deposit order_id: '.$transaction->order_id,
                ]);

                DB::commit();

                // set post fields
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

                // $response = "Success";

                return json_encode(array('status' => 'success', 'message' => 'Deposit Approved Successfully', "response" => $response),true);

            }else{
                return json_encode(array('status' => 'error', 'message' => 'amount paid must be the same as requested amount'),true);
            }





        }catch (Exception $e) {
            DB::rollback();
            return json_encode(array('status' => 'error', 'message' => 'Server Error'),true);

        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $FunctionsController = new FunctionsController();

        $transaction = Transactions::where('id', $request->id)->first();

        $client = Clients::where("id",$transaction->client_id)->first();

        $approve_bank = 0;

        $valor_pago = $request->amount_solicitation;

        // Taxas
        //$tax = $client->tax;


        if($client->currency == "brl"){


            $final_amount = number_format($request->final_amount,6,".","");
            $amount_solicitation = number_format($request->final_amount,6,".","");
            $percent_fee = $request->percent_fee;
            $fixed_fee = $request->fixed_fee;
            if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
            $comission = ($percent_fee + $fixed_fee);
           // if($comission < $tax->min_fee_pix){ $comission = $tax->min_fee_pix; $min_fee = $tax->min_fee_pix; }else{ $min_fee = "NULL"; }

        }elseif($client->currency == "usd"){

            $final_amount = number_format($request->final_amount,6,".","");
            $amount_solicitation = number_format($request->final_amount,6,".","");
            $fixed_fee = number_format($request->fixed_fee,6,".","");
            $percent_fee = number_format($request->percent_fee,6,".","");
            if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
            $comission = ($percent_fee + $fixed_fee);
            //if($comission < $tax->min_fee_pix){ $comission = $tax->min_fee_pix; $min_fee = $tax->min_fee_pix; }else{ $min_fee = "NULL"; }

        }




        if($transaction->method == 'invoice' && $request->status = 'confirmed'){
            $approve_bank = 1;
        }



        DB::beginTransaction();
        try {




                switch($request->status){
                    case 'canceled':
                        //Do update
                        $transaction->update([
                            'final_amount' => $final_amount,
                            'amount_solicitation' => $amount_solicitation,
                            'fixed_fee' => $fixed_fee,
                            'percent_fee' => $percent_fee,
                            'comission' => $comission,
                            'cancel_date' =>date('Y-m-d H:i:s'),
                            'final_date' =>date('Y-m-d H:i:s'),
                            'status' => 'canceled',
                        ]);

                        break;
                        case 'pending':
                            //Do update
                            $transaction->update([
                                'final_amount' => $final_amount,
                                'amount_solicitation' => $final_amount,
                                'fixed_fee' => $fixed_fee,
                                'percent_fee' => $percent_fee,
                                'comission' => $comission,
                                'final_date' =>date('Y-m-d H:i:s'),
                                'status' => 'pending',
                            ]);

                            break;
                            case 'confirmed':

                            if($request->amount_solicitation == $transaction->amount_solicitation){

                                    $client = $transaction->client;

                                    if($transaction->method_transaction == 'invoice'){
                                        $day = $client->days_safe_boleto;
                                    }else if($transaction->method_transaction == 'automatic_checking'){
                                        $day = $client->days_safe_shop;
                                    }else if($transaction->method_transaction == 'pix'){
                                        $day = $client->days_safe_pix;
                                    }else{
                                        $day = $client->days_safe_credit_card;
                                    }
                                //Do update
                                $transaction->update([
                                    'amount_confirmed' => $valor_pago,
                                    'final_amount' => $final_amount,
                                    'amount_solicitation' => $final_amount,
                                    'fixed_fee' => $fixed_fee,
                                    'percent_fee' => $percent_fee,
                                    'comission' => $comission,
                                    'paid_date' =>date('Y-m-d H:i:s'),
                                    'final_date' =>date('Y-m-d H:i:s'),
                                    'disponibilization_date' => date('Y-m-d 00:00:00',strtotime('+'.$day." days")),//setar a disponibilization date
                                    'status' => 'confirmed',
                                ]);

                                  // set post fields
                                    $post = [
                                        "id" => $transaction->id
                                    ];

                                    $post_field = json_encode($post);

                                    // $ch = curl_init("http://18.224.111.184/fastpayments/public/api/approvecallback");
                                    // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                                    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    // curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field)));
                                    // curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

                                    // execute!
                                    $response = curl_exec($ch);

                                    curl_close($ch);



                                break;
                            }else{
                                return json_encode(array('status' => 'error', 'message' => 'amount paid must be the same as requested amount'),true);
                            }


                }

                Logs::create([
                    'user_id' =>  auth()->user()->id,
                    'client_id' =>  $client->id,
                    'type' =>  'system',
                    'action' => 'User '.auth()->user()->name.' edit transaction order_id: '.$transaction->order_id,
                ]);

                DB::commit();


                return json_encode(array('status' => 'success', 'message' => 'Updated Successfully'),true);

        }catch (Exception $e) {
            DB::rollback();
            return json_encode(array('status' => 'error', 'message' => 'Server Error'),true);

        }
    }

    public function reportError(Request $request)
    {

        DB::beginTransaction();
        try {
            $transaction = Transactions::where('type_transaction', '=', 'deposit')
                ->where('order_id', '=', $request->order_id)
                ->where('client_id', '=', $request->client_id)
                ->first();

            $client = $transaction->client;

            //Do update
            $transaction->update([
                'cancel_date' =>date('Y-m-d H:i:s'),
                'final_date' =>date('Y-m-d H:i:s'),
                'observation' => $request->type_error." - ".$request->description_error,
                'status' => 'canceled',
            ]);

            Logs::create([
                'user_id' =>  auth()->user()->id,
                'client_id' =>  $request->client_id,
                'type' =>  'system',
                'action' => 'User '.auth()->user()->name.' cancele manually deposit order_id: '.$transaction->order_id,
            ]);

            DB::commit();

            // set post fields
            $post = [
                "order_id" => $transaction->order_id,
                "user_id" => $transaction->user_id,
                "solicitation_date" => $transaction->solicitation_date,
                "cancel_date" => $transaction->cancel_date,
                "code_identify" => $transaction->code,
                "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                "status" => "canceled",
                "description" => $transaction->observation,
            ];

            $post_field = json_encode($post);
            $hash_hmac_fiat = $client->key->authorization_deposit;

            if($transaction->method_transaction == 'invoice'){
                $url_callback = $client->urlcallback_invoice;
            }else{
                $url_callback = $client->urlcallback_card;
            }
            $ch = curl_init($url_callback);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'authorization:'.$hash_hmac_fiat));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

            // execute!
            $response = curl_exec($ch);

            // $ch2 = curl_init("https://core.zappapay.com/cron/recive-callback-boleto.php");
            $ch2 = curl_init("https://webhook.site/b85cf0e9-58ff-4d12-8626-b81255979aa8");
            curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'authorization:'.$hash_hmac_fiat));
            curl_setopt($ch2, CURLOPT_POSTFIELDS, $post_field);

            // execute!
            $response2 = curl_exec($ch2);

            // close the connection, release resources used
            curl_close($ch2);

            return json_encode(array('status' => 'success', 'message' => 'Deposit Canceled Successfully'),true);

        }catch (Exception $e) {
            DB::rollback();
            return json_encode(array('status' => 'error', 'message' => 'Server Error'),true);

        }
    }

    public function getUser(Request $request)
    {
        $transaction = Transactions::where('type_transaction', '=', 'deposit')
                ->where('order_id', '=', $request->order_id)
                ->where('client_id', '=', $request->client_id)->first();

        if($transaction->user_account_data != ''){
            //user
            $array_user = json_decode(base64_decode($transaction->user_account_data),true);

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
            $address = $array_user['address'];
            $district = $array_user['district'];
            $uf = $array_user['uf'];
            $city = $array_user['city'];
            $cep = $array_user['cep'];
        }else{

            $account_number = '-';
            $user_name = '-';
            $user_document = '-';
            $bank_name = '-';
            $agency = '-';
            $account_number = '-';
            $address = '-';
            $district = '-';
            $uf = '-';
            $city = '-';
            $cep = '-';

        }

        $return = '
        <table width="100%" cellpadding="0" cellspacing="0">
            <tbody>
                <tr>
                    <td style="border: 1px solid #dedede;padding: 5px;">USER ID</td><td style="border: 1px solid #dedede;padding: 5px;">'.$transaction->user_id.'</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #dedede;padding: 5px;">USER NAME</td><td style="border: 1px solid #dedede;padding: 5px;">'.$user_name.'</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #dedede;padding: 5px;">USER DOCUMENT</td><td style="border: 1px solid #dedede;padding: 5px;">'.$user_document.'</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #dedede;padding: 5px;">USER ADDRESS</td><td style="border: 1px solid #dedede;padding: 5px;">'.$address.'</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #dedede;padding: 5px;">USER DISTRICT</td><td style="border: 1px solid #dedede;padding: 5px;">'.$district.'</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #dedede;padding: 5px;">USER CITY</td><td style="border: 1px solid #dedede;padding: 5px;">'.$city.'</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #dedede;padding: 5px;">USER UF</td><td style="border: 1px solid #dedede;padding: 5px;">'.$uf.'</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #dedede;padding: 5px;">USER CEP</td><td style="border: 1px solid #dedede;padding: 5px;">'.$cep.'</td>
                </tr>
            </tbody>
        </table>
        ';

        return json_encode(array('table'=>$return),true);


    }
}
