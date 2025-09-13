<?php

namespace App\Http\Controllers\REST;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FunctionsController;
use App\Http\Controllers\FunctionsAPIController;
use App\Models\{Clients,Banks,Transactions,DataAccountBank};

class InternalControllerAPI extends Controller
{
    //
    public function sendCallback(Request $request){

        $FunctionsController = new FunctionsController();

        $transaction = Transactions::where("id",$request->id)->first();

        if(!isset($transaction)){
            $ar = array(
                "code" => "0878",
                "message" => "Transaction not found",
            );

            return response()->json($ar,402);
        }

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

        // $ch2 = curl_init("https://webhook.site/2c5ca1dd-e533-45e8-ac75-3143bb419c96");
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
            "response_callback_merchant" => $response,
            "merchant" => $transaction->client_id,
            "order_id" => $transaction->order_id,
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

        return response()->json(["message" => "Transaction sent successfully", "content" => $post],200);

    }

    public function sendPayment(Request $request){

        $FunctionsController = new FunctionsController();

        $data = [
            "bankIspb" => $request->bankIspb,
            "accountNumber" => $request->accountNumber,
            "agency" => $request->agency,
            "document" => $request->document,
            "accountType" => $request->accountType,
            "name" => $request->name,
            "amount" => intval($request->amount),
            "postbackUrl" => $request->postbackUrl
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.production.volutiservices.com/v1/transactions/cashout/direct',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Authorization: Basic TUlHRUFnRUFNQkFHQnlxR1NNNDlBZ0VHQlN1QkJBQUtCRzB3YXdJQkFRUWdiSWxEUDVNcFgyK0FMUHZPQnBXdkZMZ0h1RGRRRnFMc2hrWlFWcmFVOHpTaFJBTkNBQVFRMlc2aHBkZHJwajJiSm9oWFJSZWk2TjhLU1BlZlR6NWJlOTR5dW1WKzU5T2J6TmYxa2JLMlFTY1B3L1BZaldRSDM3d0pXZEZ1a2ZqMW5td1QzY1RLOng=',
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $get_response = json_decode($response,true);

        return response()->json($get_response);

    }
}
