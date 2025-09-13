<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mpdf\Mpdf;
use App\Http\Controllers\FunctionsAPIController;
use App\Models\{Transactions,Banks,BankClientsAccount,ReceiptCelcoin,Webhook,BankISPB};

class ComprovantePixController extends Controller

{
    //

    public function index($id){

        $FunctionsAPIController = new FunctionsAPIController();

        $transaction = Transactions::where('id', $id)
        ->where('status', 'confirmed')
        ->where('method_transaction', 'pix')->first();

        if(!isset($transaction->id) ){
            echo "<script>window.close();</script>";
        }

        $client = $transaction->client;
        $bankWithdraw = Banks::where('id', $transaction->id_bank)->first();

        if($bankWithdraw->code == "587"){

            $receiptCelcoin = ReceiptCelcoin::where("transaction_id", $transaction->id)->first();

            $client_id_celcoin = $client->bankWithdrawPix->client_id_celcoin;
            $client_secret_celcoin = $client->bankWithdrawPix->client_secret_celcoin;
            $access_token_celcoin = $client->bankWithdrawPix->access_token_celcoin;

            $params = [
                'client_id' => $transaction->client_id,
                'client_id_celcoin' => $client_id_celcoin,
                'client_secret_celcoin' => $client_secret_celcoin,
                'access_token_celcoin' => $access_token_celcoin
            ];

            $token_celcoin = json_decode($FunctionsAPIController->getTokenCELCOIN($params),true);

            $token = $token_celcoin['access_token'];

            $user_account_data = json_decode(base64_decode($transaction->user_account_data),true);

            $pix_key = $user_account_data['pix_key'];

            switch($user_account_data['type_pixkey']){
                case"telefone":
                    $pix_key = str_replace("(","",$pix_key);
                    $pix_key = str_replace(")","",$pix_key);
                    $pix_key = str_replace(" ","",$pix_key);
                    $pix_key = str_replace("-","",$pix_key);
                    $pix_key = "+55".$pix_key;
                break;
                case"cpf":
                    $pix_key = str_replace(".","",$pix_key);
                    $pix_key = str_replace("-","",$pix_key);
                break;
                case"cnpj":
                    $pix_key = str_replace(".","",$pix_key);
                    $pix_key = str_replace("-","",$pix_key);
                    $pix_key = str_replace("/","",$pix_key);
                break;
                case"email":
                    $pix_key = strtolower($pix_key);
                break;
                case"aleatoria":
                    $pix_key = $pix_key;
                break;

            }

            $pixInfoCelcoin = json_decode($this->getPixInfoCelcoin($pix_key,$token),true);

            $pre_agencia = $pixInfoCelcoin['account']['branch'];

            switch(strlen($pre_agencia)){
                case"1": $agencia = "000".$pre_agencia; break;
                case"2": $agencia = "00".$pre_agencia; break;
                case"3": $agencia = "0".$pre_agencia; break;
                default: $agencia = $pre_agencia; break;
            }

            $conta = $pixInfoCelcoin['account']['accountNumber'];
            $cpfCnpj = formatCnpjCpf($pixInfoCelcoin['owner']['taxIdNumber']);
            $nome = $pixInfoCelcoin['owner']['name'];

            $bankAccount = "";
            // $bankAccount = BankClientsAccount::where("code",$pixInfoCelcoin['Infos']['ReceiverBank'])->first();
            // if($bankAccount == ""){ $banco = $bankAccount->name; }else{ $banco = "BANCO NÃO INFORMADO"; }
            if($bankAccount == ""){ $banco = "BANCO NÃO INFORMADO"; }

            $payer_document = formatCnpjCpf($bankWithdraw->document);
            $payer_name = $bankWithdraw->holder;

            $e2e = $receiptCelcoin->receipt;

            $nomeBranco = preg_replace('/[ -]+/' , '-' , $user_account_data['name']);
            $file = $nomeBranco."_".$transaction->id;
            $filename = $file.".pdf";

            $mpdf = new Mpdf(['mode' => 'utf-8','format' => 'A4','margin_left' => 30,'margin_right' => 30,'margin_top' => 0,'margin_bottom' => 0,'margin_header' => 0,'margin_footer' => 0]);

            $html = "<!DOCTYPE html><html lang='pt'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>".$filename."</title>";
            $html .= "<link rel='preconnect' href='https://fonts.googleapis.com'><link rel='preconnect' href='https://fonts.gstatic.com' crossorigin><link href='https://fonts.googleapis.com/css2?family=Roboto:wght@300&display=swap' rel='stylesheet'>";
            $html .= "<style type='text/css'>body{color: #595959; font-family: 'Roboto', sans-serif !important;}h1,h2,h3,h4,h5{color:#8E1EC0;}</style>";
            $html .= "</head><body>";
            $html .= "<div><br/><br/>";
                $html .= "<div class='bts-comprovante-container'>";
                    $html .= "<div class='bts-comprovante'>";
                        $html .= "<div style='display: block' class='comprovante-conteudo'>";
                            $html .= "<div class='bts-paper'>";
                                $html .= "<div class='bts-comprovante'>";
                                    $html .= "<div class='bts-header'>";
                                    $html .= "<table class='table' width='100%'>";
                                        $html .= "<tr>";
                                            $html .= "<td witdth='50%'><img src='https://admin.fastpayments.com.br/img/fast-payments-logo.png' width='200'></td>";
                                            $html .= "<td witdth='50%' align='right' style='text-align:right;'><h2 class='tipo'>Comprovante <br> de Pagamento Pix</h2></td>"; // end .tipo
                                        $html .= "</tr>";
                                    $html .= "</table>";
                                    $html .= "<div style='height:5px;background-color:#8E1EC0;'></div>";
                                    $html .= "</div>"; // end .bts-header
                                    $html .= "<div class='bts-valor' style='text-align:center;'>";
                                        $html .= "<h2 style='margin-bottom:5px;'>R$ ".number_format($transaction->final_amount, 2, ',','.')."</h2>";
                                        $html .= "<span>";
                                            $html .= "<span style='font-size:12px;' ><b>Realizado em ".date('d/m/Y', strtotime($transaction->final_date))." às ".date('H:i:s', strtotime($transaction->final_date))."</b></span>";
                                        $html .= "</span><br>";
                                    $html .= "<br></div>"; // end .bts-valor

                                    $html .= "<hr />";

                                    $html .= "<div class='bts-block-text' style='display: block'>";

                                        $html .= "<h3>DADOS DO DESTINATÁRIO</h3>";
                                        $html .= "<table class='table' width='100%'>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>Nome:</div></td>";
                                                $html .= "<td><div class='value'>".$nome."</div></td>";
                                            $html .= "</tr>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>CPF/CNPJ:</div></td>";
                                                $html .= "<td><div class='value'>".$cpfCnpj."</div></td>";
                                            $html .= "</tr>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>Instituição:</div></td>";
                                                $html .= "<td><div class='value'>".$banco."</div></td>";
                                            $html .= "</tr>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>Agência:</div></td>";
                                                $html .= "<td><div class='value'>".$agencia."</div></td>";
                                            $html .= "</tr>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>Conta:</div></td>";
                                                $html .= "<td><div class='value'>".hyphenate($conta)."</div></td>";
                                            $html .= "</tr>";
                                        $html .= "</table>";

                                    $html .= "</div>"; // end .bts-block-text

                                    $html .= "<div class='bts-block-text' style='display: block'>";

                                        $html .= "<h3>DADOS DO PAGADOR</h3>";
                                        $html .= "<table class='table' width='100%'>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>Nome:</div></td>";
                                                $html .= "<td><div class='value'>".$payer_name."</div></td>";
                                            $html .= "</tr>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>CPF/CNPJ:</div></td>";
                                                $html .= "<td><div class='value'>".$payer_document."</div></td>";
                                            $html .= "</tr>";
                                        $html .= "</table>";

                                    $html .= "</div>"; // end .bts-block-text

                                    $html .= "<hr />";

                                    $html .= "<div class='bts-codes' style='display: block; text-align:center;'>"; // .codes
                                        $html .= "<div style='text-align:center;'>ID da Transação</div>";
                                        $html .= "<h4 style='text-align:center;'>".$e2e."</h4>";
                                    $html .= "</div>"; //end .codes

                                    $html .= "<div style='background-color: #8E1EC0; text-align:center;padding: 15px; color: #fff !important; font-weight: normal !important'>"; // .footer
                                        $html .= "(Atendimento de segunda a sexta, exceto feriados, das 8h às 20h)  <br><br>";
                                        $html .= "Capitais e Regiões Metropolitanas: 3003-0000 <br>";
                                        $html .= "Demais localidades: 0800 123 1234 <br>";
                                        $html .= "Ouvidoria: 0800 321 4321";
                                    $html .= "</div>"; //end .footer


                                $html .= "</div>"; // end .bts-comprovante
                            $html .= "</div>"; //end .bts-paper
                        $html .= "</div>"; // end .comprovante-conteudo
                    $html .= "</div>"; // end .bts-comprovante
                $html .= "</div>"; //end .bts-comprovant-container
            $html .= "</div></body></html>";

        }elseif($bankWithdraw->code == "588"){

            $webhookData = Webhook::where("client_id", $transaction->client_id)->where("order_id",$transaction->order_id)->first();

            $user_account_data = json_decode(base64_decode($transaction->user_account_data),true);

            $pix_key = $user_account_data['pix_key'];

            switch($user_account_data['type_pixkey']){
                case"telefone":
                    $pix_key = str_replace("(","",$pix_key);
                    $pix_key = str_replace(")","",$pix_key);
                    $pix_key = str_replace(" ","",$pix_key);
                    $pix_key = str_replace("-","",$pix_key);
                    $pix_key = "+55".$pix_key;
                break;
                case"cpf":
                    $pix_key = str_replace(".","",$pix_key);
                    $pix_key = str_replace("-","",$pix_key);
                break;
                case"cnpj":
                    $pix_key = str_replace(".","",$pix_key);
                    $pix_key = str_replace("-","",$pix_key);
                    $pix_key = str_replace("/","",$pix_key);
                break;
                case"email":
                    $pix_key = strtolower($pix_key);
                break;
                case"aleatoria":
                    $pix_key = $pix_key;
                break;

            }

            $pixInfoAccount = json_decode($webhookData->body,true);

            $pre_agencia = "0000";

            switch(strlen($pre_agencia)){
                case"1": $agencia = "000".$pre_agencia; break;
                case"2": $agencia = "00".$pre_agencia; break;
                case"3": $agencia = "0".$pre_agencia; break;
                default: $agencia = $pre_agencia; break;
            }

            $conta = "0000";
            $cpfCnpj = formatCnpjCpf($user_account_data['document']);
            $nome = $pixInfoAccount['data']['recipient']['name'];

            $bankAccount = $pixInfoAccount['data']['recipient']['bank']['name'];
            // $bankAccount = BankClientsAccount::where("code",$pixInfoCelcoin['Infos']['ReceiverBank'])->first();
            // if($bankAccount == ""){ $banco = $bankAccount->name; }else{ $banco = "BANCO NÃO INFORMADO"; }
            if($bankAccount == ""){ $banco = "BANCO NÃO INFORMADO"; }else{ $banco = $bankAccount; }

            $payer_document = formatCnpjCpf($bankWithdraw->document);
            $payer_name = $bankWithdraw->holder;

            $e2e = $pixInfoAccount['data']['endToEndId'];

            $nomeBranco = preg_replace('/[ -]+/' , '-' , $user_account_data['name']);
            $file = $nomeBranco."_".$transaction->id;
            $filename = $file.".pdf";

            $mpdf = new Mpdf(['mode' => 'utf-8','format' => 'A4','margin_left' => 30,'margin_right' => 30,'margin_top' => 0,'margin_bottom' => 0,'margin_header' => 0,'margin_footer' => 0]);

            $html = "<!DOCTYPE html><html lang='pt'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>".$filename."</title>";
            $html .= "<link rel='preconnect' href='https://fonts.googleapis.com'><link rel='preconnect' href='https://fonts.gstatic.com' crossorigin><link href='https://fonts.googleapis.com/css2?family=Roboto:wght@300&display=swap' rel='stylesheet'>";
            $html .= "<style type='text/css'>body{color: #595959; font-family: 'Roboto', sans-serif !important;}h1,h2,h3,h4,h5{color:#8E1EC0;}</style>";
            $html .= "</head><body>";
            $html .= "<div><br/><br/>";
                $html .= "<div class='bts-comprovante-container'>";
                    $html .= "<div class='bts-comprovante'>";
                        $html .= "<div style='display: block' class='comprovante-conteudo'>";
                            $html .= "<div class='bts-paper'>";
                                $html .= "<div class='bts-comprovante'>";
                                    $html .= "<div class='bts-header'>";
                                    $html .= "<table class='table' width='100%'>";
                                        $html .= "<tr>";
                                            $html .= "<td witdth='50%'><img src='https://admin.fastpayments.com.br/img/fast-payments-logo.png' width='200'></td>";
                                            $html .= "<td witdth='50%' align='right' style='text-align:right;'><h2 class='tipo'>Comprovante <br> de Pagamento Pix</h2></td>"; // end .tipo
                                        $html .= "</tr>";
                                    $html .= "</table>";
                                    $html .= "<div style='height:5px;background-color:#8E1EC0;'></div>";
                                    $html .= "</div>"; // end .bts-header
                                    $html .= "<div class='bts-valor' style='text-align:center;'>";
                                        $html .= "<h2 style='margin-bottom:5px;'>R$ ".number_format($transaction->final_amount, 2, ',','.')."</h2>";
                                        $html .= "<span>";
                                            $html .= "<span style='font-size:12px;' ><b>Realizado em ".date('d/m/Y', strtotime($transaction->final_date))." às ".date('H:i:s', strtotime($transaction->final_date))."</b></span>";
                                        $html .= "</span><br>";
                                    $html .= "<br></div>"; // end .bts-valor

                                    $html .= "<hr />";

                                    $html .= "<div class='bts-block-text' style='display: block'>";

                                        $html .= "<h3>DADOS DO DESTINATÁRIO</h3>";
                                        $html .= "<table class='table' width='100%'>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>Nome:</div></td>";
                                                $html .= "<td><div class='value'>".$nome."</div></td>";
                                            $html .= "</tr>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>CPF/CNPJ:</div></td>";
                                                $html .= "<td><div class='value'>".$cpfCnpj."</div></td>";
                                            $html .= "</tr>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>Instituição:</div></td>";
                                                $html .= "<td><div class='value'>".$banco."</div></td>";
                                            $html .= "</tr>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>Agência:</div></td>";
                                                $html .= "<td><div class='value'>".$agencia."</div></td>";
                                            $html .= "</tr>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>Conta:</div></td>";
                                                $html .= "<td><div class='value'>".hyphenate($conta)."</div></td>";
                                            $html .= "</tr>";
                                        $html .= "</table>";

                                    $html .= "</div>"; // end .bts-block-text

                                    $html .= "<div class='bts-block-text' style='display: block'>";

                                        $html .= "<h3>DADOS DO PAGADOR</h3>";
                                        $html .= "<table class='table' width='100%'>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>Nome:</div></td>";
                                                $html .= "<td><div class='value'>".$payer_name."</div></td>";
                                            $html .= "</tr>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>CPF/CNPJ:</div></td>";
                                                $html .= "<td><div class='value'>".$payer_document."</div></td>";
                                            $html .= "</tr>";
                                        $html .= "</table>";

                                    $html .= "</div>"; // end .bts-block-text

                                    $html .= "<hr />";

                                    $html .= "<div class='bts-codes' style='display: block; text-align:center;'>"; // .codes
                                        $html .= "<div style='text-align:center;'>ID da Transação</div>";
                                        $html .= "<h4 style='text-align:center;'>".$e2e."</h4>";
                                    $html .= "</div>"; //end .codes

                                    $html .= "<div style='background-color: #8E1EC0; text-align:center;padding: 15px; color: #fff !important; font-weight: normal !important'>"; // .footer
                                        $html .= "(Atendimento de segunda a sexta, exceto feriados, das 8h às 20h)  <br><br>";
                                        $html .= "Capitais e Regiões Metropolitanas: 3003-0000 <br>";
                                        $html .= "Demais localidades: 0800 123 1234 <br>";
                                        $html .= "Ouvidoria: 0800 321 4321";
                                    $html .= "</div>"; //end .footer


                                $html .= "</div>"; // end .bts-comprovante
                            $html .= "</div>"; //end .bts-paper
                        $html .= "</div>"; // end .comprovante-conteudo
                    $html .= "</div>"; // end .bts-comprovante
                $html .= "</div>"; //end .bts-comprovant-container
            $html .= "</div></body></html>";

        }elseif($bankWithdraw->code == "221"){

            $id = $transaction->payment_id;
            $bank = $bankWithdraw;

            // Acesso OpenPix
            $auth_openpix = $bank->auth_openpix;

            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.openpix.com.br/api/v1/payment/'.$transaction->payment_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: '.$auth_openpix
            ),
            ));

            $response = json_decode(curl_exec($curl),true);

            curl_close($curl);

            if($response['payment']['status'] == "FAILED"){

            }

            $e2e = $response['transaction']['endToEndId'];

            $account_data = json_decode(base64_decode($transaction->user_account_data),true);
            $pixInfo = json_decode($this->getPixInfo($account_data['pix_key']),true);

            $pre_agencia = $pixInfo['Infos']['ReceiverBankBranch'];

            switch(strlen($pre_agencia)){
                case"1": $agencia = "000".$pre_agencia; break;
                case"2": $agencia = "00".$pre_agencia; break;
                case"3": $agencia = "0".$pre_agencia; break;
                default: $agencia = $pre_agencia; break;
            }

            $conta = $pixInfo['Infos']['ReceiverBankAccount'].$pixInfo['Infos']['ReceiverBankAccountDigit'];
            $cpfCnpj = formatCnpjCpf($pixInfo['Infos']['ReceiverTaxNumber']);
            $nome = $pixInfo['Infos']['ReceiverName'];

            $bankAccount = BankClientsAccount::where("code",$pixInfo['Infos']['ReceiverBank'])->first();
            if(isset($bankAccount)){ $banco = $bankAccount->name; }else{ $banco = "BANCO NÃO INFORMADO"; }

            $nomeBranco = preg_replace('/[ -]+/' , '-' , $nome);
            $file = $nomeBranco."_".$transaction->id;
            $filename = $file.".pdf";

            $mpdf = new Mpdf(['mode' => 'utf-8','format' => 'A4','margin_left' => 30,'margin_right' => 30,'margin_top' => 0,'margin_bottom' => 0,'margin_header' => 0,'margin_footer' => 0]);

            $html = "<!DOCTYPE html><html lang='pt'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>".$filename."</title>";
            $html .= "<link rel='preconnect' href='https://fonts.googleapis.com'><link rel='preconnect' href='https://fonts.gstatic.com' crossorigin><link href='https://fonts.googleapis.com/css2?family=Roboto:wght@300&display=swap' rel='stylesheet'>";
            $html .= "<style type='text/css'>body{color: #595959; font-family: 'Roboto', sans-serif !important;}h1,h2,h3,h4,h5{color:#8E1EC0;}</style>";
            $html .= "</head><body>";
            $html .= "<div><br/><br/>";
                $html .= "<div class='bts-comprovante-container'>";
                    $html .= "<div class='bts-comprovante'>";
                        $html .= "<div style='display: block' class='comprovante-conteudo'>";
                            $html .= "<div class='bts-paper'>";
                                $html .= "<div class='bts-comprovante'>";
                                    $html .= "<div class='bts-header'>";
                                    $html .= "<table class='table' width='100%'>";
                                        $html .= "<tr>";
                                            $html .= "<td witdth='50%'><img src='https://admin.fastpayments.com.br/img/fast-payments-logo.png' width='200'></td>";
                                            $html .= "<td witdth='50%' align='right' style='text-align:right;'><h2 class='tipo'>Comprovante <br> de Pagamento Pix</h2></td>"; // end .tipo
                                        $html .= "</tr>";
                                    $html .= "</table>";
                                    $html .= "<div style='height:5px;background-color:#8E1EC0;'></div>";
                                    $html .= "</div>"; // end .bts-header
                                    $html .= "<div class='bts-valor' style='text-align:center;'>";
                                        $html .= "<h2 style='margin-bottom:5px;'>R$ ".number_format($transaction->final_amount, 2, ',','.')."</h2>";
                                        $html .= "<span>";
                                            $html .= "<span style='font-size:12px;' ><b>Realizado em ".date('d/m/Y', strtotime($transaction->final_date))." às ".date('H:i:s', strtotime($transaction->final_date))."</b></span>";
                                        $html .= "</span><br>";
                                    $html .= "<br></div>"; // end .bts-valor

                                    $html .= "<hr />";

                                    $html .= "<div class='bts-block-text' style='display: block'>";

                                        $html .= "<h3>DADOS DO DESTINATÁRIO</h3>";
                                        $html .= "<table class='table' width='100%'>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>Nome:</div></td>";
                                                $html .= "<td><div class='value'>".$nome."</div></td>";
                                            $html .= "</tr>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>CPF/CNPJ:</div></td>";
                                                $html .= "<td><div class='value'>".$cpfCnpj."</div></td>";
                                            $html .= "</tr>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>Instituição:</div></td>";
                                                $html .= "<td><div class='value'>".$banco."</div></td>";
                                            $html .= "</tr>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>Agência:</div></td>";
                                                $html .= "<td><div class='value'>".$agencia."</div></td>";
                                            $html .= "</tr>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>Conta:</div></td>";
                                                $html .= "<td><div class='value'>".hyphenate($conta)."</div></td>";
                                            $html .= "</tr>";
                                        $html .= "</table>";

                                    $html .= "</div>"; // end .bts-block-text

                                    $html .= "<div class='bts-block-text' style='display: block'>";

                                        $html .= "<h3>DADOS DO PAGADOR</h3>";
                                        $html .= "<table class='table' width='100%'>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>Nome:</div></td>";
                                                $html .= "<td><div class='value'>LAMERICA SYSTEM TECNOLOGIA LTDA</div></td>";
                                            $html .= "</tr>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>CPF/CNPJ:</div></td>";
                                                $html .= "<td><div class='value'>40.121.024/0001-13</div></td>";
                                            $html .= "</tr>";
                                        $html .= "</table>";

                                    $html .= "</div>"; // end .bts-block-text

                                    $html .= "<hr />";

                                    $html .= "<div class='bts-codes' style='display: block; text-align:center;'>"; // .codes
                                        $html .= "<div style='text-align:center;'>ID da Transação</div>";
                                        $html .= "<h4 style='text-align:center;'>".$e2e."</h4>";
                                    $html .= "</div>"; //end .codes

                                    $html .= "<div style='background-color: #8E1EC0; text-align:center;padding: 15px; color: #fff !important; font-weight: normal !important'>"; // .footer
                                        $html .= "(Atendimento de segunda a sexta, exceto feriados, das 8h às 20h)  <br><br>";
                                        $html .= "Capitais e Regiões Metropolitanas: 3003-0000 <br>";
                                        $html .= "Demais localidades: 0800 123 1234 <br>";
                                        $html .= "Ouvidoria: 0800 321 4321";
                                    $html .= "</div>"; //end .footer


                                $html .= "</div>"; // end .bts-comprovante
                            $html .= "</div>"; //end .bts-paper
                        $html .= "</div>"; // end .comprovante-conteudo
                    $html .= "</div>"; // end .bts-comprovante
                $html .= "</div>"; //end .bts-comprovant-container
            $html .= "</div></body></html>";

        }elseif($bankWithdraw->code == "845"){

            $webhookData = Webhook::where("client_id", $transaction->client_id)->where("order_id",$transaction->order_id)->first();

            $user_account_data = json_decode(base64_decode($transaction->user_account_data),true);

            $pixInfoAccount = json_decode($webhookData->body,true);

            if($transaction->type_transaction == "deposit"){
                $receiver_name = $bankWithdraw->holder;
                $receiver_document = formatCnpjCpf($bankWithdraw->document);
                $receiver_bank = $bankWithdraw->name;

                $payer_name = $pixInfoAccount['data']['debtorAccount']['name'];
                $payer_document = formatCnpjCpf($pixInfoAccount['data']['debtorAccount']['document']);

                $bankISPB = $pixInfoAccount['data']['debtorAccount']['ispb'];
                $bankAccountName = BankISPB::where("ispb",$bankISPB)->first();
                if(!isset($bankAccountName)){ $payer_bank = "BANCO NÃO INFORMADO"; }else{ $payer_bank = $bankAccountName->name; }

            }else{
                $payer_name = $bankWithdraw->holder;
                $payer_document = formatCnpjCpf($bankWithdraw->document);
                $payer_bank = $bankWithdraw->name;

                $receiver_name = $pixInfoAccount['data']['creditorAccount']['name'];
                $receiver_document = formatCnpjCpf($pixInfoAccount['data']['creditorAccount']['document']);

                $bankISPB = $pixInfoAccount['data']['creditorAccount']['ispb'];
                $bankAccountName = BankISPB::where("ispb",$bankISPB)->first();
                if(!isset($bankAccountName)){ $receiver_bank = "BANCO NÃO INFORMADO"; }else{ $receiver_bank = $bankAccountName->name; }
            }

            $e2e = $pixInfoAccount['data']['endToEndId'];

            $nomeBranco = preg_replace('/[ -]+/' , '-' , $user_account_data['name']);
            $file = $nomeBranco."_".$transaction->id;
            $filename = $file.".pdf";

            $mpdf = new Mpdf(['mode' => 'utf-8','format' => 'A4','margin_left' => 30,'margin_right' => 30,'margin_top' => 0,'margin_bottom' => 0,'margin_header' => 0,'margin_footer' => 0]);

            $html = "<!DOCTYPE html><html lang='pt'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>".$filename."</title>";
            $html .= "<link rel='preconnect' href='https://fonts.googleapis.com'><link rel='preconnect' href='https://fonts.gstatic.com' crossorigin><link href='https://fonts.googleapis.com/css2?family=Roboto:wght@300&display=swap' rel='stylesheet'>";
            $html .= "<style type='text/css'>body{color: #595959; font-family: 'Roboto', sans-serif !important;}h1,h2,h3,h4,h5{color:#8E1EC0;}</style>";
            $html .= "</head><body>";
            $html .= "<div><br/><br/>";
                $html .= "<div class='bts-comprovante-container'>";
                    $html .= "<div class='bts-comprovante'>";
                        $html .= "<div style='display: block' class='comprovante-conteudo'>";
                            $html .= "<div class='bts-paper'>";
                                $html .= "<div class='bts-comprovante'>";
                                    $html .= "<div class='bts-header'>";
                                    $html .= "<table class='table' width='100%'>";
                                        $html .= "<tr>";
                                            $html .= "<td witdth='50%'><img src='https://admin.fastpayments.com.br/img/fast-payments-logo.png' width='200'></td>";
                                            $html .= "<td witdth='50%' align='right' style='text-align:right;'><h2 class='tipo'>Comprovante <br> de Pagamento Pix</h2></td>"; // end .tipo
                                        $html .= "</tr>";
                                    $html .= "</table>";
                                    $html .= "<div style='height:5px;background-color:#8E1EC0;'></div>";
                                    $html .= "</div>"; // end .bts-header
                                    $html .= "<div class='bts-valor' style='text-align:center;'>";
                                        $html .= "<h2 style='margin-bottom:5px;'>R$ ".number_format($transaction->amount_solicitation, 2, ',','.')."</h2>";
                                        $html .= "<span>";
                                            $html .= "<span style='font-size:12px;' ><b>Realizado em ".date('d/m/Y', strtotime($transaction->final_date))." às ".date('H:i:s', strtotime($transaction->final_date))."</b></span>";
                                        $html .= "</span><br>";
                                    $html .= "<br></div>"; // end .bts-valor

                                    $html .= "<hr />";

                                    $html .= "<div class='bts-block-text' style='display: block'>";

                                        $html .= "<h3>DADOS DO DESTINATÁRIO</h3>";
                                        $html .= "<table class='table' width='100%'>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>Nome:</div></td>";
                                                $html .= "<td><div class='value'>".$receiver_name."</div></td>";
                                            $html .= "</tr>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>CPF/CNPJ:</div></td>";
                                                $html .= "<td><div class='value'>".$receiver_document."</div></td>";
                                            $html .= "</tr>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>Instituição:</div></td>";
                                                $html .= "<td><div class='value'>".$receiver_bank."</div></td>";
                                            $html .= "</tr>";
                                            // $html .= "<tr>";
                                            //     $html .= "<td><div class='label'>Agência:</div></td>";
                                            //     $html .= "<td><div class='value'>".$agencia."</div></td>";
                                            // $html .= "</tr>";
                                            // $html .= "<tr>";
                                            //     $html .= "<td><div class='label'>Conta:</div></td>";
                                            //     $html .= "<td><div class='value'>".hyphenate($conta)."</div></td>";
                                            // $html .= "</tr>";
                                        $html .= "</table>";

                                    $html .= "</div>"; // end .bts-block-text

                                    $html .= "<div class='bts-block-text' style='display: block'>";

                                        $html .= "<h3>DADOS DO PAGADOR</h3>";
                                        $html .= "<table class='table' width='100%'>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>Nome:</div></td>";
                                                $html .= "<td><div class='value'>".$payer_name."</div></td>";
                                            $html .= "</tr>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>CPF/CNPJ:</div></td>";
                                                $html .= "<td><div class='value'>".$payer_document."</div></td>";
                                            $html .= "</tr>";
                                            $html .= "<tr>";
                                                $html .= "<td><div class='label'>Instituição:</div></td>";
                                                $html .= "<td><div class='value'>".$payer_bank."</div></td>";
                                            $html .= "</tr>";
                                        $html .= "</table>";

                                    $html .= "</div>"; // end .bts-block-text

                                    $html .= "<hr />";

                                    $html .= "<div class='bts-codes' style='display: block; text-align:center;'>"; // .codes
                                        $html .= "<div style='text-align:center;'>ID da Transação</div>";
                                        $html .= "<h4 style='text-align:center;'>".$e2e."</h4>";
                                    $html .= "</div>"; //end .codes

                                    $html .= "<div style='background-color: #8E1EC0; text-align:center;padding: 15px; color: #fff !important; font-weight: normal !important'>"; // .footer
                                        $html .= "(Atendimento de segunda a sexta, exceto feriados, das 8h às 20h)  <br><br>";
                                        $html .= "Capitais e Regiões Metropolitanas: 3003-0000 <br>";
                                        $html .= "Demais localidades: 0800 123 1234 <br>";
                                        $html .= "Ouvidoria: 0800 321 4321";
                                    $html .= "</div>"; //end .footer


                                $html .= "</div>"; // end .bts-comprovante
                            $html .= "</div>"; //end .bts-paper
                        $html .= "</div>"; // end .comprovante-conteudo
                    $html .= "</div>"; // end .bts-comprovante
                $html .= "</div>"; //end .bts-comprovant-container
            $html .= "</div></body></html>";

        }



        $mpdf->WriteHTML($html);


       $mpdf->Output();
       //$mpdf->Output($filename, 'D');
    }

    public function bacen($id){




        $transaction = Transactions::where('id', $id)
                                    ->where('status', 'confirmed')
                                    ->where('type_transaction', 'withdraw')
                                    ->where('method_transaction', 'pix')->get();







        $id = $transaction->payment_id;

        $token = 'Token: 2a2b6e7e7b32e6f1810569ba951460f47132a95d';
        $url = "https://jhfu5s9iz1.execute-api.us-east-2.amazonaws.com/A4PV3/getinfopix?payment_id=$id";

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            $token
        ),
        ));

        $response = json_decode(curl_exec($curl), true);

        curl_close($curl);


        $status = ($response['status'] == 'Efetivada') ? 'Confirmed' : 'Refused';
        $dados = array(
            'e2e' => $response['endToEndId'],
            'status' => $status,
            'agencia' => $response['recebedor']['agencia'],
            'conta' => $response['recebedor']['conta'],
            'cpfCnpj' => formatCnpjCpf($response['recebedor']['cpfCnpj']),
            'nome' => $response['recebedor']['nome'],
            'banco' => nameBank($response['recebedor']['ispb'])
        );


        return json_encode($dados, true);




    }

    public function getPixInfoCelcoin($pix_key,$access_token_celcoin){

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

    public function getPixInfo($pix_key){

        $data = [
            "Method" => "GetInfosPixKey",
            "PartnerId" => 1059,
            "BusinessUnitId" => 1075,
            "PixKey" => $pix_key,
            "PixKeyType" => "0",
            "TaxNumber" => "46803613000168"
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apiv2.fitbank.com.br/main/execute',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Basic YzlmNjRmYzEtOWJjNi00Y2QxLTg1YjctM2U2OWFiYzgwZGE2OjRiNjhlYzJiLTc2Y2MtNGM2OS05ODgyLTU3YzhhZGFmNjI3Yw=='
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;

    }


}

function formatCnpjCpf($value)
{
  $CPF_LENGTH = 11;
  $cnpj_cpf = preg_replace("/\D/", '', $value);

  if (strlen($cnpj_cpf) === $CPF_LENGTH) {
    return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cnpj_cpf);
  }

  return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpj_cpf);
}


function hyphenate($str) {

    $count = strlen($str);
    $count = $count - 1;
    return join('-', str_split($str, $count));
}



function nameBank($ispb){
    switch($ispb) {
        case "00000000":
            $bank = "BANCO DO BRASIL S.A.";
            break;
        case "00000208":
            $bank = "BRB - BANCO DE BRASILIA S.A.";
            break;
        case "00360305":
            $bank = "CAIXA ECONOMICA FEDERAL";
            break;
        case "00416968":
            $bank = "BANCO INTER S.A.";
            break;
        case "00517645":
            $bank = "BANCO RIBEIRAO PRETO S.A.";
            break;
        case "00556603":
            $bank = "BANCO BARI DE INVESTIMENTOS E FINANCIAMENTOS S.A.";
            break;
        case "00558456":
            $bank = "BANCO CETELEM S.A.";
            break;
        case "00795423":
            $bank = "BANCO SEMEAR S.A.";
            break;
        case "00997185":
            $bank = "BANCO B3 S.A.";
            break;
        case "01023570":
            $bank = "BANCO RABOBANK INTERNATIONAL BRASIL S.A.";
            break;
        case "01181521":
            $bank = "BANCO COOPERATIVO SICREDI S.A.";
            break;
        case "01522368":
            $bank = "BANCO BNP PARIBAS BRASIL S.A.";
            break;
        case "01701201":
            $bank = "KIRTON BANK S.A. - BANCO MÚLTIPLO";
            break;
        case "01852137":
            $bank = "BANCO BRASILEIRO DE CRÉDITO SOCIEDADE ANÔNIMA";
            break;
        case "01858774":
            $bank = "BANCO BV S.A.";
            break;
        case "02038232":
            $bank = "BANCO COOPERATIVO SICOOB S.A. - BANCO SICOOB";
            break;
        case "02318507":
            $bank = "BANCO KEB HANA DO BRASIL S.A.";
            break;
        case "02658435":
            $bank = "BANCO CATERPILLAR S.A.";
            break;
        case "02801938":
            $bank = "BANCO MORGAN STANLEY S.A.";
            break;
        case "02992446":
            $bank = "BANCO CNH INDUSTRIAL CAPITAL S.A.";
            break;
        case "03012230":
            $bank = "HIPERCARD BANCO MÚLTIPLO S.A.";
            break;
        case "03017677":
            $bank = "BANCO J. SAFRA S.A.";
            break;
        case "03215790":
            $bank = "BANCO TOYOTA DO BRASIL S.A.";
            break;
        case "03323840":
            $bank = "BANCO ALFA S.A.";
            break;
        case "03502961":
            $bank = "BANCO PSA FINANCE BRASIL S.A.";
            break;
        case "03532415":
            $bank = "BANCO ABN AMRO S.A.";
            break;
        case "03609817":
            $bank = "BANCO CARGILL S.A.";
            break;
        case "03634220":
            $bank = "BANCO HONDA S.A.";
            break;
        case "04184779":
            $bank = "BANCO BRADESCARD S.A.                                                                                                                                    ";
            break;
        case "04332281":
            $bank = "GOLDMAN SACHS DO BRASIL BANCO MULTIPLO S.A.                                                                                                              ";
            break;
        case "04814563":
            $bank = "BANCO AFINZ S.A. - BANCO MÚLTIPLO                                                                                                                        ";
            break;
        case "04866275":
            $bank = "BANCO INBURSA S.A.                                                                                                                                       ";
            break;
        case "04902979":
            $bank = "BANCO DA AMAZONIA S.A.                                                                                                                                   ";
            break;
        case "04913711":
            $bank = "BANCO DO ESTADO DO PARÁ S.A.                                                                                                                             ";
            break;
        case "05040481":
            $bank = "BANCO DE LAGE LANDEN BRASIL S.A.                                                                                                                         ";
            break;
        case "06271464":
            $bank = "BANCO BRADESCO BBI S.A.                                                                                                                                  ";
            break;
        case "07207996":
            $bank = "BANCO BRADESCO FINANCIAMENTOS S.A.                                                                                                                       ";
            break;
        case "07237373":
            $bank = "BANCO DO NORDESTE DO BRASIL S.A.                                                                                                                         ";
            break;
        case "07441209":
            $bank = "BANCO MONEO S.A.                                                                                                                                         ";
            break;
        case "07450604":
            $bank = "CHINA CONSTRUCTION BANK (BRASIL) BANCO MÚLTIPLO S/A                                                                                                      ";
            break;
        case "07656500":
            $bank = "BANCO KDB DO BRASIL S.A.                                                                                                                                 ";
            break;
        case "07679404":
            $bank = "BANCO TOPÁZIO S.A.                                                                                                                                       ";
            break;
        case "08357240":
            $bank = "BANCO CSF S.A.                                                                                                                                           ";
            break;
        case "08609934":
            $bank = "MONEYCORP BANCO DE CÂMBIO S.A.                                                                                                                           ";
            break;
        case "09274232":
            $bank = "STATE STREET BRASIL S.A. - BANCO COMERCIAL                                                                                                               ";
            break;
        case "09516419":
            $bank = "PICPAY BANK - BANCO MÚLTIPLO S.A                                                                                                                         ";
            break;
        case "09526594":
            $bank = "BANCO MASTER DE INVESTIMENTO S.A.                                                                                                                        ";
            break;
        case "10264663":
            $bank = "BANCOSEGURO S.A.                                                                                                                                         ";
            break;
        case "10371492":
            $bank = "BANCO YAMAHA MOTOR DO BRASIL S.A.                                                                                                                        ";
            break;
		case "10573521":
            $bank = "MERCADOPAGO.COM REPRESENTACOES LTDA.                                                                                                                        ";
            break;
        case "10664513":
            $bank = "BANCO AGIBANK S.A.                                                                                                                                       ";
            break;
        case "10690848":
            $bank = "BANCO DA CHINA BRASIL S.A.                                                                                                                               ";
            break;
        case "10866788":
            $bank = "BANCO BANDEPE S.A.                                                                                                                                       ";
            break;
        case "11417016":
            $bank = "SCANIA BANCO S.A.                                                                                                                                        ";
            break;
        case "11476673":
            $bank = "BANCO RANDON S.A.                                                                                                                                        ";
            break;
        case "11703662":
            $bank = "TRAVELEX BANCO DE CÂMBIO S.A.                                                                                                                            ";
            break;
        case "11758741":
            $bank = "BANCO FINAXIS S.A.                                                                                                                                       ";
            break;
        case "11970623":
            $bank = "BANCO SENFF S.A.                                                                                                                                         ";
            break;
        case "13009717":
            $bank = "BANCO DO ESTADO DE SERGIPE S.A.                                                                                                                          ";
            break;
        case "13059145":
            $bank = "BEXS BANCO DE CÂMBIO S/A                                                                                                                                 ";
            break;
        case "13720915":
            $bank = "BANCO WESTERN UNION DO BRASIL S.A.                                                                                                                       ";
            break;
        case "14388334":
            $bank = "PARANÁ BANCO S.A.                                                                                                                                        ";
            break;
        case "15114366":
            $bank = "BANCO BOCOM BBM S.A.                                                                                                                                     ";
            break;
        case "15124464":
            $bank = "BANCO BESA S.A.                                                                                                                                          ";
            break;
        case "15173776":
            $bank = "SOCIAL BANK BANCO MÚLTIPLO S/A                                                                                                                           ";
            break;
        case "15357060":
            $bank = "BANCO WOORI BANK DO BRASIL S.A.                                                                                                                          ";
            break;
        case "17184037":
            $bank = "BANCO MERCANTIL DO BRASIL S.A.                                                                                                                           ";
            break;
        case "17192451":
            $bank = "BANCO ITAUCARD S.A.                                                                                                                                      ";
            break;
        case "17298092":
            $bank = "BANCO ITAÚ BBA S.A.                                                                                                                                      ";
            break;
        case "17351180":
            $bank = "BANCO TRIANGULO S.A.                                                                                                                                     ";
            break;
        case "17453575":
            $bank = "ICBC DO BRASIL BANCO MÚLTIPLO S.A.                                                                                                                       ";
            break;
        case "18236120":
            $bank = "NUBANK.                                                                                                                       ";
            break;
        case "19307785":
            $bank = "MS BANK S.A. BANCO DE CÂMBIO                                                                                                                             ";
            break;
        case "23511655":
            $bank = "DEUTSCHE SPARKASSEN LEASING DO BRASIL BANCO MÚLTIPLO S.A.                                                                                                ";
            break;
        case "23522214":
            $bank = "COMMERZBANK BRASIL S.A. - BANCO MÚLTIPLO                                                                                                                 ";
            break;
        case "23903068":
            $bank = "BANCO KOMATSU DO BRASIL S.A.                                                                                                                             ";
            break;
        case "27098060":
            $bank = "BANCO DIGIO S.A.                                                                                                                                         ";
            break;
        case "28127603":
            $bank = "BANESTES S.A. BANCO DO ESTADO DO ESPIRITO SANTO                                                                                                          ";
            break;
        case "28195667":
            $bank = "BANCO ABC BRASIL S.A.                                                                                                                                    ";
            break;
        case "28517628":
            $bank = "BANCO PACCAR S.A.                                                                                                                                        ";
            break;
        case "28811341":
            $bank = "STONEX BANCO DE CÂMBIO S.A.                                                                                                                              ";
            break;
        case "29030467":
            $bank = "SCOTIABANK BRASIL S.A. BANCO MÚLTIPLO                                                                                                                    ";
            break;
        case "30172491":
            $bank = "BANCO HYUNDAI CAPITAL BRASIL S.A.                                                                                                                        ";
            break;
        case "30306294":
            $bank = "BANCO BTG PACTUAL S.A.                                                                                                                                   ";
            break;
        case "30723886":
            $bank = "BANCO MODAL S.A.                                                                                                                                         ";
            break;
        case "31597552":
            $bank = "BANCO CLASSICO S.A.                                                                                                                                      ";
            break;
        case "31872495":
            $bank = "BANCO C6 S.A.                                                                                                                                            ";
            break;
        case "31880826":
            $bank = "BANCO GUANABARA S.A.                                                                                                                                     ";
            break;
        case "31895683":
            $bank = "BANCO INDUSTRIAL DO BRASIL S.A.                                                                                                                          ";
            break;
        case "32062580":
            $bank = "BANCO CREDIT SUISSE (BRASIL) S.A.                                                                                                                        ";
            break;
        case "33042151":
            $bank = "BANCO DE LA NACION ARGENTINA                                                                                                                             ";
            break;
        case "33042953":
            $bank = "CITIBANK N.A.                                                                                                                                            ";
            break;
        case "33132044":
            $bank = "BANCO CEDULA S.A.                                                                                                                                        ";
            break;
        case "33147315":
            $bank = "BANCO BRADESCO BERJ S.A.                                                                                                                                 ";
            break;
        case "33172537":
            $bank = "BANCO J.P. MORGAN S.A.                                                                                                                                   ";
            break;
        case "33254319":
            $bank = "BANCO LOSANGO S.A. - BANCO MÚLTIPLO                                                                                                                      ";
            break;
        case "33264668":
            $bank = "BANCO XP S.A.                                                                                                                                            ";
            break;
        case "33466988":
            $bank = "BANCO CAIXA GERAL - BRASIL S.A.                                                                                                                          ";
            break;
        case "33479023":
            $bank = "BANCO CITIBANK S.A.                                                                                                                                      ";
            break;
        case "33603457":
            $bank = "BANCO RODOBENS S.A.                                                                                                                                      ";
            break;
        case "33644196":
            $bank = "BANCO FATOR S.A.                                                                                                                                         ";
            break;
        case "33885724":
            $bank = "BANCO ITAÚ CONSIGNADO S.A.                                                                                                                               ";
            break;
        case "33923798":
            $bank = "BANCO MASTER S/A                                                                                                                                         ";
            break;
        case "34270520":
            $bank = "BANCO IBM S.A.                                                                                                                                           ";
            break;
        case "36658769":
            $bank = "BANCO XCMG BRASIL S.A.                                                                                                                                   ";
            break;
        case "42272526":
            $bank = "BNY MELLON BANCO S.A.                                                                                                                                    ";
            break;
        case "43818780":
            $bank = "DAYCOVAL LEASING - BANCO MÚLTIPLO S.A.                                                                                                                   ";
            break;
        case "44189447":
            $bank = "BANCO DE LA PROVINCIA DE BUENOS AIRES                                                                                                                    ";
            break;
        case "45246410":
            $bank = "BANCO GENIAL S.A.                                                                                                                                        ";
            break;
        case "46518205":
            $bank = "JPMORGAN CHASE BANK, NATIONAL ASSOCIATION                                                                                                                ";
            break;
        case "48795256":
            $bank = "BANCO ANDBANK (BRASIL) S.A.                                                                                                                              ";
            break;
        case "50585090":
            $bank = "BCV - BANCO DE CRÉDITO E VAREJO S.A.                                                                                                                     ";
            break;
        case "53518684":
            $bank = "BANCO HSBC S.A.                                                                                                                                          ";
            break;
        case "54403563":
            $bank = "BANCO ARBI S.A.                                                                                                                                          ";
            break;
        case "55230916":
            $bank = "INTESA SANPAOLO BRASIL S.A. - BANCO MÚLTIPLO                                                                                                             ";
            break;
        case "57839805":
            $bank = "BANCO TRICURY S.A.                                                                                                                                       ";
            break;
        case "58017179":
            $bank = "BANCO VOLVO BRASIL S.A.                                                                                                                                  ";
            break;
        case "58160789":
            $bank = "BANCO SAFRA S.A.                                                                                                                                         ";
            break;
        case "58497702":
            $bank = "BANCO LETSBANK S.A.                                                                                                                                      ";
            break;
        case "58616418":
            $bank = "BANCO FIBRA S.A.                                                                                                                                         ";
            break;
        case "59109165":
            $bank = "BANCO VOLKSWAGEN S.A.                                                                                                                                    ";
            break;
        case "59118133":
            $bank = "BANCO LUSO BRASILEIRO S.A.                                                                                                                               ";
            break;
        case "59274605":
            $bank = "BANCO GM S.A.                                                                                                                                            ";
            break;
        case "59285411":
            $bank = "BANCO PAN S.A.                                                                                                                                           ";
            break;
        case "59588111":
            $bank = "BANCO VOTORANTIM S.A.                                                                                                                                    ";
            break;
        case "60394079":
            $bank = "BANCO ITAUBANK S.A.                                                                                                                                      ";
            break;
        case "60498557":
            $bank = "BANCO MUFG BRASIL S.A.                                                                                                                                   ";
            break;
        case "60518222":
            $bank = "BANCO SUMITOMO MITSUI BRASILEIRO S.A.                                                                                                                    ";
            break;
        case "60701190":
            $bank = "ITAÚ UNIBANCO S.A.                                                                                                                                       ";
            break;
        case "60746948":
            $bank = "BANCO BRADESCO S.A.                                                                                                                                      ";
            break;
        case "60814191":
            $bank = "BANCO MERCEDES-BENZ DO BRASIL S.A.                                                                                                                       ";
            break;
        case "60850229":
            $bank = "OMNI BANCO S.A.                                                                                                                                          ";
            break;
        case "60872504":
            $bank = "ITAÚ UNIBANCO HOLDING S.A.                                                                                                                               ";
            break;
        case "60889128":
            $bank = "BANCO SOFISA S.A.                                                                                                                                        ";
            break;
        case "61024352":
            $bank = "BANCO VOITER S.A.                                                                                                                                        ";
            break;
        case "61033106":
            $bank = "BANCO CREFISA S.A.                                                                                                                                       ";
            break;
        case "61088183":
            $bank = "BANCO MIZUHO DO BRASIL S.A.                                                                                                                              ";
            break;
        case "61182408":
            $bank = "BANCO INVESTCRED UNIBANCO S.A.                                                                                                                           ";
            break;
        case "61186680":
            $bank = "BANCO BMG S.A.                                                                                                                                           ";
            break;
        case "61190658":
            $bank = "BANCO ITAÚ VEÍCULOS S.A.                                                                                                                                 ";
            break;
        case "61348538":
            $bank = "BANCO C6 CONSIGNADO S.A.                                                                                                                                 ";
            break;
        case "61533584":
            $bank = "BANCO SOCIETE GENERALE BRASIL S.A.                                                                                                                       ";
            break;
        case "61820817":
            $bank = "BANCO PAULISTA S.A.                                                                                                                                      ";
            break;
        case "62073200":
            $bank = "BANK OF AMERICA MERRILL LYNCH BANCO MÚLTIPLO S.A.                                                                                                        ";
            break;
        case "62144175":
            $bank = "BANCO PINE S.A.                                                                                                                                          ";
            break;
        case "62232889":
            $bank = "BANCO DAYCOVAL S.A.                                                                                                                                      ";
            break;
        case "62237425":
            $bank = "BANCO FIDIS S/A                                                                                                                                          ";
            break;
        case "62307848":
            $bank = "BANCO RCI BRASIL S.A.                                                                                                                                    ";
            break;
        case "62331228":
            $bank = "DEUTSCHE BANK S.A. - BANCO ALEMAO                                                                                                                        ";
            break;
        case "62421979":
            $bank = "BANCO CIFRA S.A.                                                                                                                                         ";
            break;
        case "68900810":
            $bank = "BANCO RENDIMENTO S.A.                                                                                                                                    ";
            break;
        case "71027866":
            $bank = "BANCO BS2 S.A.                                                                                                                                           ";
            break;
        case "74828799":
            $bank = "NOVO BANCO CONTINENTAL S.A. - BANCO MÚLTIPLO                                                                                                             ";
            break;
        case "75647891":
            $bank = "BANCO CRÉDIT AGRICOLE BRASIL S.A.                                                                                                                        ";
            break;
        case "76543115":
            $bank = "BANCO SISTEMA S.A.                                                                                                                                       ";
            break;
        case "78626983":
            $bank = "BANCO VR S.A.                                                                                                                                            ";
            break;
        case "78632767":
            $bank = "BANCO OURINVEST S.A.                                                                                                                                     ";
            break;
        case "80271455":
            $bank = "BANCO RNX S.A.                                                                                                                                           ";
            break;
        case "90400888":
            $bank = "BANCO SANTANDER (BRASIL) S.A.                                                                                                                            ";
            break;
        case "91884981":
            $bank = "BANCO JOHN DEERE S.A.                                                                                                                                    ";
            break;
        case "92702067":
            $bank = "BANCO DO ESTADO DO RIO GRANDE DO SUL S.A.                                                                                                                ";
            break;
        case "92874270":
            $bank = "BANCO DIGIMAIS S.A.                                                                                                                                      ";
            break;
        case "92894922":
            $bank = "BANCO ORIGINAL S.A.                                                                                                                                      ";
            break;
        case "19540550":
            $bank = "ASAAS GESTAO FINANCEIRA INSTITUICAO DE PAGAMENTO S.A.";
            break;
        case "16501555":
            $bank = "STONE INSTITUICAO DE PAGAMENTO S.A";
            break;
        default:
            $bank = "BANCO NÃO INFORMADO";
            break;
    }


    return $bank;
}
