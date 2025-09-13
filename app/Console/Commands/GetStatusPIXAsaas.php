<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use App\Http\Controllers\FunctionsAPIController;
use App\Models\{Transactions,User,Clients,Banks,Extract,Webhook};

class GetStatusPIXAsaas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'getstatuspixasaas:cron';

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

        $initial_date = date("Y-m-d 00:00:00");
        $final_date = date("Y-m-d 23:59:59");

        $transactions = Transactions::where("type_transaction","deposit")
            ->where("method_transaction","pix")
            ->where("status","pending")
            ->where("code_bank","461")
            ->whereBetween("solicitation_date",[$initial_date,$final_date])
            ->get();

        foreach($transactions as $transaction){

            $client = Clients::where("id","=",$transaction->client_id)->first();
            $access_token_asaas = $transaction->bank->access_token_asaas;

            $get_status_pix = json_decode($FunctionsAPIController->getStatusPIXAsaas($access_token_asaas,$transaction->payment_id),true);

            if($get_status_pix['status'] == "RECEIVED"){

                DB::beginTransaction();
                try{

                    $transaction->update([
                        "data_bank" => $get_status_pix['pixTransaction']
                    ]);

                    $webhook = Webhook::create([
                        "client_id" => $client->id,
                        "order_id" => $transaction->order_id,
                        "type_register" => "pix",
                        "body" => json_encode($get_status_pix,true),
                    ]);

                    DB::commit();

                    $transaction_id = $transaction->id;
                    $webhook_id = $webhook->id;

                    \App\Jobs\CheckHookPIXASAAS::dispatch($transaction_id,$webhook_id)->delay(now()->addSeconds('5'));

                    return response()->json(["message" => "success"]);

                }catch(exception $e){
                    DB::rollback();
                }

            }

        }
    }
}
