<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\FunctionsAPIController;
use App\Models\{Transactions,User,Clients,Banks,Extract,Webhook};

class CheckWithdrawalPixCelcoin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checkwithdrawalpixcelcoin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $FunctionsAPIController = new FunctionsAPIController();

        $transactions = Transactions::where("type_transaction","withdraw")
        ->where("method_transaction","pix")
        ->where("status","pending")
        ->orderby("solicitation_date","DESC")
        ->get();

        $today = date("Y-m-d 00:00:00");

        foreach($transactions as $transaction){

            $checkPix = json_decode($this->checkPix($transaction->id),true);

            if(isset($checkPix['status'])){

                if($checkPix['status'] == "CONFIRMED"){

                    \App\Jobs\ConfirmPaymentPIXCelcoin::dispatch($transaction->id,$transaction->id_bank)->delay(now()->addSeconds('5'));

                    print_r("Check Transaction ID ".$transaction->id." \n");

                }else{
                    print_r("Check Transaction ID ".$transaction->id." return status: ".$checkPix['status']." \n");

                }

            }

        }
    }

    public function checkPix($transaction_id){

        $transaction = Transactions::where("id", $transaction_id)->first();
        $client = Clients::where("id","=",$transaction->client_id)->first();

        $client_id_celcoin = $client->bankWithdrawPix->client_id_celcoin;
        $client_secret_celcoin = $client->bankWithdrawPix->client_secret_celcoin;
        $access_token_celcoin = $client->bankWithdrawPix->access_token_celcoin;

        $params = [
            'client_id' => $client->id,
            'client_id_celcoin' => $client_id_celcoin,
            'client_secret_celcoin' => $client_secret_celcoin,
            'access_token_celcoin' => $access_token_celcoin
        ];

        $token_celcoin = json_decode($this->getTokenCELCOIN($params),true);

        $token = $token_celcoin['access_token'];

        $payment_id = $transaction->payment_id;

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apicorp.celcoin.com.br/pix/v1/payment/pi/status?transactionId='.$payment_id,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Authorization: Bearer '.$token
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

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
}
