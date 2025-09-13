<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\{Transactions,User,Clients,Banks,DataAccountBank,ReceiptCelcoin,Extract};
use DB;

use App\Http\Controllers\FunctionsAPIController;

class ConfirmPaymentVolutiNew implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transaction_id;
    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($transaction_id)
    {
        //
        $this->transaction_id = $transaction_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        $FunctionsAPIController = new FunctionsAPIController();

        $transaction = Transactions::where("id",$this->transaction_id)->first();
        $bank = Banks::where("id",$transaction->id_bank)->first();
        $client = Clients::where("id",$transaction->client_id)->first();
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

        }catch(Exception $e){
            DB::roolback();
        }
    }
}
