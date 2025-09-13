<?php

namespace App\Http\Controllers\REST;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FunctionsAPIController;
use App\Models\{Clients,Banks,Transactions,Extract,Logs,DataAccountBank};

class DemoControllerAPI extends Controller
{
    //

    public function index(Request $request){

        $FunctionsAPIController = new FunctionsAPIController();

        $data = [
            "order_id" => $request->order_id,
            "user_id" => $request->user_id,
            "user_document" => $request->user_document,
            "amount" => $FunctionsAPIController->strtodouble($request->amount),
            "method" => $request->method,
            "user_address" => $request->user_address,
            "user_district" => $request->user_district,
            "user_city" => $request->user_city,
            "user_uf" => $request->user_uf,
            "user_cep" => $request->user_cep
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://apiresthomolog.fastpayments.com.br/api/deposit",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Token: 64bc24366f1a50e903e08733794dea4ae50cfec5',
                'Accept: application/json',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $return = json_decode($response,true);

        return response()->json($return);

    }

    public function search(Request $request){

        $FunctionsAPIController = new FunctionsAPIController();

        $order_id =  $request->order_id;

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apiresthomolog.fastpayments.com.br/api/deposit?order_id='.$order_id,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Token: 64bc24366f1a50e903e08733794dea4ae50cfec5',
            'Accept: application/json'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $return = json_decode($response,true);

        return response()->json($return);

    }

    public function approveTransaction(Request $request){

        $FunctionsAPIController = new FunctionsAPIController();

        $id = $request->id_atual;
        $webhook = $request->webhook;

        $transaction = Transactions::where("id",$id)->first();

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

            DB::commit();

            if($webhook != ""){
                if($transaction->type_transaction == "deposit"){

                    if($transaction->status == "confirmed"){
                        if($transaction->code_bank == "100"){

                                // set post fields
                                $post = [
                                    "order_id" => $transaction->order_id,
                                    "user_id" => $transaction->user_id,
                                    "solicitation_date" => $transaction->solicitation_date,
                                    "paid_date" => $transaction->paid_date,
                                    "code_identify" => $transaction->code,
                                    "provider_reference" => $transaction->provider_reference,
                                    "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                                    "amount_confirmed" => number_format($transaction->final_amount,2,'.',''),
                                    "comission" => $transaction->comission,
                                    "status" => $transaction->status,
                                    "stage" => "payment_done"
                                ];

                        }else{
                            // set post fields
                            $post = [
                                "order_id" => $transaction->order_id,
                                "user_id" => $transaction->user_id,
                                "solicitation_date" => $transaction->solicitation_date,
                                "paid_date" => $transaction->paid_date,
                                "code_identify" => $transaction->code,
                                "provider_reference" => $transaction->provider_reference,
                                "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                                "amount_confirmed" => number_format($transaction->final_amount,2,'.',''),
                                "comission" => $transaction->comission,
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
                            "provider_reference" => $transaction->provider_reference,
                            "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                            "status" => $transaction->status,
                        ];
                    }elseif($transaction->status == "refund"){
                        // set post fields
                        $post = [
                            "order_id" => $transaction->order_id,
                            "user_id" => $transaction->user_id,
                            "solicitation_date" => $transaction->solicitation_date,
                            "refund_date" => $transaction->refund_date,
                            "code_identify" => $transaction->code,
                            "provider_reference" => $transaction->provider_reference,
                            "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                            "status" => $transaction->status,
                        ];
                    }

                }elseif($transaction->type_transaction == "withdraw"){

                    if($transaction->status == "confirmed"){

                        if($transaction->method_transaction == "TED"){

                            // set post fields
                            $post = [
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
                                "provider_reference" => $transaction->provider_reference,
                                "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                                "amount_confirmed" => number_format($transaction->final_amount,2,'.',''),
                                "comission" => $transaction->comission,
                                "status" => $transaction->status,
                            ];

                        }elseif($transaction->method_transaction == "pix"){

                            // set post fields
                            $post = [
                                "order_id" => $transaction->order_id,
                                "solicitation_date" => $transaction->solicitation_date,
                                "user_id" => $transaction->user_id,
                                "user_name" => $user_account_data['name'],
                                "user_document" => $user_account_data['document'],
                                "paid_date" => $transaction->paid_date,
                                "code_identify" => $transaction->code,
                                "provider_reference" => $transaction->provider_reference,
                                "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                                "amount_confirmed" => number_format($transaction->final_amount,2,'.',''),
                                "comission" => $transaction->comission,
                                "status" => $transaction->status,
                            ];

                        }


                    }elseif($transaction->status == "canceled"){

                        // set post fields
                        $post = [
                            "order_id" => $transaction->order_id,
                            "solicitation_date" => $transaction->solicitation_date,
                            "user_id" => $transaction->user_id,
                            "user_name" => $user_account_data['name'],
                            "user_document" => $user_account_data['document'],
                            "cancel_date" => $transaction->cancel_date,
                            "code_identify" => $transaction->code,
                            "provider_reference" => $transaction->provider_reference,
                            "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                            "status" => $transaction->status,
                        ];

                    }

                }

                $post_field = json_encode($post);

                $ch2 = curl_init($webhook);
                curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch2, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'Token:'.$client->key->authorization));
                curl_setopt($ch2, CURLOPT_POSTFIELDS, $post_field);

                // execute!
                $response2 = curl_exec($ch2);
                $http_status2  = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

                // close the connection, release resources used
                curl_close($ch2);
            }

            return response()->json(['status' => 'success', 'message' => 'Transaction approved successfully']);

        }catch (Exception $e) {
            DB::rollback();

            return response()->json(['status' => 'error', 'message' => 'Server Error']);

        }

    }

    public function cancelTransaction(Request $request){

        $FunctionsAPIController = new FunctionsAPIController();

        $id = $request->id_atual;
        $webhook = $request->webhook;

        $transaction = Transactions::where("id",$id)->first();

        $client = Clients::where("id",$transaction->client_id)->first();

        DB::beginTransaction();
        try {

            //Do update
            $transaction->update([
                'cancel_date' =>date('Y-m-d H:i:s'),
                'final_date' =>date('Y-m-d H:i:s'),
                'status' => 'canceled',
            ]);

            DB::commit();

            if($webhook != ""){
                if($transaction->type_transaction == "deposit"){

                    if($transaction->status == "confirmed"){
                        if($transaction->code_bank == "100"){

                                // set post fields
                                $post = [
                                    "order_id" => $transaction->order_id,
                                    "user_id" => $transaction->user_id,
                                    "solicitation_date" => $transaction->solicitation_date,
                                    "paid_date" => $transaction->paid_date,
                                    "code_identify" => $transaction->code,
                                    "provider_reference" => $transaction->provider_reference,
                                    "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                                    "amount_confirmed" => number_format($transaction->final_amount,2,'.',''),
                                    "comission" => $transaction->comission,
                                    "status" => $transaction->status,
                                    "stage" => "payment_done"
                                ];

                        }else{
                            // set post fields
                            $post = [
                                "order_id" => $transaction->order_id,
                                "user_id" => $transaction->user_id,
                                "solicitation_date" => $transaction->solicitation_date,
                                "paid_date" => $transaction->paid_date,
                                "code_identify" => $transaction->code,
                                "provider_reference" => $transaction->provider_reference,
                                "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                                "amount_confirmed" => number_format($transaction->final_amount,2,'.',''),
                                "comission" => $transaction->comission,
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
                            "provider_reference" => $transaction->provider_reference,
                            "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                            "status" => $transaction->status,
                        ];
                    }elseif($transaction->status == "refund"){
                        // set post fields
                        $post = [
                            "order_id" => $transaction->order_id,
                            "user_id" => $transaction->user_id,
                            "solicitation_date" => $transaction->solicitation_date,
                            "refund_date" => $transaction->refund_date,
                            "code_identify" => $transaction->code,
                            "provider_reference" => $transaction->provider_reference,
                            "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                            "status" => $transaction->status,
                        ];
                    }

                }elseif($transaction->type_transaction == "withdraw"){

                    if($transaction->status == "confirmed"){

                        if($transaction->method_transaction == "TED"){

                            // set post fields
                            $post = [
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
                                "provider_reference" => $transaction->provider_reference,
                                "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                                "amount_confirmed" => number_format($transaction->final_amount,2,'.',''),
                                "comission" => $transaction->comission,
                                "status" => $transaction->status,
                            ];

                        }elseif($transaction->method_transaction == "pix"){

                            // set post fields
                            $post = [
                                "order_id" => $transaction->order_id,
                                "solicitation_date" => $transaction->solicitation_date,
                                "user_id" => $transaction->user_id,
                                "user_name" => $user_account_data['name'],
                                "user_document" => $user_account_data['document'],
                                "paid_date" => $transaction->paid_date,
                                "code_identify" => $transaction->code,
                                "provider_reference" => $transaction->provider_reference,
                                "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                                "amount_confirmed" => number_format($transaction->final_amount,2,'.',''),
                                "comission" => $transaction->comission,
                                "status" => $transaction->status,
                            ];

                        }


                    }elseif($transaction->status == "canceled"){

                        // set post fields
                        $post = [
                            "order_id" => $transaction->order_id,
                            "solicitation_date" => $transaction->solicitation_date,
                            "user_id" => $transaction->user_id,
                            "user_name" => $user_account_data['name'],
                            "user_document" => $user_account_data['document'],
                            "cancel_date" => $transaction->cancel_date,
                            "code_identify" => $transaction->code,
                            "provider_reference" => $transaction->provider_reference,
                            "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                            "status" => $transaction->status,
                        ];

                    }

                }

                $post_field = json_encode($post);

                $ch2 = curl_init($webhook);
                curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch2, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'Token:'.$client->key->authorization));
                curl_setopt($ch2, CURLOPT_POSTFIELDS, $post_field);

                // execute!
                $response2 = curl_exec($ch2);
                $http_status2  = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

                // close the connection, release resources used
                curl_close($ch2);
            }

            return response()->json(['status' => 'success', 'message' => 'Transaction canceled successfully']);

        }catch (Exception $e) {
            DB::rollback();

            return response()->json(['status' => 'error', 'message' => 'Server Error']);

        }

    }

    public function sendTransaction(Request $request){

        $FunctionsAPIController = new FunctionsAPIController();

        $id = $request->id_atual;
        $webhook = $request->webhook;

        $transaction = Transactions::where("id",$id)->first();

        $client = Clients::where("id",$transaction->client_id)->first();


        if($webhook != ""){
            if($transaction->type_transaction == "deposit"){

                if($transaction->status == "confirmed"){
                    if($transaction->code_bank == "100"){

                            // set post fields
                            $post = [
                                "order_id" => $transaction->order_id,
                                "user_id" => $transaction->user_id,
                                "solicitation_date" => $transaction->solicitation_date,
                                "paid_date" => $transaction->paid_date,
                                "code_identify" => $transaction->code,
                                "provider_reference" => $transaction->provider_reference,
                                "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                                "amount_confirmed" => number_format($transaction->final_amount,2,'.',''),
                                "comission" => $transaction->comission,
                                "status" => $transaction->status,
                                "stage" => "payment_done"
                            ];

                    }else{
                        // set post fields
                        $post = [
                            "order_id" => $transaction->order_id,
                            "user_id" => $transaction->user_id,
                            "solicitation_date" => $transaction->solicitation_date,
                            "paid_date" => $transaction->paid_date,
                            "code_identify" => $transaction->code,
                            "provider_reference" => $transaction->provider_reference,
                            "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                            "amount_confirmed" => number_format($transaction->final_amount,2,'.',''),
                            "comission" => $transaction->comission,
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
                        "provider_reference" => $transaction->provider_reference,
                        "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                        "status" => $transaction->status,
                    ];
                }elseif($transaction->status == "refund"){
                    // set post fields
                    $post = [
                        "order_id" => $transaction->order_id,
                        "user_id" => $transaction->user_id,
                        "solicitation_date" => $transaction->solicitation_date,
                        "refund_date" => $transaction->refund_date,
                        "code_identify" => $transaction->code,
                        "provider_reference" => $transaction->provider_reference,
                        "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                        "status" => $transaction->status,
                    ];
                }

            }elseif($transaction->type_transaction == "withdraw"){

                if($transaction->status == "confirmed"){

                    if($transaction->method_transaction == "TED"){

                        // set post fields
                        $post = [
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
                            "provider_reference" => $transaction->provider_reference,
                            "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                            "amount_confirmed" => number_format($transaction->final_amount,2,'.',''),
                            "comission" => $transaction->comission,
                            "status" => $transaction->status,
                        ];

                    }elseif($transaction->method_transaction == "pix"){

                        // set post fields
                        $post = [
                            "order_id" => $transaction->order_id,
                            "solicitation_date" => $transaction->solicitation_date,
                            "user_id" => $transaction->user_id,
                            "user_name" => $user_account_data['name'],
                            "user_document" => $user_account_data['document'],
                            "paid_date" => $transaction->paid_date,
                            "code_identify" => $transaction->code,
                            "provider_reference" => $transaction->provider_reference,
                            "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                            "amount_confirmed" => number_format($transaction->final_amount,2,'.',''),
                            "comission" => $transaction->comission,
                            "status" => $transaction->status,
                        ];

                    }


                }elseif($transaction->status == "canceled"){

                    // set post fields
                    $post = [
                        "order_id" => $transaction->order_id,
                        "solicitation_date" => $transaction->solicitation_date,
                        "user_id" => $transaction->user_id,
                        "user_name" => $user_account_data['name'],
                        "user_document" => $user_account_data['document'],
                        "cancel_date" => $transaction->cancel_date,
                        "code_identify" => $transaction->code,
                        "provider_reference" => $transaction->provider_reference,
                        "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                        "status" => $transaction->status,
                    ];

                }

            }

            $post_field = json_encode($post);

            $ch2 = curl_init($webhook);
            curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'Token:'.$client->key->authorization));
            curl_setopt($ch2, CURLOPT_POSTFIELDS, $post_field);

            // execute!
            $response2 = curl_exec($ch2);
            $http_status2  = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

            // close the connection, release resources used
            curl_close($ch2);

            if($http_status2 == "200"){
                return response()->json(['status' => 'success', 'message' => 'Webhook send successfully']);
            }else{
                return response()->json(['status' => 'error', 'message' => 'Error on send webhook url']);
            }

        }else{
            return response()->json(['status' => 'error', 'message' => 'Empty webhook url']);
        }

    }
}
