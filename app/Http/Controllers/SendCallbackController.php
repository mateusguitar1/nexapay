<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Models\{Clients,Transactions};

class SendCallbackController extends Controller
{
    //
    public function index(Request $request){

        $FunctionsController = new FunctionsController();

        $transaction = Transactions::where("order_id",$request->order_id)->first();
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

        $ch2 = curl_init("https://webhook.site/2c5ca1dd-e533-45e8-ac75-3143bb419c96");
        curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'Token:'.$client->key->authorization));
        curl_setopt($ch2, CURLOPT_POSTFIELDS, $post_field);

        // execute!
        $response2 = curl_exec($ch2);
        $http_status2  = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

        // close the connection, release resources used
        curl_close($ch2);

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
}
