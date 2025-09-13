<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use App\Http\Controllers\FunctionsAPIController;
use App\Models\{Transactions,User,Clients,Banks,Extract,Webhook};

class SendWebhooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sendwebhooks:cron';

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
        $list = [];

        $date_start = date("Y-m-d 00:00:00");
        $date_end = date("Y-m-d 23:59:59");

        $client = Clients::where("id","18")->first();
        $url_callback = $client->key->url_callback;
        $token = $client->key->authorization;

        $transactions = Transactions::where("client_id","18")
        ->where("type_transaction","deposit")
        ->where("status","confirmed")
        ->whereBetween("solicitation_date",[$date_start,$date_end])->get();
        foreach($transactions as $transaction){

            $user_account_data = json_decode(base64_decode($transaction->user_account_data),true);

            $name_user = "";
            if(isset($user_account_data['name'])){
                if(empty($user_account_data['name'])){
                    $name_user = "";
                }else{
                    $name_user = $user_account_data['name'];
                }
            }

            $document = "";
            if(isset($user_account_data['document'])){
                if(empty($user_account_data['document'])){
                    $document = "";
                }else{
                    $document = $user_account_data['document'];
                }
            }

             // set post fields
             $post = [
                "order_id" => $transaction->order_id,
                "solicitation_date" => $transaction->solicitation_date,
                "user_id" => $transaction->user_id,
                "user_name" => $name_user,
                "user_document" => $document,
                "paid_date" => $transaction->paid_date,
                "code_identify" => $transaction->code,
                "type_transaction" => $transaction->type_transaction,
                "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
                "amount_confirmed" => number_format($transaction->final_amount,2,'.',''),
                "status" => $transaction->status,
                "comission" => $transaction->comission,
                "disponibilization_date" => date("d/m/Y 00:00:00",strtotime($transaction->disponibilization_date)),
            ];

            $post_field = json_encode($post);

            $ch = curl_init($url_callback);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'Token:'.$token));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

            // execute!
            $response = curl_exec($ch);
            $http_status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            // close the connection, release resources used
            curl_close($ch);

            // if($http_status == "200"){
            //     DB::beginTransaction();
            //     try{

            //         $transaction->update([
            //             "confirmation_callback" => "1"
            //         ]);

            //         DB::commit();

            //     }catch(exception $e){
            //         DB::rollback();
            //     }
            // }

            print_r("Send ORDER ID:".$transaction->order_id." / Client ".$client->name);
            print_r("\n");

            // $post_register = [
            //     "date_send" => date("Y-m-d H:i:s"),
            //     "response" => $response,
            //     "order_id" => $transaction->order_id,
            //     "solicitation_date" => $transaction->solicitation_date,
            //     "user_id" => $transaction->user_id,
            //     "user_name" => $user_account_data['name'],
            //     "user_document" => $user_account_data['document'],
            //     "paid_date" => $transaction->paid_date,
            //     "code_identify" => $transaction->code,
            //     "type_transaction" => $transaction->type_transaction,
            //     "amount_solicitation" => number_format($transaction->amount_solicitation,2,'.',''),
            //     "amount_confirmed" => number_format($transaction->final_amount,2,'.',''),
            //     "status" => $transaction->status,
            //     "comission" => $transaction->comission,
            //     "disponibilization_date" => date("d/m/Y 00:00:00",strtotime($transaction->disponibilization_date)),
            // ];

            // $path_name = "approvepay-webhook-send-callback-pagstar-".date("Y-m-d");

            // if (!file_exists('/var/www/html/approvepay/storage/logs/'.$path_name)) {
            //     mkdir('/var/www/html/approvepay/storage/logs/'.$path_name, 0777, true);
            // }

            // $FunctionsAPIController->registerRecivedsRequests("/var/www/html/approvepay/storage/logs/".$path_name."/log.txt",json_encode($post_register));

        }

    }

}
