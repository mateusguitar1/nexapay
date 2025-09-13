<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Models\{Clients,Keys,Transactions,DataInvoice};
use App\Http\Controllers\FunctionsAPIController;
use App\Http\Controllers\FunctionsBBController;

class MethodsViewsController extends Controller
{
    //

    public function qrPix(Request $request,$id,$order_id,$dimension = null){

        $transaction = Transactions::where("id","=",$id)
            ->where("order_id","=",$order_id)
            ->first();

        if($dimension != null){
            $width = explode("x",$dimension)[0];
            $height = explode("x",$dimension)[1];
        }else{
            $width = "200";
            $height = "200";
        }

        if(!empty($transaction)){

            $data = [
                "qrpix" => $transaction->bank_data,
                "width" => $width,
                "height" => $height,
            ];

            return view('qrcode.qrCode',compact('data'));
        }

    }

    public function invoiceGlobal(Request $request,$authorization,$order_id){

        $FunctionsController = new FunctionsController();
        $FunctionsBBController = new FunctionsBBController();

        $authentication = Keys::where("authorization","=",$authorization)->first();
        $client = $authentication->client;
        $transaction = Transactions::where("client_id","=",$client->id)->where("order_id","=",$order_id)->first();

        $dados_user = json_decode(base64_decode($transaction['user_account_data']));
        $valor_cobrado_clean = $transaction['amount_solicitation'];
        $valor_cobrado_modif = str_replace(".","",$transaction['amount_solicitation']);
        $codigo_pedido = $transaction['code'];

        $days_sum = $client->days_safe_shop;

        $nosso_numero = $transaction['data_bank'];
        $numero_documento = $codigo_pedido;
        $data_emissao = str_replace("/","",date("d/m/Y"));
        // $data_vencimento = str_replace("/","",SomarData(date("d/m/Y"),$days_sum,0,0));
        $data_vencimento = str_replace("/","",$FunctionsController->datetostr(substr($transaction['due_date'],0,10)));
        $pagador_documento = $FunctionsController->limpaCPF_CNPJ($dados_user->document);
        $pagador_nome = $FunctionsController->tirarAcentos($FunctionsController->trata_unicode($dados_user->name));
        $pagador_endereco = $FunctionsController->tirarAcentos($FunctionsController->trata_unicode($dados_user->address));
        $pagador_bairro = $FunctionsController->tirarAcentos($FunctionsController->trata_unicode($dados_user->district));
        $pagador_cidade = $FunctionsController->tirarAcentos($FunctionsController->trata_unicode($dados_user->city));
        $pagador_uf = $FunctionsController->tirarAcentos($FunctionsController->trata_unicode($dados_user->uf));
        $pagador_cep = $FunctionsController->tirarAcentos($FunctionsController->trata_unicode($dados_user->cep));

        $holder_bank = $client->bankInvoice->holder;
        $agency_bank = $client->bankInvoice->agency;
        $account_bank = $client->bankInvoice->account;
        $address_bank = $client->bankInvoice->address;
        $document_bank = $client->bankInvoice->document;
        $code_bank = $client->bankInvoice->code;

        $dataInvoice = DataInvoice::where("client_id","=",$transaction['client_id'])->where("order_id","=",$transaction['order_id'])->first();

        if(!empty($dataInvoice)){
            $codigo_de_barras = $dataInvoice->barcode;
            $linha_digitavel = $dataInvoice->barcode;
        }else{
            $codigo_de_barras = "";
            $linha_digitavel = "";
        }


        $f1_method = $client->key->boletofirst_method;
        $amount_f1_method = $client->key->minamount_boletofirst;

        ////////////////////////////////

        // DADOS DO BOLETO PARA O SEU CLIENTE
        $dias_de_prazo_para_pagamento = $days_sum;
        $taxa_boleto = 0;
        $data_venc = $FunctionsController->datetostr(substr($transaction['due_date'],0,10)); // Prazo de X dias OU informe data: "13/04/2006";
        $valor_cobrado = $valor_cobrado_clean; // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
        //$valor_cobrado = str_replace(",", ".",$valor_cobrado);
        $valor_boleto = number_format($valor_cobrado + $taxa_boleto, 2, ',', '');

        $dadosboleto["nosso_numero"] = $nosso_numero;
        $dadosboleto["numero_documento"] = $numero_documento;	// Num do pedido ou do documento
        $dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
        $dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
        $dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
        $dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

        // DADOS DO SEU CLIENTE
        $dadosboleto["sacado"] = $pagador_nome;
        $dadosboleto["endereco1"] = $pagador_endereco.", ".$pagador_bairro;
        $dadosboleto["endereco2"] = $pagador_cidade." - ".$pagador_uf." | CEP: ".$pagador_cep;

        // INFORMACOES PARA O CLIENTE
        // if($f1_method == "enable" && $valor_cobrado_clean >= $amount_f1_method){
        //     $dadosboleto["informacaopagamento1"] = "Liberação em até 1 hora ao pagar no internet bank ou aplicativo no celular dos principais bancos brasileiros.";
        //     $dadosboleto["informacaopagamento2"] = "Caixas eletrônicos em até 4 horas. Lotéricas e demais serviços de pagamento como Bancos Digitais, Wallets (PicPay, Mercado Pago, etc.) em até 1 dia útil.<br/>* Sujeito a variação de tempo devido a instabilidades na rede bancária.";
        // }else{

        // }

        $dadosboleto["informacaopagamento1"] = "Liberação em até 24 horas após o pagamento.";
        $dadosboleto["informacaopagamento2"] = "";
        $dadosboleto["informacaopagamento3"] = "<b>O pagamento deve ser realizado pela leitura ou digitação do código de barras.<br/>Transferência via (PIX/TED/DOC/TEF) não serão compensadas!</b>";

        $dadosboleto['observacoes_header'] = "Caso não consiga efetuar o pagamento imediatamente, por favor, aguarde 10 minutos para registro do mesmo junto a rede bancária.";

        $dadosboleto["demonstrativo1"] = "Crédito em ".$holder_bank;
        $dadosboleto["demonstrativo2"] = "";
        $dadosboleto["demonstrativo3"] = "";

        // INSTRUÇÕES PARA O CAIXA
        $dadosboleto["instrucoes1"] = "- Não receber apos vencimento nem valor menor que o do documento";
        $dadosboleto["instrucoes2"] = "- Não aceitar pagamento em cheque.";
        $dadosboleto["instrucoes3"] = "- ID Transação: ".$transaction['order_id'];
        $dadosboleto["instrucoes4"] = "";


        // DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
        $dadosboleto["quantidade"] = "";
        $dadosboleto["valor_unitario"] = "";
        $dadosboleto["aceite"] = "N";
        $dadosboleto["especie"] = "R$";
        $dadosboleto["especie_doc"] = "DM";


        // ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO BOLETO --------------- //

        // SEUS DADOS
        $dadosboleto["identificacao"] = "Crédito em ".$holder_bank;
        $dadosboleto["cpf_cnpj"] = $document_bank;
        $dadosboleto["endereco"] = $address_bank;
        $dadosboleto["cidade_uf"] = "";
        $dadosboleto["cedente"] = $holder_bank;
        $dadosboleto["code_bank"] = $code_bank;

        ///////////////////////////////

        // if($code_bank == "218"){

            $dadosboleto["codigo_barras"] = $FunctionsBBController->calcula_codigo_de_barras($linha_digitavel);
            $dadosboleto["linha_digitavel"] = $FunctionsBBController->monta_linha_digitavel($linha_digitavel);

        // }elseif($code_bank == "450"){

        //     $linha_digitavel_clear = str_replace(".","",$linha_digitavel);
        //     $linha_digitavel_clear = str_replace(" ","",$linha_digitavel_clear);

        //     $dadosboleto["codigo_barras"] = $FunctionsBBController->calcula_codigo_de_barras($linha_digitavel_clear);
        //     $dadosboleto["linha_digitavel"] = $linha_digitavel;

        // }

        return view('invoice.global')->with('dadosboleto',$dadosboleto);

    }
}
