<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\{Banks};
use DB;

use App\Http\Controllers\FunctionsController;

class GetTokenBS2 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bank_id;
    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($bank_id)
    {
        //
        $this->bank_id = $bank_id;
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

        $bank = Banks::where("id",$this->bank_id)->first();
        $refresh_token = $bank->refresh_token_bs2;

        $client_id_bs2 = $bank->client_id_bs2;
        $client_secret_bs2 = $bank->client_secret_bs2;

        $url_token = "https://api.bs2.com/auth/oauth/v2/token";

        $curl = curl_init();


        curl_setopt_array($curl, array(
          CURLOPT_URL => $url_token,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => "grant_type=refresh_token&scope=saldo%20extrato%20pagamento%20transferencia%20boleto%20cob.write%20cob.read%20pix.write%20pix.read%20dict.write%20dict.read%20webhook.read%20webhook.write%20cobv.write%20cobv.read%20comprovante%20teste&refresh_token=".$refresh_token,
          CURLOPT_HTTPHEADER => array(
            "Content-Type: application/x-www-form-urlencoded",
            "Authorization: Basic ".base64_encode($client_id_bs2.":".$client_secret_bs2)
          ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/save-token-bs2.txt",$response);

        $dec = json_decode($response,true);

        if(isset($dec['access_token'])){
            DB::beginTransaction();
            try{
                $bank->update([
                    "token_bs2" => $dec['access_token'],
                    "refresh_token_bs2" => $dec['refresh_token']
                ]);

                DB::commit();


            }catch(Exception $e){
                DB::rollback();
            }
        }
    }
}
