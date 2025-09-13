<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\FunctionsAPIController;
use App\Models\{Transactions,User,Clients,Banks,Extract,Webhook,Logs};

class ExecuteAllWithdrawals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'executeallwithdrawals:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $FunctionsAPIController = new FunctionsAPIController();

        $transactions = Transactions::where("type_transaction","withdraw")
        // ->whereIn("client_id",[12,13])
        ->where("method_transaction","pix")
        ->where("status","pending")
        ->orderby("solicitation_date","DESC")
        // ->whereIn("client_id",['12'])
        ->get();

        $today = date("Y-m-d 00:00:00");

        if(!isset($transactions)){
            print_r("Not found withdrawals");
            print_r("\n");
        }else{
            print_r("Found withdrawals");
            print_r("\n");
        }

        foreach($transactions as $transaction){

            $orderIdSearch = $transaction->order_id;

            $log = Logs::where("client_id",$transaction->client_id)
                ->where("type","add")
                ->where("action","LIKE","%".$orderIdSearch."%")
                ->first();



            if(!isset($log)){

                $client = $transaction->client;

                $amount_withdraw = $transaction->amount_solicitation;

                $av_today = Extract::where("client_id",$client->id)
                    ->where("disponibilization_date","<=",$today)
                    ->sum("final_amount");

                print_r($av_today);
                print_r("\n");

                $total_available = Extract::where("client_id",$client->id)->where("disponibilization_date","<=",$today)->sum("final_amount");

                print_r($total_available);
                print_r("\n");

                // Select all withdraws pending
                $sql_all_withdraw_pending = Transactions::where("client_id",$client->id)->where("status","pending")->where("type_transaction","withdraw")->sum('amount_solicitation');
                if(!empty($sql_all_withdraw_pending[0])){
                    $total_withdraw_pending = $sql_all_withdraw_pending;
                }else{
                    $total_withdraw_pending = 0;
                }

                print_r($total_withdraw_pending);
                print_r("\n");

                if($total_available > 0){
                    print_r("av sup 0");
                    print_r("\n");
                    // if(($total_available - $total_withdraw_pending) > 0){
                        print_r("av - total_pending sup 0");
                        print_r("\n");
                        // if((($total_available - $total_withdraw_pending) - $amount_withdraw) >= 0){
                        if(($total_available - $amount_withdraw) >= 0){

                            print_r("can execute");
                            print_r("\n");

                            if($client->withdraw_permition === true){
                                $id_bank_withdraw = $client->bank_withdraw_permition;

                                $bank_withdraw = Banks::where("id",$id_bank_withdraw)->first();

                                if(!empty($bank_withdraw)){
                                    if($bank_withdraw->withdraw_permition === true){

                                        if($transaction->method_transaction == "pix" && $bank_withdraw->code == "587"){
                                            print_r("pass withdraw");
                                            print_r("\n");
                                            // \App\Jobs\PerformWithdrawalPaymentPIXCelcoin::dispatch($transaction->id,$bank_withdraw->id)->delay(now()->addSeconds('5'));
                                        }elseif($transaction->method_transaction == "pix" && $bank_withdraw->code == "588"){
                                            print_r("pass withdraw voluti");
                                            print_r("\n");
                                            // \App\Jobs\PerformWithdrawalPIXVoluti::dispatch($transaction->id,$bank_withdraw->id)->delay(now()->addSeconds('5'));
                                        }

                                        print_r("Transaction ID ".$transaction->id." send to withdraw \n");

                                    }
                                }

                            }

                        }else{
                            return array("message" => "Withdrawal amount greater than your balance available", "code" => "0443");
                        }
                    // }else{
                    //     return array("message" => "Insufficient balance available due to pending withdrawals", "code" => "0442");
                    // }
                }else{
                    return array("message" => "Withdrawal not allowed due to insufficient balance available", "code" => "0441");
                }
            }else{
                print_r("Have logs");
            }
        }

    }
}
