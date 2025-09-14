<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use DB;
use App\Models\{Transactions,User,Clients,Banks};

use App\Http\Controllers\FunctionsController;

class PerformWithdrawalPaymentFront implements ShouldQueue
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

        $FunctionsController = new FunctionsController();

        $transaction = Transactions::where("id",$this->transaction_id)->first();
        $bank = Banks::where("id",4)->first();
        $client = Clients::where("id",$transaction->client_id)->first();

        $url_callback = $client->key->url_callback;
        // $url_callback = 'https://webhook.site/011d5c4e-779b-406b-adc0-4542c524ff9d';

        $user_account_data = json_decode(base64_decode($transaction->user_account_data),true);

        print_r($user_account_data);
        exit();

        $data = [
            "order_id" => $transaction->order_id,
            "user_name" => $user_account_data['name'],
            "user_document" => $user_account_data['document'],
            "amount" => $transaction->amount_solicitation,
            "status" => $transaction->status,
        ];

        $post_field = json_encode($data,true);

        $ch = curl_init($url_callback);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'Token:'.$client->key->authorization_withdraw_a4p));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

        // execute!
        $response = curl_exec($ch);
        $http_status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // close the connection, release resources used
        curl_close($ch);

        // Taxas
        $tax = $client->tax;

        $cotacao_dolar_markup = "1";
        $cotacao_dolar = "1";
        $spread_deposit = "0";

        $final_amount = $transaction->amount_solicitation;
        $percent_fee = ($final_amount * ($tax->withdraw_pix_percent / 100));
        $fixed_fee = $tax->withdraw_pix_absolute;
        if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
        $comission = ($percent_fee + $fixed_fee);
        if($comission < $tax->min_fee_withdraw_pix){ $comission = $tax->min_fee_withdraw_pix; $min_fee = $tax->min_fee_withdraw_pix; }else{ $min_fee = "NULL"; }

        // if(isset($tk['access_token'])){

        //     $token_bs2 = $tk['access_token'];

        //     $getPayment = json_decode($this->CreateKeyPaymentId($data,$token_bs2),true);

        //     if(isset($getPayment['pagamentoId'])){

                $paymentId = rand(1000,9000);
                $recebedor = $user_account_data['name'];

                // $finish_payment = $this->ConfirmKeyPaymentId($paymentId,$data,$recebedor,$token_bs2);

                // if($finish_payment['message'] == "success"){

                    DB::beginTransaction();
                    try{

                    $date_confirm = date("Y-m-d H:i:s");

                    $transaction->update([
                        "status" => "confirmed",
                        "id_bank" => $bank->id,
                        "paid_date" => $date_confirm,
                        "final_date" => $date_confirm,
                        "payment_id" => $paymentId,
                        "data_bank" => ""
                    ]);

                    DB::commit();

                    }catch(Exception $e){
                    DB::rollback();
                    }

                    // set post fields
                    $post = [
                        "order_id" => $transaction->order_id,
                        "user_id" => $transaction->user_id,
                        "solicitation_date" => $transaction->solicitation_date,
                        "paid_date" => $date_confirm,
                        "code_identify" => $transaction->code,
                        "amount_solicitation" => $transaction->amount_solicitation,
                        "amount_confirmed" => $transaction->amount_solicitation,
                        "status" => "confirmed"
                    ];

                    $post_field = json_encode($post);

                    $ch = curl_init($url_callback);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'Token:'.$client->key->authorization_withdraw_a4p));
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

                    // execute!
                    $response = curl_exec($ch);
                    $http_status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                    // close the connection, release resources used
                    curl_close($ch);

                    $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/performwithdrawalpaymentKey.txt",json_encode($post));

                    $post_field = json_encode(["transaction_id" => $transaction->id]);

                    $ch = curl_init("http://18.224.111.184/fastpayments/public/api/approvecallback");
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field)));
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

                    // execute!
                    $response = curl_exec($ch);

                    curl_close($ch);

                    // $ch = curl_init("https://xdash.FastPayments.com/api/receiptWithdraw");
                    // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    // curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field)));
                    // curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

                    // // execute!
                    // $responsenew = curl_exec($ch);
                    // $http_status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                    // // close the connection, release resources used
                    // curl_close($ch);

                // }else{

                //     DB::beginTransaction();
                //     try{

                //         $description = "Unable to withdraw - ".$finish_payment['response'][0]['descricao']." - tag ".$finish_payment['response'][0]['tag'];

                //         $transaction->update([
                //             "status" => "canceled",
                //             "data_bank" => json_encode($getPayment),
                //             "reason_status" => $description
                //         ]);

                //         DB::commit();

                //     }catch(Exception $e){
                //         DB::rollback();
                //     }

                //     array_push($getPayment,$finish_payment);

                //     $curl = curl_init();

                //     curl_setopt_array($curl, array(
                //         CURLOPT_URL => $url_callback,
                //         CURLOPT_RETURNTRANSFER => true,
                //         CURLOPT_ENCODING => '',
                //         CURLOPT_MAXREDIRS => 10,
                //         CURLOPT_TIMEOUT => 0,
                //         CURLOPT_FOLLOWLOCATION => true,
                //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                //         CURLOPT_CUSTOMREQUEST => 'POST',
                //         CURLOPT_POSTFIELDS => json_encode($getPayment),
                //         CURLOPT_HTTPHEADER => array(
                //         'Content-Type: application/json',
                //         ),
                //     ));

                //     $response = curl_exec($curl);

                //     curl_close($curl);

                //     $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/performwithdrawalpaymentkey.txt",json_encode($getPayment));

                // }

            // }else{

            //     DB::beginTransaction();
            //     try{


            //         if($getPayment[0]['descricao'] == "003 - Chave DICT não encontrada"){
            //             $description = "003 - PIX key not found";
            //         }else{
            //             $description = $getPayment[0]['descricao'];
            //         }


            //         $transaction->update([
            //             "status" => "canceled",
            //             "data_bank" => json_encode($getPayment),
            //             "reason_status" => $description
            //         ]);

            //         DB::commit();

            //     }catch(Exception $e){
            //         DB::rollback();
            //     }

            //     $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/performwithdrawalpaymentkey.txt",json_encode(['message' => 'error paymentId','return' => $getPayment]));

            // }


        // }else{

        //     $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/performwithdrawalpaymentkey.txt",json_encode(['message' => 'error token','return' => $tk]));

        // }
    }

    public function CreateKeyPaymentId($data = array(),$token_bs2){

        $FunctionsController = new FunctionsController();

        $user_document = $data['user_document'];

        if (strpos($user_document, '@') !== false) {
            $type_document = "EMAIL";
            $valor = $user_document;
        }else{

            $count_tp_doc = strlen($user_document);
            if($count_tp_doc == 11){
                $type_document = "CPF";
                $valor = $user_document;
            }elseif($count_tp_doc == 13){
                $type_document = "PHONE";
                $valor = "+".$user_document;
            }elseif($count_tp_doc == 14){
                $type_document = "CNPJ";
                $valor = $user_document;
            }

        }

        $post = [
            "chave" => [
                "valor" => $valor,
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
        //    "campoLivre" => "Pagamento ORDER ID ".sanitizeString($data['order_id'])
           "campoLivre" => ""
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.bs2.com/pix/direto/forintegration/v1/pagamentos/'.$paymentId.'/confirmacao',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
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
           return ["message" => "error", "http_code" => $httpcode, "response" => json_decode($response,true)];
        }

   }

}
