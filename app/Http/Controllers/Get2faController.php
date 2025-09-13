<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use \Google\Authenticator\GoogleQrUrl;
use \Google\Authenticator\FixedBitNotation;
use \Google\Authenticator\GoogleAuthenticator;
use \Google\Authenticator\GoogleAuthenticatorInterface;

use App\Http\Controllers\FunctionsController;
use App\Models\{Clients,Transactions,Extract,Taxes,Keys,Banks,Logs,BankClientsAccount};

class Get2faController extends Controller
{
    //

    public function index(Request $request){
        $g = new GoogleAuthenticator();
        // $secret = 'XVQ2UIGO75XRUKJO';
        //Você pode usar o $g->generateSecret() para gerar o secret
        $secret = $g->generateSecret();

        //o método "getUrl" recebe como parâmetro: "username", "host" e a chave "secret"
        $url = $g->getURL('fastpayments', 'fastpayments.com.br', $secret);

        $data = [
            "url" => $url,
            "secret" => $secret
        ];

        return view("2fa.index",compact('data'));
    }

    public function get2fa(Request $request){
        $g = new GoogleAuthenticator();
        // $secret = 'XVQ2UIGO75XRUKJO';
        //Você pode usar o $g->generateSecret() para gerar o secret
        $secret = $g->generateSecret();

        //o método "getUrl" recebe como parâmetro: "username", "host" e a chave "secret"
        $url = $g->getURL(auth()->user()->id, 'fastpayments.com.br', $secret);

        $data = [
            "url" => $url,
            "secret" => $secret
        ];

        return response()->json($data);
    }

    public function check(Request $request){

        $g = new \Google\Authenticator\GoogleAuthenticator();
        $secret = 'MD24ARZX55WMN53P';

        $code = $request->code; //código de 6 dígitos gerados pelo app do Google Authenticator

        $today = date("Y-m-d 00:00:00");

        if($g->checkCode($secret, $code)){
            $ids = explode(",",$request->withdrawals_id);
            $transactions = Transactions::whereIn("id",$ids)
                ->where("status","pending")
                ->where("type_transaction","withdraw")
                ->where("method_transaction","pix")
                ->get();

            foreach($transactions as $transaction){

                $client = $transaction->client;

                $amount_withdraw = $transaction->amount_solicitation;

                $total_available = Extract::where("client_id",$client->id)
                    ->where("disponibilization_date","<=",$today)
                    ->sum("final_amount");

                // Select all withdraws pending
                $sql_all_withdraw_pending = Transactions::where("client_id",$client->id)->where("status","pending")->where("type_transaction","withdraw")->sum('amount_solicitation');
                if(!empty($sql_all_withdraw_pending[0])){
                    $total_withdraw_pending = $sql_all_withdraw_pending;
                }else{
                    $total_withdraw_pending = 0;
                }

                if($total_available > 0){
                    // return array("title" => "Success!", "message" => "total_available > 0", "status" => "success");
                    if(($total_available - $amount_withdraw) >= 0){
                        // return array("title" => "Success!", "message" => "total_available - amount_withdraw >= 0", "status" => "success");
                        if($client->withdraw_permition === true){
                            $id_bank_withdraw = $client->bank_withdraw_permition;
                            // return array("title" => "Success!", "message" => "client->withdraw_permition === true", "status" => "success");
                            $bank_withdraw = Banks::where("id",$id_bank_withdraw)->first();

                            if(!empty($bank_withdraw)){
                                // return array("title" => "Success!", "message" => "!empty(bank_withdraw)", "status" => "success");
                                if($bank_withdraw->withdraw_permition === true){
                                    // return array("title" => "Success!", "message" => "bank_withdraw->withdraw_permition === true", "status" => "success");
                                    if($transaction->method_transaction == "pix" && $bank_withdraw->code == "588"){
                                        // return array("title" => "Success!", "message" => "transaction->method_transaction == pix && bank_withdraw->code == 588", "status" => "success");

                                        // return array("title" => "Success!", "message" => "client->id != 81 And send to approve", "status" => "success");
                                        \App\Jobs\PerformWithdrawalPIXVoluti::dispatch($transaction->id,$bank_withdraw->id)->delay(now()->addSeconds('0'));

                                    }elseif($transaction->method_transaction == "pix" && $bank_withdraw->code == "587"){

                                        \App\Jobs\PerformWithdrawalPaymentPIXCelcoinTRUE::dispatch($transaction->id,$bank_withdraw->id)->delay(now()->addSeconds('5'));

                                    }elseif($transaction->method_transaction == "pix" && $bank_withdraw->code == "844"){

                                        \App\Jobs\PerformWithdrawalPIXHUBAPIANYNEW::dispatch($transaction->id,$bank_withdraw->id)->delay(now()->addSeconds('5'));

                                    }elseif($transaction->method_transaction == "pix" && $bank_withdraw->code == "845"){

                                        \App\Jobs\PerformWithdrawalPIXVolutiFILENEW::dispatch($transaction->id,$bank_withdraw->id)->delay(now()->addSeconds('5'));

                                    }elseif($transaction->method_transaction == "pix" && $bank_withdraw->code == "846"){

                                        \App\Jobs\PerformWithdrawalPIXLuxTakNewsts::dispatch($transaction->id,$bank_withdraw->id)->delay(now()->addSeconds('5'));

                                    }

                                }
                            }

                        }else{
                            // return array("message" => "Client not permition withdraw", "code" => "0443");
                        }

                    }else{
                        // return array("message" => "Withdrawal amount greater than your balance available", "code" => "0443");
                    }
                }else{
                    // return array("message" => "Withdrawal not allowed due to insufficient balance available", "code" => "0441");
                }

            }
            return array("title" => "Success!", "message" => "Execution request processed successfully!", "status" => "success");
        }else{
            return array("title" => "Error!", "message" => "Incorrect or expired code!", "status" => "error");
        }

    }
}
