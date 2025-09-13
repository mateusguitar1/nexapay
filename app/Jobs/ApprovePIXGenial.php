<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\{Transactions,User,Clients,Banks,Extract};
use DB;

use App\Http\Controllers\FunctionsController;

class ApprovePIXGenial implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transaction_id;
    protected $id_setaccepted_ted_post;
    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($transaction_id,$id_setaccepted_ted_post)
    {
        //
        $this->transaction_id = $transaction_id;
        $this->id_setaccepted_ted_post = $id_setaccepted_ted_post;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $FunctionsController = new FunctionsController();

        $transaction = Transactions::where("id",$this->transaction_id)->where("status","pending")->first();

        $date = date("Y-m-d H:i:s");

        if($transaction){

            $payment_id = $transaction->payment_id;
            $client = $transaction->client;
            $bank = $transaction->bank;

            $url_callback = $client->key->url_callback_shop;

            $bank_name = $bank->bank_name;
            $holder = $bank->holder;
            $agency = $bank->agency;
            $type_account = $bank->type_account;
            $account = $bank->account;
            $document = $bank->document;

            $days_safe_pix = $client->days_safe_pix;
            
            $paid_date = date('Y-m-d H:i:s');
            $date_confirmed_bank = date("Y-m-d",strtotime(substr($paid_date,0,10)." +".$days_safe_pix." days"));
            $cancel_date = date('Y-m-d H:i:s');
            
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

            $receita_comission = ($comission * $cotacao_dolar);
            $receita_spread_deposito = ($valor_pago / $cotacao_dolar - $final_amount) * $cotacao_dolar;

            DB::beginTransaction();
            try{

                $transaction->update([
                    "status" => "confirmed",
                    "paid_date" => $date,
                    "final_date" => $date,
                    "disponibilization_date" => $date_confirmed_bank,
                    "amount_confirmed" => $valor_pago,
                    "final_amount" => $final_amount,
                    "quote" => $cotacao_dolar,
                    "percent_markup" => $spread_deposit,
                    "quote_markup" => $cotacao_dolar_markup,
                    "fixed_fee" => $fixed_fee,
                    "percent_fee" => $percent_fee,
                    "comission" => $comission,
                    "min_fee" => $min_fee,
                    "receita_spread" => $receita_spread_deposito,
                    "receita_comission" => $receita_comission,
                ]);

                // Deposit
                Extract::create([
                    "transaction_id" => $transaction->id,
                    "order_id" => $transaction->order_id,
                    "client_id" => $transaction->client_id,
                    "user_id" => $transaction->user_id,
                    "type_transaction_extract" => "cash-in",
                    "description_code" => "MD02",
                    "description_text" => "Depósito pro Pix",
                    "cash_flow" => $transaction->amount_solicitation,
                    "final_amount" => $transaction->final_amount,
                    "quote" => $transaction->quote,
                    "quote_markup" => $transaction->quote_markup,
                    "receita" => ((($transaction->amount_solicitation / $transaction->quote) - $transaction->final_amount) * $transaction->quote),
                    "disponibilization_date" => $transaction->disponibilization_date,
                ]);
                // Comission
                Extract::create([
                    "transaction_id" => $transaction->id,
                    "order_id" => $transaction->order_id,
                    "client_id" => $transaction->client_id,
                    "user_id" => $transaction->user_id,
                    "type_transaction_extract" => "cash-out",
                    "description_code" => "CM03",
                    "description_text" => "Comissão sobre Depósito de Pix",
                    "cash_flow" => $transaction->amount_solicitation,
                    "final_amount" => ($transaction->comission * (-1)),
                    "quote" => $transaction->quote,
                    "quote_markup" => $transaction->quote_markup,
                    "receita" => ($transaction->comission * $transaction->quote),
                    "disponibilization_date" => $transaction->disponibilization_date,
                ]);

                DB::commit();

                // set post fields
                $post = [
                    "order_id" => $transaction->order_id,
                    "user_id" => $transaction->user_id,
                    "solicitation_date" => $transaction->solicitation_date,
                    "paid_date" => $paid_date,
                    "code_identify" => $transaction->code,
                    "amount_solicitation" => $transaction->amount_solicitation,
                    "amount_confirmed" => $valor_pago,
                    "status" => "confirmed",
                    "comission" => $comission,
                    "disponibilization_date" => date("d/m/Y 00:00:00",strtotime($date_confirmed_bank)),
                ];

                $post_field = json_encode($post);

                $ch = curl_init($url_callback);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'authorization:'.$client->key->authorization_deposit));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

                // execute!
                $response = curl_exec($ch);
                $http_status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                // close the connection, release resources used
                curl_close($ch);

                /**
                 * Remove Id do extrato Genial
                 */

                $login_genial = $bank->login_genial;
                $pass_genial = $bank->pass_genial;

                // Post Estático
                $post = [
                    "authentication" => [
                        "User" => $login_genial,
                        "Password" => $pass_genial,
                        "Agency" => floatval($bank->agency),
                        "AccountNumber" => floatval($bank->account),
                        "CPF_CNPJ" => $bank->document
                    ],
                    "Id" => $this->id_setaccepted_ted_post,
                    "Type" => "PIX"
                ];

                $curl = curl_init();

                curl_setopt_array($curl, array(
                CURLOPT_URL => "https://pixlatam.bancoplural.com/api/BPO/v2/SetAcceptedTedPost",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($post),
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json",
                ),
                ));
        
                $response = curl_exec($curl);
        
                curl_close($curl);

            }catch(exception $e){
                DB::roolback();
            }

        }else{

            $post_var = json_encode($transaction);

            $fp = fopen('/var/www/all4paylite/logs/error-to-approve-pix-genial.txt', 'a');
            fwrite($fp, $post_var."\n");
            fclose($fp);

        }
    }
}
