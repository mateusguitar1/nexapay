<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use DB;
use App\Models\{Transactions,User,Clients,Banks,DataAccountBank,ReceiptCelcoin,Extract};

use App\Http\Controllers\FunctionsAPIController;

class CancelDepositPendingShipay implements ShouldQueue
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
        $bank = $transaction->bank;
        $client = $transaction->client;

        $response_token = json_decode($FunctionsAPIController->getTokenShipay($bank->shipay_client_id,$bank->shipay_access_key,$bank->shipay_secret_key),true);
        $token = $response_token['access_token'];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.shipay.com.br/order/'.$transaction->payment_id,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer '.$token
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $path_name = "cancel-deposit-pending-shipay-".date("Y-m-d");

        if (!file_exists('/var/www/html/fastpayments/logs/'.$path_name)) {
            mkdir('/var/www/html/fastpayments/logs/'.$path_name, 0777, true);
        }

        $FunctionsAPIController->registerRecivedsRequests("/var/www/html/fastpayments/logs/".$path_name."/log.txt",$response);

        return $response;

    }
}
