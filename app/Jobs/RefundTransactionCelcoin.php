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

class RefundTransactionCelcoin implements ShouldQueue
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

        $client_id_celcoin = $bank->client_id_celcoin;
        $client_secret_celcoin = $bank->client_secret_celcoin;
        $access_token_celcoin = $bank->access_token_celcoin;

        $params = [
            'client_id' => $transaction->client_id,
            'client_id_celcoin' => $client_id_celcoin,
            'client_secret_celcoin' => $client_secret_celcoin,
            'access_token_celcoin' => $access_token_celcoin
        ];

        $token_celcoin = json_decode($this->getTokenCELCOIN($params),true);
        $token = $token_celcoin['access_token'];

        $data_refund = [
            "transactionIdentification" => $transaction->id,
            "amount" => floatval($transaction->amount_solicitation),
            "reason" => "DVPT",
            "additionalInformation" => "Recebimento estornado devido a pagamento por outra titularidade"
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apicorp.celcoin.com.br/pix/v1/reverse/'.$transaction->deep_link,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data_refund),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$token
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $path_name = "celcoin-refund-pix-".date("Y-m-d");

        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
        }

        $data_request_auth = [
            "response" => json_decode($response,true),
            "created_at" => date("Y-m-d H:i:s")
        ];

        $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($data_request_auth));

        DB::beginTransaction();
        try{

            $refund_date = date("Y-m-d H:i:s");

            $transaction->update([
                "status" => "refund",
                "refund_date" => $refund_date,
                "final_date" => $refund_date
            ]);

            DB::commit();

        }catch(Exception $e){
            DB::rollBack();
        }


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


    }

    public function getTokenCELCOIN($params = array()){

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apicorp.celcoin.com.br/v5/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array('client_id' => $params['client_id_celcoin'],'grant_type' => 'client_credentials','client_secret' => $params['client_secret_celcoin']),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function getPixInfo($pix_key,$access_token_celcoin){

        $data = [
            "payerId" => env('COMPANY_DOCUMENT'),
            "key" => $pix_key
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apicorp.celcoin.com.br/pix/v1/dict/v2/key',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'accept: application/json',
            'Content-Type: application/json-patch+json',
            'Authorization: Bearer '.$access_token_celcoin
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }
}
