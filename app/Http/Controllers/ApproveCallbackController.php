<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Models\{Clients,Transactions};

class ApproveCallbackController extends Controller
{
    //
    public function sendCallback(Request $request){

        $FunctionsController = new FunctionsController();

        $transaction = Transactions::where("id","=",$request->id)->first();
        $client = Clients::where("id","=",$transaction->client_id)->first();

        if($transaction->type_transaction == "deposit"){
            $url_callback = $client->key->url_callback;
        }elseif($transaction->type_transaction == "withdraw"){
            $url_callback = $client->key->url_callback_withdraw;
        }

        $confirmed_bank_check = "none";
        $user_account_data = json_decode(base64_decode($transaction->user_account_data),true);

        if($transaction->type_transaction == "deposit"){

            if($transaction->status == "confirmed"){
                if($transaction->code_bank == "100"){

                        // set post fields
                        $post = [
                            "id" => $transaction->id,
                            "fast_id" => $transaction->id,
                            "type_transaction" => $transaction->type_transaction,
                            "order_id" => $transaction->order_id,
                            "user_id" => $transaction->user_id,
                            "solicitation_date" => $transaction->solicitation_date,
                            "paid_date" => $transaction->paid_date,
                            "code_identify" => $transaction->code,
                            "provider_reference" => $transaction->id,
                            "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                            "amount_confirmed" => number_format($transaction->final_amount,2,'.',''),
                            "comission" => $transaction->comission,
                            "status" => $transaction->status,
                            "stage" => "payment_done"
                        ];

                }else{
                    // set post fields
                    $post = [
                        "id" => $transaction->id,
                        "fast_id" => $transaction->id,
                        "type_transaction" => $transaction->type_transaction,
                        "order_id" => $transaction->order_id,
                        "user_id" => $transaction->user_id,
                        "solicitation_date" => $transaction->solicitation_date,
                        "paid_date" => $transaction->paid_date,
                        "code_identify" => $transaction->code,
                        "provider_reference" => $transaction->id,
                        "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                        "amount_confirmed" => number_format($transaction->final_amount,2,'.',''),
                        "comission" => $transaction->comission,
                        "status" => $transaction->status,
                    ];
                }
            }elseif($transaction->status == "canceled"){
                // set post fields
                $post = [
                    "id" => $transaction->id,
                    "fast_id" => $transaction->id,
                    "type_transaction" => $transaction->type_transaction,
                    "order_id" => $transaction->order_id,
                    "user_id" => $transaction->user_id,
                    "solicitation_date" => $transaction->solicitation_date,
                    "cancel_date" => $transaction->cancel_date,
                    "code_identify" => $transaction->code,
                    "provider_reference" => $transaction->id,
                    "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                    "status" => $transaction->status,
                ];
            }elseif($transaction->status == "refund"){
                // set post fields
                $post = [
                    "id" => $transaction->id,
                    "fast_id" => $transaction->id,
                    "type_transaction" => $transaction->type_transaction,
                    "order_id" => $transaction->order_id,
                    "user_id" => $transaction->user_id,
                    "solicitation_date" => $transaction->solicitation_date,
                    "refund_date" => $transaction->refund_date,
                    "code_identify" => $transaction->code,
                    "provider_reference" => $transaction->id,
                    "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                    "status" => $transaction->status,
                ];
            }

        }elseif($transaction->type_transaction == "withdraw"){

            if($transaction->status == "confirmed"){

                if($transaction->method_transaction == "TED"){

                    // set post fields
                    $post = [
                        "id" => $transaction->id,
                        "fast_id" => $transaction->id,
                        "type_transaction" => $transaction->type_transaction,
                        "order_id" => $transaction->order_id,
                        "solicitation_date" => $transaction->solicitation_date,
                        "user_id" => $transaction->user_id,
                        "user_name" => $user_account_data['name'],
                        "user_document" => $user_account_data['document'],
                        "bank_name" => $user_account_data['bank_name'],
                        "agency" => $user_account_data['agency'],
                        "type_operation" => $user_account_data['operation_bank'],
                        "account" => $user_account_data['account_number'],
                        "paid_date" => $transaction->paid_date,
                        "code_identify" => $transaction->code,
                        "provider_reference" => $transaction->id,
                        "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                        "amount_confirmed" => number_format($transaction->final_amount,2,'.',''),
                        "comission" => $transaction->comission,
                        "status" => $transaction->status,
                    ];

                }elseif($transaction->method_transaction == "pix"){

                    // set post fields
                    $post = [
                        "id" => $transaction->id,
                        "fast_id" => $transaction->id,
                        "type_transaction" => $transaction->type_transaction,
                        "order_id" => $transaction->order_id,
                        "solicitation_date" => $transaction->solicitation_date,
                        "user_id" => $transaction->user_id,
                        "user_name" => $user_account_data['name'],
                        "user_document" => $user_account_data['document'],
                        "paid_date" => $transaction->paid_date,
                        "code_identify" => $transaction->code,
                        "provider_reference" => $transaction->id,
                        "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                        "amount_confirmed" => number_format($transaction->final_amount,2,'.',''),
                        "comission" => $transaction->comission,
                        "status" => $transaction->status,
                    ];

                }


            }elseif($transaction->status == "canceled"){

                // set post fields
                $post = [
                    "id" => $transaction->id,
                    "fast_id" => $transaction->id,
                    "type_transaction" => $transaction->type_transaction,
                    "order_id" => $transaction->order_id,
                    "solicitation_date" => $transaction->solicitation_date,
                    "user_id" => $transaction->user_id,
                    "user_name" => $user_account_data['name'],
                    "user_document" => $user_account_data['document'],
                    "cancel_date" => $transaction->cancel_date,
                    "code_identify" => $transaction->code,
                    "provider_reference" => $transaction->id,
                    "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                    "status" => $transaction->status,
                ];

            }

        }

        $post_field = json_encode($post);

        // $ch2 = curl_init("https://webhook.site/e2fec21b-7a70-4941-aa3d-18f25ac7fe31");
        // curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "POST");
        // curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch2, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'Token:'.$client->key->authorization));
        // curl_setopt($ch2, CURLOPT_POSTFIELDS, $post_field);

        // // execute!
        // $response2 = curl_exec($ch2);
        // $http_status2  = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

        // // close the connection, release resources used
        // curl_close($ch2);

        $ch = curl_init($url_callback);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'Token:'.$client->key->authorization));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

        // execute!
        $response = curl_exec($ch);
        $http_status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $path_name = "response-send-callback-merchant-".date("Y-m-d");

        if (!file_exists('/var/www/html/fastpayments/logs/'.$path_name)) {
            mkdir('/var/www/html/fastpayments/logs/'.$path_name, 0777, true);
        }

        $resp = [
            "date_send" => date("Y-m-d H:i:s"),
            "http_status" => $http_status,
            "order_id" => $transaction->order_id,
            "user_id" => $transaction->user_id,
            "solicitation_date" => $transaction->solicitation_date,
            "paid_date" => $transaction->paid_date,
            "code_identify" => $transaction->code,
            "amount_solicitation" => $transaction->amount_solicitation,
            "amount_confirmed" => $transaction->amount_solicitation,
            "status" => $transaction->status,
            "comission" => $transaction->comission,
            "disponibilization_date" => $transaction->disponibilization_date,
            "response_merchant" => $response
        ];

        $FunctionsController->registerRecivedsRequests("/var/www/html/fastpayments/logs/".$path_name."/log.txt",json_encode($resp));

        // close the connection, release resources used
        curl_close($ch);

        if($http_status == "200"){
            DB::beginTransaction();
            try{

                $transaction->update([
                    "confirmation_callback" => "1"
                ]);

                DB::commit();

            }catch(exception $e){
                DB::rollback();
            }
        }



    }

    public function approve($ids){

        $list_ids = $ids;

        $FunctionsController = new FunctionsController();

        $transactions = Transactions::whereIn('id', $list_ids)->get();

        foreach($transactions as $transaction){

            $client = Clients::where("id",$transaction->client_id)->first();

            $valor_pago = $transaction->amount_solicitation;
            $final_amount = $transaction->amount_solicitation;

            // Calulo Taxas //
            $cot_ar = $FunctionsController->get_cotacao_dolar($transaction['client_id'],"deposit");
            $cotacao_dolar_markup = $cot_ar['markup'];
            $cotacao_dolar = $cot_ar['quote'];
            $spread_deposit = $cot_ar['spread'];

            if($cotacao_dolar_markup == $cotacao_dolar){

                $cotacao_dolar_markup = ($cotacao_dolar + ($cotacao_dolar * ($spread_deposit / 100)));

            }

            // Taxas
            $tax = $client->tax;

            if($client->currency == "brl"){

                $cotacao_dolar_markup = "1";
                $cotacao_dolar = "1";
                $spread_deposit = "0";

                $percent_fee = ($transaction->amount_solicitation * ($tax->pix_percent / 100));
                $fixed_fee = $tax->credit_card_absolute;
                if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                $comission = ($percent_fee + $fixed_fee);
                if($comission < $tax->min_fee_pix){ $comission = $tax->min_fee_pix; $min_fee = $tax->min_fee_pix; }else{ $min_fee = "NULL"; }

            }elseif($client->currency == "usd"){

                $final_amount = number_format(($transaction->amount_solicitation / $cotacao_dolar_markup),6,".","");
                $fixed_fee = number_format(($tax->credit_card_absolute),6,".","");
                $percent_fee = number_format(($final_amount * ($tax->pix_percent / 100)),6,".","");
                if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                $comission = ($percent_fee + $fixed_fee);
                if($comission < $tax->min_fee_pix){ $comission = $tax->min_fee_pix; $min_fee = $tax->min_fee_pix; }else{ $min_fee = "NULL"; }

            }

            DB::beginTransaction();
            try {

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
                        'fixed_fee' => $fixed_fee,
                        'percent_fee' => $percent_fee,
                        'comission' => $comission,
                        'paid_date' =>date('Y-m-d H:i:s'),
                        'final_date' =>date('Y-m-d H:i:s'),
                        'disponibilization_date' => date('Y-m-d 00:00:00',strtotime('+'.$day." days")),//setar a disponibilization date
                        'status' => 'confirmed',
                    ]);

                    DB::commit();

                    if($transaction->code_bank == "100"){
                        $url_callback = $client->key->url_callback_invoice;

                        if($transaction->confirmed_bank == NULL || $transaction->confirmed_bank == '0'){
                            $confirmed_bank_check = "0";
                        }else{
                            $confirmed_bank_check = "1";
                        }

                    }else{
                        $url_callback = $client->key->url_callback_shop;

                        $confirmed_bank_check = "none";
                    }

                    if($transaction->status == "confirmed"){
                        if($transaction->code_bank == "100"){
                            if($confirmed_bank_check == '0'){
                                // set post fields
                                $post = [
                                    "order_id" => $transaction->order_id,
                                    "user_id" => $transaction->user_id,
                                    "solicitation_date" => $transaction->solicitation_date,
                                    "paid_date" => $transaction->paid_date,
                                    "code_identify" => $transaction->code,
                                    "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                                    "amount_confirmed" => number_format($transaction->amount_confirmed,2,'.',''),
                                    "status" => $transaction->status,
                                    "stage" => "show_to_payment"
                                ];
                            }elseif($confirmed_bank_check == '1'){
                                // set post fields
                                $post = [
                                    "order_id" => $transaction->order_id,
                                    "user_id" => $transaction->user_id,
                                    "solicitation_date" => $transaction->solicitation_date,
                                    "paid_date" => $transaction->paid_date,
                                    "code_identify" => $transaction->code,
                                    "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                                    "amount_confirmed" => number_format($transaction->amount_confirmed,2,'.',''),
                                    "status" => $transaction->status,
                                    "stage" => "payment_done"
                                ];
                            }
                        }else{
                            // set post fields
                            $post = [
                                "order_id" => $transaction->order_id,
                                "user_id" => $transaction->user_id,
                                "solicitation_date" => $transaction->solicitation_date,
                                "paid_date" => $transaction->paid_date,
                                "code_identify" => $transaction->code,
                                "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                                "amount_confirmed" => number_format($transaction->amount_confirmed,2,'.',''),
                                "status" => $transaction->status,
                            ];
                        }
                    }elseif($transaction->status == "canceled"){
                        // set post fields
                        $post = [
                            "order_id" => $transaction->order_id,
                            "user_id" => $transaction->user_id,
                            "solicitation_date" => $transaction->solicitation_date,
                            "cancel_date" => $transaction->cancel_date,
                            "code_identify" => $transaction->code,
                            "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                            "status" => $transaction->status,
                        ];
                    }


                    $post_field = json_encode($post);

                    $ch2 = curl_init("https://webhook.site/b09d4325-a85d-40dc-8eed-71adfe15aa68");
                    curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "POST");
                    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch2, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'authorization:'.$client->key->authorization_deposit));
                    curl_setopt($ch2, CURLOPT_POSTFIELDS, $post_field);

                    // execute!
                    $response2 = curl_exec($ch2);
                    $http_status2  = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

                    // close the connection, release resources used
                    curl_close($ch2);

                    $ch = curl_init($url_callback);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'authorization:'.$client->key->authorization_deposit));
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

                    // execute!
                    $response = curl_exec($ch);
                    $http_status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                    $FunctionsController->registerRecivedsRequests("/var/www/html/fastpayments/logs/send-callback-approve-manual-admin.txt",json_encode($response));

                    // close the connection, release resources used
                    curl_close($ch);

                    if($http_status == "200"){
                        DB::beginTransaction();
                        try{

                            $transaction->update([
                                "confirmation_callback" => "1",
                                "confirmed_bank" => "1",
                            ]);

                            DB::commit();

                        }catch(exception $e){
                            DB::rollback();
                        }
                    }




            }catch (Exception $e) {
                DB::rollback();
                // return json_encode(array('status' => 'error', 'message' => 'Server Error'),true);

            }

        }

        return json_encode(array('status' => 'success', 'message' => 'Deposit Approved Successfully'),true);

    }

    public function sendCallbackInternal($transaction_id){

        $FunctionsController = new FunctionsController();

        $transaction = Transactions::where("id","=",$transaction_id)->get();

        foreach($transaction as $row){

            $client = Clients::where("id","=",$row['client_id'])->first();

            if($row['code_bank'] == "100"){
                $url_callback = $client->key->url_callback_invoice;

                if($row['confirmed_bank'] == NULL || $row['confirmed_bank'] == '0'){
                    $confirmed_bank_check = "0";
                }else{
                    $confirmed_bank_check = "1";
                }

            }else{
                $url_callback = $client->key->url_callback_shop;

                $confirmed_bank_check = "none";
            }


            if($row['status'] == "confirmed"){
                if($row['code_bank'] == "100"){
                    if($confirmed_bank_check == '0'){
                        // set post fields
                        $post = [
                            "order_id" => $row['order_id'],
                            "user_id" => $row['user_id'],
                            "solicitation_date" => $row['solicitation_date'],
                            "paid_date" => $row['paid_date'],
                            "code_identify" => $row['code'],
                            "amount_solicitation" => number_format($row['amount_solicitation'],2,'.',''),
                            "amount_confirmed" => number_format($row['amount_confirmed'],2,'.',''),
                            "status" => $row['status'],
                            "stage" => "show_to_payment"
                        ];
                    }elseif($confirmed_bank_check == '1'){
                        // set post fields
                        $post = [
                            "order_id" => $row['order_id'],
                            "user_id" => $row['user_id'],
                            "solicitation_date" => $row['solicitation_date'],
                            "paid_date" => $row['paid_date'],
                            "code_identify" => $row['code'],
                            "amount_solicitation" => number_format($row['amount_solicitation'],2,'.',''),
                            "amount_confirmed" => number_format($row['amount_confirmed'],2,'.',''),
                            "status" => $row['status'],
                            "stage" => "payment_done"
                        ];
                    }
                }else{
                    // set post fields
                    $post = [
                        "order_id" => $row['order_id'],
                        "user_id" => $row['user_id'],
                        "solicitation_date" => $row['solicitation_date'],
                        "paid_date" => $row['paid_date'],
                        "code_identify" => $row['code'],
                        "amount_solicitation" => number_format($row['amount_solicitation'],2,'.',''),
                        "amount_confirmed" => number_format($row['amount_confirmed'],2,'.',''),
                        "status" => $row['status'],
                    ];
                }
            }elseif($row['status'] == "canceled"){
                // set post fields
                $post = [
                    "order_id" => $row['order_id'],
                    "user_id" => $row['user_id'],
                    "solicitation_date" => $row['solicitation_date'],
                    "cancel_date" => $row['cancel_date'],
                    "code_identify" => $row['code'],
                    "amount_solicitation" => number_format($row['amount_solicitation'],2,'.',''),
                    "status" => $row['status'],
                ];
            }


            $post_field = json_encode($post);

            $ch = curl_init($url_callback);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'authorization:'.$client->key->authorization_deposit));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

            // execute!
            $response = curl_exec($ch);
            $http_status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            $FunctionsController->registerRecivedsRequests("/var/www/html/fastpayments/logs/send-callback-approve-manual-admin.txt",json_encode($response));

            // close the connection, release resources used
            curl_close($ch);

            if($http_status == "200"){
                DB::beginTransaction();
                try{

                    $row->update([
                        "confirmation_callback" => "1"
                    ]);

                    DB::commit();

                }catch(exception $e){
                    DB::rollback();
                }
            }

            $ch = curl_init("https://webhook.site/b09d4325-a85d-40dc-8eed-71adfe15aa68");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'authorization:'.$client->key->authorization_deposit));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

            // execute!
            $response = curl_exec($ch);
            $http_status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            // close the connection, release resources used
            curl_close($ch);

        }

    }
}
