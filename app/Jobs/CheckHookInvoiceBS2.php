<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\{Clients,Keys,Transactions,DataInvoice,Banks,Webhook,BanksInvoicePayment,BankClientsAccount,Extract};
use DB;
use App\Http\Controllers\FunctionsController;

class CheckHookInvoiceBS2 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $id_hook;
    protected $id_client;
    protected $id_order;
    public $tries = 1;

    public function __construct($id_hook,$id_client,$id_order)
    {
        //
        $this->id_hook = $id_hook;
        $this->id_client = $id_client;
        $this->id_order = $id_order;
    }


    public function handle()
    {
        //
        $FunctionsController = new FunctionsController();

        $webhook = Webhook::where("id","=",$this->id_hook)
            ->where("client_id","=",$this->id_client)
            ->where("order_id","=",$this->id_order)
            ->first();

        $data_save = [
            "id_hook" => $this->id_hook,
            "id_client" => $this->id_client,
            "id_order" => $this->id_order,
            "webhook_return" => $webhook
        ];

        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/get_webhook_job.txt",json_encode($data_save));

        $row = Transactions::where("client_id","=",$this->id_client)
            ->where("order_id","=",$this->id_order)
            ->first();

        if(!empty($webhook)){

            $payload = json_decode($webhook->body,true);

            // Take situacao boleto
            $status_bs2 = $payload['Situacao'];

            if(isset($payload['BancoPagamento'])){
                if($payload['BancoPagamento'] != null){

                    $code_bank_check = $payload['BancoPagamento'];

                    if(strlen($code_bank_check) == "2"){
                        $code_bank_check = "0".$code_bank_check;
                    }elseif(strlen($code_bank_check) == "1"){
                        $code_bank_check = "00".$code_bank_check;
                    }

                    $bankclientsaccount = BankClientsAccount::where("code","=",$code_bank_check)->first();
                    if(isset($bankclientsaccount->name)){
                        $name_bank = $bankclientsaccount->name;
                    }else{
                        $name_bank = "---";
                    }

                    $b1_status = "no";

                    foreach($payload['StatusBoleto'] as $indice => $content){
                        if($content['Status'] == "2"){
                            $dt_status = strtotime(substr($content['DataStatus'],0,10));
                            $dt_request = strtotime(substr($row->solicitation_date,0,10));

                            if($dt_status == $dt_request){
                                $b1_status = "yes";
                            }

                        }
                    }

                    $banksinvoicepayment = BanksInvoicePayment::where("id_transaction","=",$row->id)->first();

                    if(!isset($banksinvoicepayment)){
                        DB::beginTransaction();
                        try{

                            BanksInvoicePayment::create([
                                "id_transaction" => $row->id,
                                "client_id" => $row->client_id,
                                "user_id" => $row->user_id,
                                "bank_code" => $code_bank_check,
                                "bank_name" => $name_bank,
                                "solicitation_date" => $row->solicitation_date,
                                "notification_date" => date("Y-m-d H:i:s",strtotime(substr($payload['DataNotificacao'],0,19))),
                                "b1_identify" => $b1_status,
                                "body" => $webhook->body,
                            ]);

                            DB::commit();

                        }catch(Exception $e){
                            DB::rollback();
                        }
                    }

                }
            }

            // situacao 1 = Boleto em aberto - A vencer
            // situacao 2 = Boleto em aberto - Vencido
            // situacao 3 = Boleto Cancelado - Baixado
            // situacao 4 = Boleto Liquidado - Compensado

            switch($status_bs2){
                case"1": $status_boleto = "Boleto em aberto - A vencer"; break;
                case"2": $status_boleto = "Boleto em aberto - Vencido"; break;
                case"3": $status_boleto = "Boleto Cancelado - Baixado"; break;
                case"4": $status_boleto = "Boleto Liquidado - Compensado"; break;
            }

            $client = Clients::where("id","=",$row->client_id)->first();
            $tax = $client->tax;
            $tax_cancel_boleto = $tax->boleto_cancel;

            // Calulo Taxas //
            $cot_ar = $FunctionsController->get_cotacao_dolar($client->id,"deposit");
            $cotacao_dolar_markup = $cot_ar['markup'];
            $cotacao_dolar = $cot_ar['quote'];
            $spread_deposit = $cot_ar['spread'];

            // Taxas
            $tax = $client->tax;

            if($client->currency == "brl"){

                $cotacao_dolar_markup = "1";
                $cotacao_dolar = "1";
                $spread_deposit = "0";

                $final_amount = $row->amount_solicitation;
                $percent_fee = ($final_amount * ($tax->boleto_percent / 100));
                $fixed_fee = $tax->boleto_absolute;
                if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                $comission = ($percent_fee + $fixed_fee);
                if($comission < $tax->min_fee_boleto){ $comission = $tax->min_fee_boleto; $min_fee = $tax->min_fee_boleto; }else{ $min_fee = "NULL"; }

            }elseif($client->currency == "usd"){

                $final_amount = number_format(($row->amount_solicitation / $cotacao_dolar_markup),6,".","");
                $fixed_fee = number_format(($tax->boleto_absolute),6,".","");
                $percent_fee = number_format(($final_amount * ($tax->boleto_percent / 100)),6,".","");
                if(!is_numeric($fixed_fee)){ $fixed_fee = 0; }
                $comission = ($percent_fee + $fixed_fee);
                if($comission < $tax->min_fee_boleto){ $comission = $tax->min_fee_boleto; $min_fee = $tax->min_fee_boleto; }else{ $min_fee = "NULL"; }

            }

            if(is_numeric($client->key->minamount_boletofirst)){
                $minamount = $client->key->minamount_boletofirst;
            }else{
                $minamount = 0;
            }

            $valor_pago = $row->amount_solicitation;

            $receita_comission = ($comission * $cotacao_dolar);
            $receita_spread_deposito = ($valor_pago / $cotacao_dolar - $final_amount) * $cotacao_dolar;

            if($client->key->boletofirst_method == "enable"){

                if($row->amount_solicitation >= $minamount){
                    // If paid
                    if($status_bs2 == "4"){

                        $paid_date = date("Y-m-d H:i:s");

                        $days_safe_boleto = $client->days_safe_boleto;

                        switch($FunctionsController->dia_semana(date("Y-m-d"))){
                            case"sex": $days_safe_boleto = ($days_safe_boleto + 2); break;
                            case"sab": $days_safe_boleto = ($days_safe_boleto + 1); break;
                            case"dom": $days_safe_boleto = ($days_safe_boleto + 1); break;
                        }

                        $date_confirmed_bank = date("Y-m-d",strtotime("+".$days_safe_boleto." days"))." 00:00:00";

                        $verify = array(
                            "status" => $row['status'],
                            "confirmed_bank" => $row['confirmed_bank'],
                            "disponibilization_date" => date("d/m/Y 00:00:00",strtotime($date_confirmed_bank)),
                            "valor_pago" => $valor_pago,
                            "status_bs2" => $status_bs2,
                            "check" => "webhook"
                        );

                        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/etapa-bs2.txt",json_encode($verify));

                        if($row['confirmed_bank'] == NULL || $row['confirmed_bank'] == '0'){
                            $confirmed_bank_check = "0";
                        }else{
                            $confirmed_bank_check = "1";
                        }

                        if($row['status'] == "confirmed" && $confirmed_bank_check == '0'){

                            DB::beginTransaction();
                            try{

                                $row->update([
                                    "confirmed_bank" => "1",
                                    "disponibilization_date" => $date_confirmed_bank,
                                    "fixed_fee" => $fixed_fee,
                                    "percent_fee" => $percent_fee,
                                    "comission" => $comission,
                                    "quote" => $cotacao_dolar,
                                    "percent_markup" => $spread_deposit,
                                    "quote_markup" => $cotacao_dolar_markup,
                                    "receita_spread" => $receita_spread_deposito,
                                    "receita_comission" => $receita_comission,
                                    "receita_spread" => $receita_spread_deposito,
                                    "receita_comission" => $receita_comission,
                                ]);

                                $webhook->update([
                                    "type_register" => "checked"
                                ]);


                                // Deposit
                                Extract::create([
                                    "transaction_id" => $row->id,
                                    "order_id" => $row->order_id,
                                    "client_id" => $row->client_id,
                                    "user_id" => $row->user_id,
                                    "type_transaction_extract" => "cash-in",
                                    "description_code" => "MD01",
                                    "description_text" => "Depósito de Boleto",
                                    "cash_flow" => $row->amount_solicitation,
                                    "final_amount" => $row->final_amount,
                                    "quote" => $row->quote,
                                    "quote_markup" => $row->quote_markup,
                                    "receita" => ((($row->amount_solicitation / $row->quote) - $row->final_amount) * $row->quote),
                                    "disponibilization_date" => $row->disponibilization_date,
                                ]);
                                // Comission
                                Extract::create([
                                    "transaction_id" => $row->id,
                                    "order_id" => $row->order_id,
                                    "client_id" => $row->client_id,
                                    "user_id" => $row->user_id,
                                    "type_transaction_extract" => "cash-out",
                                    "description_code" => "CM01",
                                    "description_text" => "Comissão sobre Depósito de Boleto",
                                    "cash_flow" => $row->amount_solicitation,
                                    "final_amount" => ($row->comission * (-1)),
                                    "quote" => $row->quote,
                                    "quote_markup" => $row->quote_markup,
                                    "receita" => ($row->comission * $row->quote),
                                    "disponibilization_date" => $row->disponibilization_date,
                                ]);

                                DB::commit();

                                // if($client->id != "177"){

                                    // set post fields
                                    $post = [
                                        "order_id" => $row['order_id'],
                                        "user_id" => $row['user_id'],
                                        "solicitation_date" => $row['solicitation_date'],
                                        "paid_date" => $paid_date,
                                        "code_identify" => $row['code'],
                                        "amount_solicitation" => $row['amount_solicitation'],
                                        "amount_confirmed" => $row['amount_solicitation'],
                                        "status" => "confirmed",
                                        "stage" => "payment_done",
                                        "comission" => $comission,
                                        "disponibilization_date" => $row['disponibilization_date']
                                    ];

                                    $post_field = json_encode($post);

                                    $ch = curl_init($client->key->url_callback_invoice);
                                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'authorization:'.$client->key->authorization_deposit));
                                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

                                    // execute!
                                    $response = curl_exec($ch);
                                    $http_status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                                    // close the connection, release resources used
                                    curl_close($ch);

                                // }else{
                                //     $http_status == "200";
                                // }

                                if($http_status == "200"){

                                    $row->update([
                                        "confirmation_callback" => "1"
                                    ]);

                                    DB::commit();

                                }

                                $post_var = [
                                    "date_send_callback" => date("Y-m-d H:i:s"),
                                    "type" => "bs2boleto",
                                    "order_id" => $row['order_id'],
                                    "user_id" => $row['user_id'],
                                    "solicitation_date" => $row['solicitation_date'],
                                    "paid_date" => $paid_date,
                                    "code_identify" => $row['code'],
                                    "amount_solicitation" => $row['amount_solicitation'],
                                    "amount_confirmed" => $row['amount_solicitation'],
                                    "status" => "confirmed",
                                    "comission" => $comission,
                                    "disponibilization_date" => $row['disponibilization_date'],
                                    "response_http_server" => $http_status,
                                    "response_server" => $response
                                ];

                                $post_var = json_encode($post_var);

                                $fp = fopen('/var/www/html/nexapay/logs/send-callback-bs2boleto.txt', 'a');
                                fwrite($fp, $post_var."\n");
                                fclose($fp);

                            }catch(exception $e){
                                DB::rollback();
                            }

                        }elseif($row['status'] == "pending" && $confirmed_bank_check == '0' || $row['status'] == "canceled" && $confirmed_bank_check == '0'){

                            DB::beginTransaction();
                            try{

                                $row->update([
                                    "status" => "confirmed",
                                    "amount_confirmed" => $row['amount_solicitation'],
                                    "final_amount" => $final_amount,
                                    "quote" => $cotacao_dolar,
                                    "percent_markup" => $spread_deposit,
                                    "quote_markup" => $cotacao_dolar_markup,
                                    "fixed_fee" => $fixed_fee,
                                    "percent_fee" => $percent_fee,
                                    "comission" => $comission,
                                    "min_fee" => $min_fee,
                                    "confirmed_bank" => "1",
                                    "paid_date" => $paid_date,
                                    "final_date" => $paid_date,
                                    "disponibilization_date" => $date_confirmed_bank,
                                    "receita_spread" => $receita_spread_deposito,
                                    "receita_comission" => $receita_comission,
                                ]);

                                $webhook->update([
                                    "type_register" => "checked"
                                ]);

                                // Deposit
                                Extract::create([
                                    "transaction_id" => $row->id,
                                    "order_id" => $row->order_id,
                                    "client_id" => $row->client_id,
                                    "user_id" => $row->user_id,
                                    "type_transaction_extract" => "cash-in",
                                    "description_code" => "MD01",
                                    "description_text" => "Depósito de Boleto",
                                    "cash_flow" => $row->amount_solicitation,
                                    "final_amount" => $row->final_amount,
                                    "quote" => $row->quote,
                                    "quote_markup" => $row->quote_markup,
                                    "receita" => ((($row->amount_solicitation / $row->quote) - $row->final_amount) * $row->quote),
                                    "disponibilization_date" => $row->disponibilization_date,
                                ]);
                                // Comission
                                Extract::create([
                                    "transaction_id" => $row->id,
                                    "order_id" => $row->order_id,
                                    "client_id" => $row->client_id,
                                    "user_id" => $row->user_id,
                                    "type_transaction_extract" => "cash-out",
                                    "description_code" => "CM01",
                                    "description_text" => "Comissão sobre Depósito de Boleto",
                                    "cash_flow" => $row->amount_solicitation,
                                    "final_amount" => ($row->comission * (-1)),
                                    "quote" => $row->quote,
                                    "quote_markup" => $row->quote_markup,
                                    "receita" => ($row->comission * $row->quote),
                                    "disponibilization_date" => $row->disponibilization_date,
                                ]);

                                DB::commit();
                                // // if($client->id != "177"){

                                    // set post fields
                                    $post = [
                                        "order_id" => $row['order_id'],
                                        "user_id" => $row['user_id'],
                                        "solicitation_date" => $row['solicitation_date'],
                                        "paid_date" => $paid_date,
                                        "code_identify" => $row['code'],
                                        "amount_solicitation" => $row['amount_solicitation'],
                                        "amount_confirmed" => $row['amount_solicitation'],
                                        "status" => "confirmed",
                                        "stage" => "payment_done",
                                        "disponibilization_date" => $row['disponibilization_date']
                                    ];

                                    $post_field = json_encode($post);

                                    $ch = curl_init($client->key->url_callback_invoice);
                                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'authorization:'.$client->key->authorization_deposit));
                                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

                                    // execute!
                                    $response = curl_exec($ch);
                                    $http_status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                                    // close the connection, release resources used
                                    curl_close($ch);

                                // }else{
                                //     $http_status = "200";
                                // }

                                if($http_status == "200"){
                                    DB::beginTransaction();
                                    try{

                                        $row->update([
                                            "confirmation_callback" => "1"
                                        ]);

                                        DB::commit();

                                    }catch(exception $e){
                                        DB::rollback();
                                    }
                                }

                                $post_var = [
                                    "date_send_callback" => date("Y-m-d H:i:s"),
                                    "type" => "bs2boleto",
                                    "order_id" => $row['order_id'],
                                    "user_id" => $row['user_id'],
                                    "solicitation_date" => $row['solicitation_date'],
                                    "paid_date" => $paid_date,
                                    "code_identify" => $row['code'],
                                    "amount_solicitation" => $row['amount_solicitation'],
                                    "amount_confirmed" => $row['amount_solicitation'],
                                    "status" => "confirmed",
                                    "comission" => $comission,
                                    "disponibilization_date" => $row['disponibilization_date'],
                                    "response_http_server" => $http_status,
                                    "response_server" => $response
                                ];

                                $post_var = json_encode($post_var);

                                $fp = fopen('send-callback-bs2boleto.txt', 'a');
                                fwrite($fp, $post_var."\n");
                                fclose($fp);

                            }catch(exception $e){
                                DB::rollback();
                            }

                        }else{
                            DB::beginTransaction();
                            try{

                                $webhook->update([
                                    "type_register" => "checked"
                                ]);

                                DB::commit();

                            }catch(Exception $e){
                                DB::rollback();
                            }

                        }

                    }elseif($status_bs2 == "3"){ // If cancel

                        $cancel_date = date("Y-m-d H:i:s");

                        DB::beginTransaction();
                        try{

                            $row->update([
                                "status" => "canceled",
                                "cancel_date" => $cancel_date,
                                "final_date" => $cancel_date,
                                "fixed_fee" => $tax_cancel_boleto,
                                "comission" => $tax_cancel_boleto,
                                "quote" => $cotacao_dolar,
                                "percent_markup" => $spread_deposit,
                                "quote_markup" => $cotacao_dolar_markup,
                            ]);

                            $webhook->update([
                                "type_register" => "checked"
                            ]);

                            DB::commit();

                            // set post fields
                            $post = [
                                "order_id" => $row['order_id'],
                                "user_id" => $row['user_id'],
                                "solicitation_date" => $row['solicitation_date'],
                                "cancel_date" => $cancel_date,
                                "fixed_fee" => $tax_cancel_boleto,
                                "code_identify" => $row['code'],
                                "amount_solicitation" => $row['amount_solicitation'],
                                "status" => "canceled"
                            ];

                            $post_field = json_encode($post);

                            $ch = curl_init($client->key->url_callback_invoice);
                            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'authorization:'.$client->key->authorization_deposit));
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

                            // execute!
                            $response = curl_exec($ch);
                            $http_status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                            // close the connection, release resources used
                            curl_close($ch);

                            if($http_status == "200"){
                                DB::beginTransaction();
                                try{

                                    $row->update([
                                        "confirmation_callback" => "1"
                                    ]);

                                    DB::commit();

                                }catch(exception $e){
                                    DB::rollback();
                                }
                            }

                            $post_var = [
                                "date_send_callback" => date("Y-m-d H:i:s"),
                                "type" => "bs2boleto",
                                "order_id" => $row['order_id'],
                                "user_id" => $row['user_id'],
                                "solicitation_date" => $row['solicitation_date'],
                                "cancel_date" => $cancel_date,
                                "code_identify" => $row['code'],
                                "amount_solicitation" => $row['amount_solicitation'],
                                "status" => "canceled",
                                "response_http_server" => $http_status,
                                "response_server" => $response
                            ];

                            $post_var = json_encode($post_var);

                            $fp = fopen('send-callback-bs2boleto.txt', 'a');
                            fwrite($fp, $post_var."\n");
                            fclose($fp);

                        }catch(exception $e){
                            DB::rollback();
                        }

                    }else{

                        if($row->confirmation_callback == NULL){

                            $data_invoice = DataInvoice::where("order_id","=",$row->order_id)->first();

                            // Take status boleto
                            $status_bs2_praca = $payload['StatusBoleto'];
                            $paid_date = date("Y-m-d H:i:s");

                            foreach($status_bs2_praca as $sta){

                                if($sta['Status'] == "2"){

                                    $data_hora_paid = date("Y-m-d H:i:s");

                                    $amount_fiat = $row->amount_solicitation;
                                    $method_payment = "tef";

                                    $days_safe_boleto = "4";

                                    switch($FunctionsController->dia_semana(date("Y-m-d"))){
                                        case"sex": $days_safe_boleto = ($days_safe_boleto + 2); break;
                                        case"sab": $days_safe_boleto = ($days_safe_boleto + 1); break;
                                        case"dom": $days_safe_boleto = ($days_safe_boleto + 1); break;
                                    }

                                    $date_confirmed_bank = date("Y-m-d",strtotime("+".$days_safe_boleto." days"))." 00:00:00";

                                    DB::beginTransaction();
                                    try{

                                        $data_invoice->update([
                                            "status_boletofirst" => "confirmed",
                                            "done_at" => $data_hora_paid
                                        ]);

                                        $row->update([
                                            "status" => "confirmed",
                                            "paid_date" => $data_hora_paid,
                                            "final_date" => $data_hora_paid,
                                            "amount_confirmed" => $amount_fiat,
                                            "final_amount" => $final_amount,
                                            "quote" => $cotacao_dolar,
                                            "percent_markup" => $spread_deposit,
                                            "quote_markup" => $cotacao_dolar_markup,
                                            "fixed_fee" => $fixed_fee,
                                            "percent_fee" => $percent_fee,
                                            "comission" => $comission,
                                            "min_fee" => $min_fee,
                                            "disponibilization_date" => $date_confirmed_bank,
                                            "receita_spread" => $receita_spread_deposito,
                                            "receita_comission" => $receita_comission,
                                        ]);

                                        $webhook->update([
                                            "type_register" => "checked"
                                        ]);

                                        // Deposit
                                        Extract::create([
                                            "transaction_id" => $row->id,
                                            "order_id" => $row->order_id,
                                            "client_id" => $row->client_id,
                                            "user_id" => $row->user_id,
                                            "type_transaction_extract" => "cash-in",
                                            "description_code" => "MD01",
                                            "description_text" => "Depósito de Boleto",
                                            "cash_flow" => $row->amount_solicitation,
                                            "final_amount" => $row->final_amount,
                                            "quote" => $row->quote,
                                            "quote_markup" => $row->quote_markup,
                                            "receita" => ((($row->amount_solicitation / $row->quote) - $row->final_amount) * $row->quote),
                                            "disponibilization_date" => $row->disponibilization_date,
                                        ]);
                                        // Comission
                                        Extract::create([
                                            "transaction_id" => $row->id,
                                            "order_id" => $row->order_id,
                                            "client_id" => $row->client_id,
                                            "user_id" => $row->user_id,
                                            "type_transaction_extract" => "cash-out",
                                            "description_code" => "CM01",
                                            "description_text" => "Comissão sobre Depósito de Boleto",
                                            "cash_flow" => $row->amount_solicitation,
                                            "final_amount" => ($row->comission * (-1)),
                                            "quote" => $row->quote,
                                            "quote_markup" => $row->quote_markup,
                                            "receita" => ($row->comission * $row->quote),
                                            "disponibilization_date" => $row->disponibilization_date,
                                        ]);

                                        DB::commit();

                                    }catch(exception $e){
                                        DB::rollback();
                                    }

                                    // set post fields
                                    $post = [
                                        "order_id" => $row->order_id,
                                        "user_id" => $row->user_id,
                                        "solicitation_date" => $row->solicitation_date,
                                        "paid_date" => $data_hora_paid,
                                        "code_identify" => $row->code,
                                        "amount_solicitation" => $row->amount_solicitation,
                                        "amount_confirmed" => $row->amount_solicitation,
                                        "status" => "confirmed",
                                        "stage" => "show_to_payment",
                                    ];

                                    $post_field = json_encode($post);

                                    $ch = curl_init($client->key->url_callback_invoice);
                                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'authorization:'.$client->key->authorization_deposit));
                                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

                                    // execute!
                                    $response = curl_exec($ch);
                                    $http_status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                                    if($http_status == "200"){
                                        DB::beginTransaction();
                                        try{

                                            $row->update([
                                                "confirmation_callback" => "1"
                                            ]);

                                            DB::commit();

                                        }catch(exception $e){
                                            DB::rollback();
                                        }
                                    }

                                    // close the connection, release resources used
                                    curl_close($ch);

                                    $post_var = [
                                        "date_send_callback" => date("Y-m-d H:i:s",strtotime("-3 hours")),
                                        "type" => "pracabs2",
                                        "order_id" => $row->order_id,
                                        "user_id" => $row->user_id,
                                        "solicitation_date" => $row->solicitation_date,
                                        "paid_date" => $data_hora_paid,
                                        "code_identify" => $row->code,
                                        "amount_solicitation" => $row->amount_solicitation,
                                        "amount_confirmed" => $row->amount_solicitation,
                                        "status" => "confirmed",
                                        "response_http_server" => $http_status,
                                        "response_server" => $response
                                    ];

                                    $post_var = json_encode($post_var);

                                    $fp = fopen('send-callback-pracabs2.txt', 'a');
                                    fwrite($fp, $post_var."\n");
                                    fclose($fp);

                                }else{

                                    DB::beginTransaction();
                                    try{

                                        $webhook->update([
                                            "type_register" => "checked"
                                        ]);

                                        DB::commit();

                                    }catch(Exception $e){
                                        DB::rollback();
                                    }

                                }

                            }

                            $date = date("Y-m-d H:i:s");

                        }

                    }

                }else{

                    // If paid
                    if($status_bs2 == "4"){

                        $paid_date = date("Y-m-d H:i:s");

                        $days_safe_boleto = $client->days_safe_boleto;

                        switch($FunctionsController->dia_semana(date("Y-m-d"))){
                            case"sex": $days_safe_boleto = ($days_safe_boleto + 2); break;
                            case"sab": $days_safe_boleto = ($days_safe_boleto + 1); break;
                            case"dom": $days_safe_boleto = ($days_safe_boleto + 1); break;
                        }


                        $date_confirmed_bank = date("Y-m-d",strtotime("+".$days_safe_boleto." days"))." 00:00:00";

                        $verify = array(
                            "status" => $row['status'],
                            "confirmed_bank" => $row['confirmed_bank'],
                            "disponibilization_date" => date("d/m/Y 00:00:00",strtotime($date_confirmed_bank)),
                            "valor_pago" => $valor_pago,
                            "status_bs2" => $status_bs2,
                            "check" => "webhook"
                        );

                        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/etapa-bs2.txt",json_encode($verify));

                        if($row['confirmed_bank'] == NULL || $row['confirmed_bank'] == '0'){
                            $confirmed_bank_check = "0";
                        }else{
                            $confirmed_bank_check = "1";
                        }

                        if($row['status'] == "confirmed" && $confirmed_bank_check == '0'){

                            DB::beginTransaction();
                            try{

                                $row->update([
                                    "confirmed_bank" => "1",
                                    "disponibilization_date" => $date_confirmed_bank,
                                    "fixed_fee" => $fixed_fee,
                                    "percent_fee" => $percent_fee,
                                    "comission" => $comission,
                                    "quote" => $cotacao_dolar,
                                    "percent_markup" => $spread_deposit,
                                    "quote_markup" => $cotacao_dolar_markup,
                                    "receita_spread" => $receita_spread_deposito,
                                    "receita_comission" => $receita_comission,
                                ]);

                                $webhook->update([
                                    "type_register" => "checked"
                                ]);

                                // Deposit
                                // Extract::create([
                                //     "transaction_id" => $row->id,
                                //     "order_id" => $row->order_id,
                                //     "client_id" => $row->client_id,
                                //     "user_id" => $row->user_id,
                                //     "type_transaction_extract" => "cash-in",
                                //     "description_code" => "MD01",
                                //     "description_text" => "Depósito de Boleto",
                                //     "cash_flow" => $row->amount_solicitation,
                                //     "final_amount" => $row->final_amount,
                                //     "quote" => $row->quote,
                                //     "quote_markup" => $row->quote_markup,
                                //     "receita" => ((($row->amount_solicitation / $row->quote) - $row->final_amount) * $row->quote),
                                //     "disponibilization_date" => $row->disponibilization_date,
                                // ]);
                                // // Comission
                                // Extract::create([
                                //     "transaction_id" => $row->id,
                                //     "order_id" => $row->order_id,
                                //     "client_id" => $row->client_id,
                                //     "user_id" => $row->user_id,
                                //     "type_transaction_extract" => "cash-out",
                                //     "description_code" => "CM01",
                                //     "description_text" => "Comissão sobre Depósito de Boleto",
                                //     "cash_flow" => $row->amount_solicitation,
                                //     "final_amount" => ($row->comission * (-1)),
                                //     "quote" => $row->quote,
                                //     "quote_markup" => $row->quote_markup,
                                //     "receita" => ($row->comission * $row->quote),
                                //     "disponibilization_date" => $row->disponibilization_date,
                                // ]);

                                DB::commit();

                                // if($client->id != "177"){

                                    // set post fields
                                    $post = [
                                        "order_id" => $row['order_id'],
                                        "user_id" => $row['user_id'],
                                        "solicitation_date" => $row['solicitation_date'],
                                        "paid_date" => $paid_date,
                                        "code_identify" => $row['code'],
                                        "amount_solicitation" => $row['amount_solicitation'],
                                        "amount_confirmed" => $row['amount_solicitation'],
                                        "status" => "confirmed",
                                        "stage" => "payment_done",
                                        "disponibilization_date" => $row['disponibilization_date'],
                                        "comission" => $comission,
                                    ];

                                    $post_field = json_encode($post);

                                    $ch = curl_init($client->key->url_callback_invoice);
                                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'authorization:'.$client->key->authorization_deposit));
                                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

                                    // execute!
                                    $response = curl_exec($ch);
                                    $http_status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                                    // close the connection, release resources used
                                    curl_close($ch);

                                // }else{
                                //     $http_status = "200";
                                // }

                                if($http_status == "200"){

                                    $row->update([
                                        "confirmation_callback" => "1"
                                    ]);

                                    DB::commit();

                                }

                                $post_var = [
                                    "date_send_callback" => date("Y-m-d H:i:s"),
                                    "type" => "bs2boleto",
                                    "order_id" => $row['order_id'],
                                    "user_id" => $row['user_id'],
                                    "solicitation_date" => $row['solicitation_date'],
                                    "paid_date" => $paid_date,
                                    "code_identify" => $row['code'],
                                    "amount_solicitation" => $row['amount_solicitation'],
                                    "amount_confirmed" => $row['amount_solicitation'],
                                    "status" => "confirmed",
                                    "comission" => $comission,
                                    "disponibilization_date" => $row['disponibilization_date'],
                                    "response_http_server" => $http_status,
                                    "response_server" => $response
                                ];

                                $post_var = json_encode($post_var);

                                $fp = fopen('/var/www/html/nexapay/logs/send-callback-bs2boleto.txt', 'a');
                                fwrite($fp, $post_var."\n");
                                fclose($fp);

                            }catch(exception $e){
                                DB::rollback();
                            }

                        }elseif($row['status'] == "pending" && $confirmed_bank_check == '0' || $row['status'] == "canceled" && $confirmed_bank_check == '0'){

                            DB::beginTransaction();
                            try{

                                $row->update([
                                    "status" => "confirmed",
                                    "amount_confirmed" => $row['amount_solicitation'],
                                    "final_amount" => $final_amount,
                                    "quote" => $cotacao_dolar,
                                    "percent_markup" => $spread_deposit,
                                    "quote_markup" => $cotacao_dolar_markup,
                                    "fixed_fee" => $fixed_fee,
                                    "percent_fee" => $percent_fee,
                                    "comission" => $comission,
                                    "min_fee" => $min_fee,
                                    "confirmed_bank" => "1",
                                    "paid_date" => $paid_date,
                                    "final_date" => $paid_date,
                                    "disponibilization_date" => $date_confirmed_bank,
                                    "receita_spread" => $receita_spread_deposito,
                                    "receita_comission" => $receita_comission,
                                ]);

                                $webhook->update([
                                    "type_register" => "checked"
                                ]);

                                // Deposit
                                Extract::create([
                                    "transaction_id" => $row->id,
                                    "order_id" => $row->order_id,
                                    "client_id" => $row->client_id,
                                    "user_id" => $row->user_id,
                                    "type_transaction_extract" => "cash-in",
                                    "description_code" => "MD01",
                                    "description_text" => "Depósito de Boleto",
                                    "cash_flow" => $row->amount_solicitation,
                                    "final_amount" => $row->final_amount,
                                    "quote" => $row->quote,
                                    "quote_markup" => $row->quote_markup,
                                    "receita" => ((($row->amount_solicitation / $row->quote) - $row->final_amount) * $row->quote),
                                    "disponibilization_date" => $row->disponibilization_date,
                                ]);
                                // Comission
                                Extract::create([
                                    "transaction_id" => $row->id,
                                    "order_id" => $row->order_id,
                                    "client_id" => $row->client_id,
                                    "user_id" => $row->user_id,
                                    "type_transaction_extract" => "cash-out",
                                    "description_code" => "CM01",
                                    "description_text" => "Comissão sobre Depósito de Boleto",
                                    "cash_flow" => $row->amount_solicitation,
                                    "final_amount" => ($row->comission * (-1)),
                                    "quote" => $row->quote,
                                    "quote_markup" => $row->quote_markup,
                                    "receita" => ($row->comission * $row->quote),
                                    "disponibilization_date" => $row->disponibilization_date,
                                ]);

                                DB::commit();

                                // // if($client->id != "177"){

                                    // set post fields
                                    $post = [
                                        "order_id" => $row['order_id'],
                                        "user_id" => $row['user_id'],
                                        "solicitation_date" => $row['solicitation_date'],
                                        "paid_date" => $paid_date,
                                        "code_identify" => $row['code'],
                                        "amount_solicitation" => $row['amount_solicitation'],
                                        "amount_confirmed" => $row['amount_solicitation'],
                                        "status" => "confirmed",
                                        "stage" => "payment_done",
                                        "comission" => $comission,
                                        "disponibilization_date" => $row['disponibilization_date'],
                                    ];

                                    $post_field = json_encode($post);

                                    $ch = curl_init($client->key->url_callback_invoice);
                                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'authorization:'.$client->key->authorization_deposit));
                                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

                                    // execute!
                                    $response = curl_exec($ch);
                                    $http_status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                                    // close the connection, release resources used
                                    curl_close($ch);

                                // }else{
                                //     $http_status = "200";
                                // }

                                if($http_status == "200"){
                                    DB::beginTransaction();
                                    try{

                                        $row->update([
                                            "confirmation_callback" => "1"
                                        ]);

                                        DB::commit();

                                    }catch(exception $e){
                                        DB::rollback();
                                    }
                                }

                                $post_var = [
                                    "date_send_callback" => date("Y-m-d H:i:s"),
                                    "type" => "bs2boleto",
                                    "order_id" => $row['order_id'],
                                    "user_id" => $row['user_id'],
                                    "solicitation_date" => $row['solicitation_date'],
                                    "paid_date" => $paid_date,
                                    "code_identify" => $row['code'],
                                    "amount_solicitation" => $row['amount_solicitation'],
                                    "amount_confirmed" => $row['amount_solicitation'],
                                    "status" => "confirmed",
                                    "comission" => $comission,
                                    "disponibilization_date" => $row['disponibilization_date'],
                                    "response_http_server" => $http_status,
                                    "response_server" => $response
                                ];

                                $post_var = json_encode($post_var);

                                $fp = fopen('send-callback-bs2boleto.txt', 'a');
                                fwrite($fp, $post_var."\n");
                                fclose($fp);

                            }catch(exception $e){
                                DB::rollback();
                            }

                        }

                    }elseif($status_bs2 == "3"){ // If cancel

                        $cancel_date = date("Y-m-d H:i:s");

                        DB::beginTransaction();
                        try{

                            $row->update([
                                "status" => "canceled",
                                "cancel_date" => $cancel_date,
                                "final_date" => $cancel_date,
                                "status" => "canceled",
                                "cancel_date" => $cancel_date,
                                "final_date" => $cancel_date,
                                "fixed_fee" => $tax_cancel_boleto,
                                "comission" => $tax_cancel_boleto,
                                "quote" => $cotacao_dolar,
                                "percent_markup" => $spread_deposit,
                                "quote_markup" => $cotacao_dolar_markup,
                            ]);

                            $webhook->update([
                                "type_register" => "checked"
                            ]);

                            DB::commit();

                            // set post fields
                            $post = [
                                "order_id" => $row['order_id'],
                                "user_id" => $row['user_id'],
                                "solicitation_date" => $row['solicitation_date'],
                                "cancel_date" => $cancel_date,
                                "code_identify" => $row['code'],
                                "amount_solicitation" => $row['amount_solicitation'],
                                "status" => "canceled"
                            ];

                            $post_field = json_encode($post);

                            $ch = curl_init($client->key->url_callback_invoice);
                            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'authorization:'.$client->key->authorization_deposit));
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

                            // execute!
                            $response = curl_exec($ch);
                            $http_status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                            // close the connection, release resources used
                            curl_close($ch);

                            if($http_status == "200"){
                                DB::beginTransaction();
                                try{

                                    $row->update([
                                        "confirmation_callback" => "1"
                                    ]);

                                    DB::commit();

                                }catch(exception $e){
                                    DB::rollback();
                                }
                            }

                            $post_var = [
                                "date_send_callback" => date("Y-m-d H:i:s"),
                                "type" => "bs2boleto",
                                "order_id" => $row['order_id'],
                                "user_id" => $row['user_id'],
                                "solicitation_date" => $row['solicitation_date'],
                                "cancel_date" => $cancel_date,
                                "code_identify" => $row['code'],
                                "amount_solicitation" => $row['amount_solicitation'],
                                "status" => "canceled",
                                "response_http_server" => $http_status,
                                "response_server" => $response
                            ];

                            $post_var = json_encode($post_var);

                            $fp = fopen('send-callback-bs2boleto.txt', 'a');
                            fwrite($fp, $post_var."\n");
                            fclose($fp);

                        }catch(exception $e){
                            DB::rollback();
                        }

                    }else{

                        DB::beginTransaction();
                        try{

                            $webhook->update([
                                "type_register" => "checked"
                            ]);

                            DB::commit();

                        }catch(Exception $e){
                            DB::rollback();
                        }

                    }

                }

            }else{

                // If paid
                if($status_bs2 == "4"){

                    $paid_date = date("Y-m-d H:i:s");

                    $days_safe_boleto = $client->days_safe_boleto;

                    switch($FunctionsController->dia_semana(date("Y-m-d"))){
                        case"sex": $days_safe_boleto = ($days_safe_boleto + 2); break;
                        case"sab": $days_safe_boleto = ($days_safe_boleto + 1); break;
                        case"dom": $days_safe_boleto = ($days_safe_boleto + 1); break;
                    }


                    $date_confirmed_bank = date("Y-m-d",strtotime("+".$days_safe_boleto." days"))." 00:00:00";

                    $verify = array(
                        "status" => $row['status'],
                        "confirmed_bank" => $row['confirmed_bank'],
                        "disponibilization_date" => date("d/m/Y 00:00:00",strtotime($date_confirmed_bank)),
                        "valor_pago" => $valor_pago,
                        "status_bs2" => $status_bs2,
                        "check" => "webhook"
                    );

                    $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/etapa-bs2.txt",json_encode($verify));

                    if($row['confirmed_bank'] == NULL || $row['confirmed_bank'] == '0'){
                        $confirmed_bank_check = "0";
                    }else{
                        $confirmed_bank_check = "1";
                    }

                    if($row['status'] == "confirmed" && $confirmed_bank_check == '0'){

                        DB::beginTransaction();
                        try{

                            $row->update([
                                "confirmed_bank" => "1",
                                "disponibilization_date" => $date_confirmed_bank,
                                "fixed_fee" => $fixed_fee,
                                "percent_fee" => $percent_fee,
                                "comission" => $comission,
                                "quote" => $cotacao_dolar,
                                "percent_markup" => $spread_deposit,
                                "quote_markup" => $cotacao_dolar_markup,
                                "receita_spread" => $receita_spread_deposito,
                                "receita_comission" => $receita_comission,
                            ]);

                            $webhook->update([
                                "type_register" => "checked"
                            ]);

                            // Deposit
                            Extract::create([
                                "transaction_id" => $row->id,
                                "order_id" => $row->order_id,
                                "client_id" => $row->client_id,
                                "user_id" => $row->user_id,
                                "type_transaction_extract" => "cash-in",
                                "description_code" => "MD01",
                                "description_text" => "Depósito de Boleto",
                                "cash_flow" => $row->amount_solicitation,
                                "final_amount" => $row->final_amount,
                                "quote" => $row->quote,
                                "quote_markup" => $row->quote_markup,
                                "receita" => ((($row->amount_solicitation / $row->quote) - $row->final_amount) * $row->quote),
                                "disponibilization_date" => $row->disponibilization_date,
                            ]);
                            // Comission
                            Extract::create([
                                "transaction_id" => $row->id,
                                "order_id" => $row->order_id,
                                "client_id" => $row->client_id,
                                "user_id" => $row->user_id,
                                "type_transaction_extract" => "cash-out",
                                "description_code" => "CM01",
                                "description_text" => "Comissão sobre Depósito de Boleto",
                                "cash_flow" => $row->amount_solicitation,
                                "final_amount" => ($row->comission * (-1)),
                                "quote" => $row->quote,
                                "quote_markup" => $row->quote_markup,
                                "receita" => ($row->comission * $row->quote),
                                "disponibilization_date" => $row->disponibilization_date,
                            ]);

                            DB::commit();

                            // if($client->id != "177"){

                                // set post fields
                                $post = [
                                    "order_id" => $row['order_id'],
                                    "user_id" => $row['user_id'],
                                    "solicitation_date" => $row['solicitation_date'],
                                    "paid_date" => $paid_date,
                                    "code_identify" => $row['code'],
                                    "amount_solicitation" => $row['amount_solicitation'],
                                    "amount_confirmed" => $row['amount_solicitation'],
                                    "status" => "confirmed",
                                    "stage" => "payment_done",
                                    "comission" => $comission,
                                    "disponibilization_date" => $row['disponibilization_date'],
                                ];

                                $post_field = json_encode($post);

                                $ch = curl_init($client->key->url_callback_invoice);
                                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'authorization:'.$client->key->authorization_deposit));
                                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

                                // execute!
                                $response = curl_exec($ch);
                                $http_status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                                // close the connection, release resources used
                                curl_close($ch);

                            // }else{
                            //     $http_status = "200";
                            // }

                            if($http_status == "200"){

                                $row->update([
                                    "confirmation_callback" => "1"
                                ]);

                                DB::commit();

                            }

                            $post_var = [
                                "date_send_callback" => date("Y-m-d H:i:s"),
                                "type" => "bs2boleto",
                                "order_id" => $row['order_id'],
                                "user_id" => $row['user_id'],
                                "solicitation_date" => $row['solicitation_date'],
                                "paid_date" => $paid_date,
                                "code_identify" => $row['code'],
                                "amount_solicitation" => $row['amount_solicitation'],
                                "amount_confirmed" => $row['amount_solicitation'],
                                "status" => "confirmed",
                                "comission" => $comission,
                                "disponibilization_date" => $row['disponibilization_date'],
                                "response_http_server" => $http_status,
                                "response_server" => $response
                            ];

                            $post_var = json_encode($post_var);

                            $fp = fopen('/var/www/html/nexapay/logs/send-callback-bs2boleto.txt', 'a');
                            fwrite($fp, $post_var."\n");
                            fclose($fp);

                        }catch(exception $e){
                            DB::rollback();
                        }

                    }elseif($row['status'] == "pending" && $confirmed_bank_check == '0' || $row['status'] == "canceled" && $confirmed_bank_check == '0'){

                        DB::beginTransaction();
                        try{

                            $row->update([
                                "status" => "confirmed",
                                "amount_confirmed" => $row['amount_solicitation'],
                                "final_amount" => $final_amount,
                                "quote" => $cotacao_dolar,
                                "percent_markup" => $spread_deposit,
                                "quote_markup" => $cotacao_dolar_markup,
                                "fixed_fee" => $fixed_fee,
                                "percent_fee" => $percent_fee,
                                "comission" => $comission,
                                "min_fee" => $min_fee,
                                "confirmed_bank" => "1",
                                "paid_date" => $paid_date,
                                "final_date" => $paid_date,
                                "disponibilization_date" => $date_confirmed_bank,
                                "receita_spread" => $receita_spread_deposito,
                                "receita_comission" => $receita_comission,
                            ]);

                            // Deposit
                            Extract::create([
                                "transaction_id" => $row->id,
                                "order_id" => $row->order_id,
                                "client_id" => $row->client_id,
                                "user_id" => $row->user_id,
                                "type_transaction_extract" => "cash-in",
                                "description_code" => "MD01",
                                "description_text" => "Depósito de Boleto",
                                "cash_flow" => $row->amount_solicitation,
                                "final_amount" => $row->final_amount,
                                "quote" => $row->quote,
                                "quote_markup" => $row->quote_markup,
                                "receita" => ((($row->amount_solicitation / $row->quote) - $row->final_amount) * $row->quote),
                                "disponibilization_date" => $row->disponibilization_date,
                            ]);
                            // Comission
                            Extract::create([
                                "transaction_id" => $row->id,
                                "order_id" => $row->order_id,
                                "client_id" => $row->client_id,
                                "user_id" => $row->user_id,
                                "type_transaction_extract" => "cash-out",
                                "description_code" => "CM01",
                                "description_text" => "Comissão sobre Depósito de Boleto",
                                "cash_flow" => $row->amount_solicitation,
                                "final_amount" => ($row->comission * (-1)),
                                "quote" => $row->quote,
                                "quote_markup" => $row->quote_markup,
                                "receita" => ($row->comission * $row->quote),
                                "disponibilization_date" => $row->disponibilization_date,
                            ]);

                            DB::commit();

                            // // if($client->id != "177"){

                                // set post fields
                                $post = [
                                    "order_id" => $row['order_id'],
                                    "user_id" => $row['user_id'],
                                    "solicitation_date" => $row['solicitation_date'],
                                    "paid_date" => $paid_date,
                                    "code_identify" => $row['code'],
                                    "amount_solicitation" => $row['amount_solicitation'],
                                    "amount_confirmed" => $row['amount_solicitation'],
                                    "status" => "confirmed",
                                    "stage" => "payment_done",
                                    "comission" => $comission,
                                    "disponibilization_date" => date("d/m/Y 00:00:00",strtotime($date_confirmed_bank))
                                ];

                                $post_field = json_encode($post);

                                $ch = curl_init($client->key->url_callback_invoice);
                                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'authorization:'.$client->key->authorization_deposit));
                                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

                                // execute!
                                $response = curl_exec($ch);
                                $http_status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                                // close the connection, release resources used
                                curl_close($ch);

                            // }else{
                            //     $http_status = "200";
                            // }

                            if($http_status == "200"){
                                DB::beginTransaction();
                                try{

                                    $row->update([
                                        "confirmation_callback" => "1"
                                    ]);

                                    DB::commit();

                                }catch(exception $e){
                                    DB::rollback();
                                }
                            }

                            $post_var = [
                                "date_send_callback" => date("Y-m-d H:i:s"),
                                "type" => "bs2boleto",
                                "order_id" => $row['order_id'],
                                "user_id" => $row['user_id'],
                                "solicitation_date" => $row['solicitation_date'],
                                "paid_date" => $paid_date,
                                "code_identify" => $row['code'],
                                "amount_solicitation" => $row['amount_solicitation'],
                                "amount_confirmed" => $row['amount_solicitation'],
                                "status" => "confirmed",
                                "comission" => $comission,
                                "disponibilization_date" => date("d/m/Y 00:00:00",strtotime($date_confirmed_bank)),
                                "response_http_server" => $http_status,
                                "response_server" => $response
                            ];

                            $post_var = json_encode($post_var);

                            $fp = fopen('send-callback-bs2boleto.txt', 'a');
                            fwrite($fp, $post_var."\n");
                            fclose($fp);

                        }catch(exception $e){
                            DB::rollback();
                        }

                    }

                }elseif($status_bs2 == "3"){ // If cancel

                    $cancel_date = date("Y-m-d H:i:s");

                    DB::beginTransaction();
                    try{

                        $row->update([
                            "status" => "canceled",
                            "cancel_date" => $cancel_date,
                            "final_date" => $cancel_date,
                            "fixed_fee" => $tax_cancel_boleto,
                            "comission" => $tax_cancel_boleto,
                            "quote" => $cotacao_dolar,
                            "percent_markup" => $spread_deposit,
                            "quote_markup" => $cotacao_dolar_markup,
                        ]);

                        $webhook->update([
                            "type_register" => "checked"
                        ]);

                        DB::commit();

                        // set post fields
                        $post = [
                            "order_id" => $row['order_id'],
                            "user_id" => $row['user_id'],
                            "solicitation_date" => $row['solicitation_date'],
                            "cancel_date" => $cancel_date,
                            "code_identify" => $row['code'],
                            "amount_solicitation" => $row['amount_solicitation'],
                            "status" => "canceled"
                        ];

                        $post_field = json_encode($post);

                        $ch = curl_init($client->key->url_callback_invoice);
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'authorization:'.$client->key->authorization_deposit));
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

                        // execute!
                        $response = curl_exec($ch);
                        $http_status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                        // close the connection, release resources used
                        curl_close($ch);

                        if($http_status == "200"){
                            DB::beginTransaction();
                            try{

                                $row->update([
                                    "confirmation_callback" => "1"
                                ]);

                                DB::commit();

                            }catch(exception $e){
                                DB::rollback();
                            }
                        }

                        $post_var = [
                            "date_send_callback" => date("Y-m-d H:i:s"),
                            "type" => "bs2boleto",
                            "order_id" => $row['order_id'],
                            "user_id" => $row['user_id'],
                            "solicitation_date" => $row['solicitation_date'],
                            "cancel_date" => $cancel_date,
                            "code_identify" => $row['code'],
                            "amount_solicitation" => $row['amount_solicitation'],
                            "status" => "canceled",
                            "response_http_server" => $http_status,
                            "response_server" => $response
                        ];

                        $post_var = json_encode($post_var);

                        $fp = fopen('send-callback-bs2boleto.txt', 'a');
                        fwrite($fp, $post_var."\n");
                        fclose($fp);

                    }catch(exception $e){
                        DB::rollback();
                    }

                }else{

                    DB::beginTransaction();
                    try{

                        $webhook->update([
                            "type_register" => "checked"
                        ]);

                        DB::commit();

                    }catch(Exception $e){
                        DB::rollback();
                    }

                }

            }

        }


    }
}
