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

class CheckHookPIXOPENPIXTED implements ShouldQueue
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

        $transaction = Transactions::where("id","=",$this->id_transaction)->where("status","!=","confirmed")->first();
        if($transaction){

            $bank = Banks::where("id",$transaction->id_bank)->first();
            $webhook = Webhook::where("id","=",$this->id_webhook)->first();

            $ck = json_decode($webhook['body'],true);
            $code = $ck['pix']['charge']['correlationID'];
            $EndToEndId = $ck['pix']['endToEndId'];

            if($code == $transaction->code && $EndToEndId != null){

                $paid_date = date("Y-m-d H:i:s");
                $clients = Clients::where("id","=",$transaction->client_id)->first();
                $days_safe_ted = $clients->days_safe_ted;

                $date_confirmed_bank = date("Y-m-d",strtotime("+".$days_safe_ted." days"))." 00:00:00";

                // Taxas
                $tax = $clients->tax;

                $cotacao_dolar_markup = "1";
                $cotacao_dolar = "1";
                $spread_deposit = "0";

                $description_deposit_text = "Depósito por TED QrCode";
                $description_comission_text = "Comissão sobre Depósito TED QrCode";
                $tax_percent = $clients->tax->replacement_percent;
                $tax_fixed = $clients->tax->replacement_absolute;
                $tax_min = $clients->tax->min_replacement;

                $final_amount = $transaction->amount_solicitation;
                $percent_fee = ($transaction->amount_solicitation * ($tax_percent / 100));
                $fixed_fee = $tax_fixed;
                if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                $comission = ($percent_fee + $fixed_fee);
                if($comission < $tax_min){ $comission = $tax_min; $min_fee = $tax_min; }else{ $min_fee = "0"; }

                $valor_pago = $transaction->amount_solicitation;

                $receita_comission = ($comission * $cotacao_dolar);
                $receita_spread_deposito = ($valor_pago / $cotacao_dolar - $final_amount) * $cotacao_dolar;

                DB::beginTransaction();
                try{

                    $transaction->update([
                        "status" => "confirmed",
                        "amount_confirmed" => $valor_pago,
                        "final_amount" => $final_amount,
                        "quote" => $cotacao_dolar,
                        "percent_markup" => $spread_deposit,
                        "quote_markup" => $cotacao_dolar_markup,
                        "fixed_fee" => $fixed_fee,
                        "percent_fee" => $percent_fee,
                        "comission" => $comission,
                        "id_bank" => $clients->bankPix->id,
                        "min_fee" => $min_fee,
                        "confirmed_bank" => "1",
                        "paid_date" => $paid_date,
                        "final_date" => $paid_date,
                        "disponibilization_date" => $date_confirmed_bank,
                        "receita_spread" => $receita_spread_deposito,
                        "receita_comission" => $receita_comission,
                    ]);

                    // // Deposit
                    Extract::create([
                        "transaction_id" => $transaction->id,
                        "order_id" => $transaction->order_id,
                        "client_id" => $transaction->client_id,
                        "user_id" => $transaction->user_id,
                        "bank_id" => $clients->bankPix->id,
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
                        "bank_id" => $clients->bankPix->id,
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
                    // }

                    if(in_array($clients->id,[11,27,28])){
                        // set post fields
                        $post = [
                            "id" => $transaction->id,
                            "fast_id" => $transaction->id,
                            "type_transaction" => $transaction->type_transaction,
                            "order_id" => $transaction->order_id,
                            "user_id" => $transaction->user_id,
                            "solicitation_date" => $transaction->solicitation_date,
                            "paid_date" => $paid_date,
                            "code_identify" => $transaction->code,
                            "provider_reference" => $transaction->id,
                            "amount_solicitation" => $transaction->amount_solicitation,
                            "amount_confirmed" => $transaction->amount_solicitation,
                            "status" => "confirmed",
                            "comission" => $comission,
                            "disponibilization_date" => $date_confirmed_bank,
                        ];
                    }else{
                        // set post fields
                        $post = [
                            "order_id" => $transaction->order_id,
                            "user_id" => $transaction->user_id,
                            "solicitation_date" => $transaction->solicitation_date,
                            "paid_date" => $paid_date,
                            "code_identify" => $transaction->code,
                            "provider_reference" => $transaction->id,
                            "amount_solicitation" => $transaction->amount_solicitation,
                            "amount_confirmed" => $transaction->amount_solicitation,
                            "status" => "confirmed",
                            "comission" => $comission,
                            "disponibilization_date" => $date_confirmed_bank,
                        ];
                    }

                    $post_field = json_encode($post);

                    $ch = curl_init($clients->key->url_callback);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'Token:'.$clients->key->authorization));
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

                    // execute!
                    $response = curl_exec($ch);
                    $http_status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                    // close the connection, release resources used
                    curl_close($ch);

                    $post_register = [
                        "date_send" => date("Y-m-d H:i:s"),
                        "http_status" => $http_status,
                        "order_id" => $transaction->order_id,
                        "user_id" => $transaction->user_id,
                        "solicitation_date" => $transaction->solicitation_date,
                        "paid_date" => $paid_date,
                        "code_identify" => $transaction->code,
                        "amount_solicitation" => $transaction->amount_solicitation,
                        "amount_confirmed" => $transaction->amount_solicitation,
                        "status" => "confirmed",
                        "comission" => $comission,
                        "disponibilization_date" => $date_confirmed_bank,
                    ];

                    $FunctionsController->registerRecivedsRequests("/var/www/html/fastpayments/logs/get_webhook_pix_job_openpix_ted.txt",json_encode($post_register));

                    if($http_status == "200"){

                        $transaction->update([
                            "confirmation_callback" => "1"
                        ]);

                        DB::commit();

                    }

                }catch(Exception $e){
                    DB::rollback();
                }

            }

        }
    }
}
