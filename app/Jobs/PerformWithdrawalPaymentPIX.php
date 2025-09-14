<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\{Transactions,User,Clients,Banks};
use DB;

use App\Http\Controllers\FunctionsAPIController;

class PerformWithdrawalPaymentPIX implements ShouldQueue
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

        $user_account_data = json_decode(base64_decode($transaction->user_account_data),true);

        $data = [
            "value" => str_replace(".","",($transaction->amount_solicitation * 100)),
            "destinationAlias" => strtolower($user_account_data['pix_key']),
            "comment" => "Remessa ".$transaction->code,
            "correlationID" => $transaction->payment_id,
            "sourceAccountId" => "6234bbd784b9b1daf4d63e6a"
        ];

        // Acesso OpenPix
        $auth_openpix = $bank->auth_openpix;

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.openpix.com.br/api/v1/payment',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Authorization: '.$auth_openpix,
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function CreateKeyPaymentId($data = array(),$token_bs2){

        $FunctionsController = new FunctionsController();

        $count_tp_doc = strlen($data['user_document']);
        if($count_tp_doc <= 11){
            $type_document = "CPF";
        }else{
            $type_document = "CNPJ";
        }

        $post = [
            "chave" => [
                "valor" => $data['user_document'],
                "tipo" => $type_document
            ]
        ];

        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/performwithdrawalpaymentKey.txt",json_encode($post));

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.bs2.com/pix/direto/forintegration/v1/pagamentos/chave',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($post),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$token_bs2,
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function ConfirmKeyPaymentId($paymentId,$data = array(),$recebedor = array(),$token_bs2){

        $FunctionsController = new FunctionsController();

        $post = [
           "recebedor" => [
                 "ispb" => $recebedor['ispb'],
                 "conta" => [
                    "agencia" => $recebedor['conta']['agencia'],
                    "numero" => $recebedor['conta']['numero'],
                    "tipo" => $recebedor['conta']['tipo']
                 ],
                 "pessoa" => [
                    "documento" => $recebedor['pessoa']['documento'],
                    "tipoDocumento" => $recebedor['pessoa']['tipoDocumento'],
                    "nome" => $recebedor['pessoa']['nome'],
                    "nomeFantasia" => $recebedor['pessoa']['nomeFantasia'],
                 ],
           ],
           "valor" => floatval($data['amount']),
           "campoLivre" => "Pagamento ORDER ID ".$data['order_id']
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.bs2.com/pix/direto/forintegration/v1/pagamentos/'.$paymentId.'/confirmacao',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($post),
        CURLOPT_HTTPHEADER => array(
           'Content-Type: application/json',
           'Authorization: Bearer '.$token_bs2,
        ),
        ));

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if($httpcode == 202){
           return ["message" => "success", "http_code" => $httpcode];
        }else{
           return ["message" => "error", "http_code" => $httpcode];
        }

   }
}
