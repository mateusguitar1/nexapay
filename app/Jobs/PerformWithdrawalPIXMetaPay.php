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

class PerformWithdrawalPIXMetaPay implements ShouldQueue
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
            case"phone":
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
            case"random":
                $new_pixkey = $pix_key;
                $type_pixkey = "RANDOM";
            break;
            default:
                $new_pixkey = $pix_key;
                $type_pixkey = "RANDOM";
        }

        $amount = floatval($transaction->amount_solicitation);

        if($type_pixkey == "phone" || $type_pixkey == "telefone"){
            if(substr($new_pixkey,0,2) == "55"){
                $new_pixkey = "+".$new_pixkey;
                $type_pixkey = "PHONE";
            }
        }

        // if($user_account_data['name'] != "" || $user_account_data['name'] !== null){
        //     $user_name = $user_account_data['name'];
        // }else{
        //     $user_name = "NEXA PAY";
        // }

        $user_name = "NEXA PAY";

        // if($user_account_data['document'] != "" && $user_account_data['document'] !== null){
        //     $user_document = $user_account_data['document'];
        // }else{
        //     $user_document = "40521421012";
        // }

        $user_document = "92730030018";

		$postData = [
            "amount" => floatval($amount),
            "amount_cents" => intval($amount * 100),
            "external_id" => $transaction->code,
            "pix_key" => $new_pixkey,
            "key_type" => $type_pixkey,
            "description" => "",
            "callbackUrl" => "https://hooknexapay.financebaking.com/api/metapayhook"
		];

        $path_name = "metapay-withdraw-data-send-".date("Y-m-d");

        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
        }

        $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($postData));

        $access_token = $client->bankPix->paghiper_api;

        $url = "https://api.metabroker.finance/ellitium/withdraw";

		$curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$access_token
        ),
        ));

        $response = curl_exec($curl);

        $path_name = "metapay-response-raw-cashout-".date("Y-m-d");

        if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
            mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
        }

        $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",$response);

		if (curl_errno($curl)) {

            $path_name = "metapay-save-error-split-".date("Y-m-d");

            if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
            }

            $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode(["error" => print_r($response)]));

            curl_close($curl);
            exit();
		}else{

            $get_response = json_decode($response,true);

            $data_response = [
                "response" => $get_response,
                "account_data" => $user_account_data
            ];

            $path_name = "result-metapay-cashout-split-".date("Y-m-d");

            if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
            }

            $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($data_response));

            curl_close($curl);

        }

    }

    public function sign($params = array(),$merchantKey){

        ksort($params);

        $sign_string = '';

        foreach ($params as $key => $value){

            if (!empty($value)){
                $sign_string.= $key.'='.$value.'&';
            }
        }

        $sign_string = substr($sign_string, 0, -1);

        $sign = hash("sha256", $sign_string.$merchantKey);

        return $sign;
    }

}
