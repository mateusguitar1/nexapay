<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use DB;
use App\Models\{Clients,Keys,Transactions,DataInvoice,Banks,Webhook,Extract};
use App\Http\Controllers\FunctionsController;

class CheckHookPIXLuxTakWithdrawNew implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $id_transaction;
    protected $id_webhook;
    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id_transaction,$id_webhook)
    {
        //
        $this->id_transaction = $id_transaction;
        $this->id_webhook = $id_webhook;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        $FunctionsController = new FunctionsController();

        $transaction = Transactions::where("id",$this->id_transaction)->where("status","!=","confirmed")->first();
        if($transaction){

            $bank = Banks::where("id",$transaction->id_bank)->first();
            $webhook = Webhook::where("id","=",$this->id_webhook)->first();

            $ck = json_decode($webhook['body'],true);
            $payment_id = $ck['custom_code'];

            $user_account_data = json_decode(base64_decode($transaction->user_account_data),true);

            if($payment_id == $transaction->code){

                $paid_date = date("Y-m-d H:i:s");
                $client = Clients::where("id","=",$transaction->client_id)->first();

                $date_disponibilization = date('Y-m-d 00:00:00');

                DB::beginTransaction();
                try{

                    //Do update
                    $transaction->update([
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
                        "final_amount" => ($transaction->amount_solicitation  * (-1)),
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

                    DB::commit();

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

                    $post_field = json_encode($post);

                    $ch = curl_init($client->key->url_callback);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'Token:'.$client->key->authorization));
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

                    // execute!
                    $response = curl_exec($ch);
                    $http_status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                    // close the connection, release resources used
                    curl_close($ch);

                    if($http_status == "200"){

                        $transaction->update([
                            "confirmation_callback" => "1"
                        ]);

                        DB::commit();

                    }

                }catch(Exception $e){
                    DB::roolback();
                }

            }

        }
    }
}
