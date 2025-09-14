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

class PerformWithdrawalPIXVoluti implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transaction_id;
    protected $bank_withdraw_id;
    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($transaction_id,$bank_withdraw_id)
    {
        //
        $this->transaction_id = $transaction_id;
        $this->bank_withdraw_id = $bank_withdraw_id;
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
        $bank = Banks::where("id",$this->bank_withdraw_id)->first();
        $client = Clients::where("id",$transaction->client_id)->first();
        $data_bank_account = DataAccountBank::where("id_bank",$bank->id)->first();

        $clientCode = $FunctionsAPIController->gera_pedido($transaction->user_id);

        $token = $bank->voluti_basic;

        $user_account_data = json_decode(base64_decode($transaction->user_account_data),true);

        $pix_key = $user_account_data['pix_key'];
        $type_pixkey = "";

        switch(strtolower($user_account_data['type_pixkey'])){
            case"telefone":
                $pix_key = str_replace("+55","",$pix_key);
                $pix_key = str_replace("(","",$pix_key);
                $pix_key = str_replace(")","",$pix_key);
                $pix_key = str_replace(" ","",$pix_key);
                $pix_key = str_replace("-","",$pix_key);
                $pix_key = "+55".$pix_key;
                $type_pixkey = "PHONE";
            break;
            case"cpf":
                $pix_key = str_replace(".","",$pix_key);
                $pix_key = str_replace("-","",$pix_key);
                $type_pixkey = "CPF";
            break;
            case"cnpj":
                $pix_key = str_replace(".","",$pix_key);
                $pix_key = str_replace("-","",$pix_key);
                $pix_key = str_replace("/","",$pix_key);
                $type_pixkey = "CNPJ";
            break;
            case"email":
                $pix_key = strtolower($pix_key);
                $type_pixkey = "EMAIL";
            break;
            case"aleatoria":
                $pix_key = $pix_key;
                $type_pixkey = "EVP";
            break;

        }

        $amount = number_format(floatval($transaction->amount_solicitation),2,".","") * 100;

        $data = [
            "pixKey" => [
                "key" => $pix_key,
                "type" => $type_pixkey
            ],
            "amount" => intval($amount),
            "description" => "",
            "postbackUrl" => "https://volutihook.fastpayments.com.br/api/volutihook"
        ];

        $path_name = "voluti-perform-withdraw-new-".date("Y-m-d");

        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
        }

        $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($data));

        $return_info = [
            "date_now" => date("Y-m-d H:i:s"),
            "bank_id" => $bank->id,
            "transaction_id" => $transaction->id,
            "order_id" => $transaction->order_id,
            "user_account_data" => $user_account_data,
            "token" => $token,
            "request" => $data
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://v-api.volutipay.com.br/v1/transactions/cashout',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data,true),
        CURLOPT_HTTPHEADER => array(
            'Authorization: '.$token,
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $get_response = json_decode($response,true);

        $data_response = [
            "response" => $get_response,
            "pix_key" => $pix_key,
            "account_data" => $user_account_data
        ];

        $path_name = "voluti-cashout-".date("Y-m-d");

        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
        }

        $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($data_response));

        if(isset($get_response['conciliationId'])){

            DB::beginTransaction();
            try{

                //Do update
                $transaction->update([
                    "payment_id" => $get_response['conciliationId']
                ]);

                DB::commit();

            }catch(Exception $e){
                DB::roolback();
            }

        }

    }
}
