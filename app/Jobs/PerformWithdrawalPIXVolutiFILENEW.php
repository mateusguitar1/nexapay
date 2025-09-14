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

class PerformWithdrawalPIXVolutiFILENEW implements ShouldQueue
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

        $user_account_data = json_decode(base64_decode($transaction->user_account_data),true);

        $pix_key = $user_account_data['pix_key'];
        $type_pix = strtolower($user_account_data['type_pixkey']);
        $type_pixkey = "";
        $new_pixkey = "";

        switch($type_pix){
            case"telefone":
                $new_pixkey = str_replace("+55","",$pix_key);
                $new_pixkey = str_replace("(","",$new_pixkey);
                $new_pixkey = str_replace(")","",$new_pixkey);
                $new_pixkey = str_replace(" ","",$new_pixkey);
                $new_pixkey = str_replace("-","",$new_pixkey);
                $new_pixkey = str_replace(".","",$new_pixkey);
                $new_pixkey = str_replace("+","",$new_pixkey);

                if(strlen($new_pixkey) > 11){
                    if(substr($new_pixkey,0,2) == "55"){
                        $new_pixkey = substr($new_pixkey,2,11);
                    }
                }

                $new_pixkey = "+55".$new_pixkey;
                $type_pixkey = "PHONE";
            break;
            case"phone":
                $new_pixkey = str_replace("(","",$pix_key);
                $new_pixkey = str_replace(")","",$new_pixkey);
                $new_pixkey = str_replace(" ","",$new_pixkey);
                $new_pixkey = str_replace("-","",$new_pixkey);
                $new_pixkey = str_replace(".","",$new_pixkey);
                $new_pixkey = str_replace("+","",$new_pixkey);

                if(strlen($new_pixkey) > 11){
                    if(substr($new_pixkey,0,2) == "55"){
                        $new_pixkey = substr($new_pixkey,2,11);
                    }
                }

                $new_pixkey = "+55".$new_pixkey;
                $type_pixkey = "PHONE";
            break;
            case"cpf":
                $new_pixkey = str_replace(".","",$pix_key);
                $new_pixkey = str_replace("-","",$new_pixkey);
                $type_pixkey = "CPF";
            break;
            case"cnpj":
                $new_pixkey = str_replace(".","",$pix_key);
                $new_pixkey = str_replace("-","",$new_pixkey);
                $new_pixkey = str_replace("/","",$new_pixkey);
                $type_pixkey = "CNPJ";
            break;
            case"email":
                $new_pixkey = strtolower($pix_key);
                $type_pixkey = "EMAIL";
            break;
            case"aleatoria":
                $new_pixkey = $pix_key;
                $type_pixkey = "EVP";
            break;
            case"random":
                $new_pixkey = $pix_key;
                $type_pixkey = "EVP";
            break;
            default:
                $new_pixkey = $pix_key;
                $type_pixkey = "EVP";
        }

        $amount = floatval($transaction->amount_solicitation);

        $params = [
            'voluti_clientid' => env('VOLUTI_CLIENTID_WITHDRAW'),
            'voluti_clientsecret' => env('VOLUTI_CLIENTSC_WITHDRAW')
        ];

        $getTokenVoluti = json_decode($FunctionsAPIController->getTokenVolutiWithdrawNew($params),true);
        // $getTokenVoluti = $FunctionsAPIController->getTokenVolutiWithdrawNew($params);

        $path_name = "volutinew-token-withdraw-".date("Y-m-d");

        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
        }

        $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($getTokenVoluti));

        $url = "https://accounts.voluti.com.br/api/v2/pix/payments/dict";

        if($type_pixkey == "phone" || $type_pixkey == "telefone"){
            if(substr($new_pixkey,0,2) == "55"){
                $new_pixkey = "+".$new_pixkey;
            }
        }

		$postData = [
			"pixKey" => $new_pixkey,
            "payment" => [
                "currency" => "BRL",
                "amount" => floatval($amount)
            ],
            //"creditorDocument" => $user_account_data['document'],
            "priority" => "NORM",
            "description" => "Pagamento numero ".$transaction->code,
            "expiration" => 600
		];

        $path_name = "volutinew-withdraw-data-send-".date("Y-m-d");

        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
        }

        $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($postData));

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-idempotency-key: '.$transaction->code,
			'Content-Type: application/json',
			'Accept: application/json',
            'Authorization: Bearer '.$getTokenVoluti['access_token']
		]);

        curl_setopt($ch, CURLOPT_SSLCERT, env('VOLUTI_CERT_WITHDRAW'));
        curl_setopt($ch, CURLOPT_SSLCERTPASSWD, env('VOLUTI_CERT_WITHDRAW_PASSPHRASE'));
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, env('VOLUTI_SSLKEYTYPE'));
        curl_setopt($ch, CURLOPT_SSLKEY, env('VOLUTI_KEY_WITHDRAW'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

		$response = curl_exec($ch);

		if (curl_errno($ch)) {

            $path_name = "volutinew-save-error-".date("Y-m-d");

            if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
            }

            $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode(["error" => print_r($response)]));

			// print_r(curl_error($ch));

            // $path_name = "volutinew-cashout-error-".date("Y-m-d");

            // if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
            //     mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
            // }

            // $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode(["error" => print_r(curl_error($ch))]));

            curl_close($ch);
            exit();
		}

		curl_close($ch);

        $get_response = json_decode($response,true);

        $data_response = [
            "response" => $get_response,
            "pix_key" => $pix_key,
            "account_data" => $user_account_data
        ];

        $path_name = "volutinew-cashout-".date("Y-m-d");

        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
        }

        $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($data_response));

        if(isset($get_response['endToEndId'])){

            DB::beginTransaction();
            try{

                //Do update
                $transaction->update([
                    "payment_id" => $get_response['endToEndId']
                ]);

                DB::commit();

            }catch(Exception $e){
                DB::roolback();
            }

        }

    }

}
