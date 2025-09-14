<?php

namespace App\Http\Middleware;

use DB;
use App\Http\Controllers\FunctionsAPIController;
use App\Models\{Clients,Keys,Transactions,BlockListBin};

use Closure;

class CheckDataDepositApiV3
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $FunctionsAPIController = new FunctionsAPIController();

        $authorizationAPI = $request->header('Token');
        $auth = Keys::where("authorization","=",$authorizationAPI)->first();

        if($auth){
            $client = $auth->client;
        }else{
            $client = Clients::where("id","8")->first();
        }

        $client_id = $client->id;

        if(!isset($request->method)){
            $json_return = array("message" => "Method not defined", "reason" => "Illegal Conditions", "code" => "0511");
            return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
            exit();
        }

        if(isset($request->card_number)){

            $cc = $request->card_number;
            $number_card_bd = substr($cc,0,6)."******".substr($cc,12,4);

            $checkBlockList = BlockListBin::where("card_bin",$number_card_bd)->count();

            if($checkBlockList > 0){

                $json_return = array("message" => "Card BIN ".$number_card_bd." blocked by FastPayments", "reason" => "Illegal Conditions", "code" => "0515");
                return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
                exit();

            }

        }

        $transaction = Transactions::where("client_id","=",$client->id)->where("order_id","=",$request->order_id)->first();
        if($transaction){

            // Order_id already exists
            $json_return = array("message" => "Order_id already exists", "reason" => "Illegal Conditions");
            return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
            exit();
        }else{

            // Check BLockList from CPF / USER_ID
            $block_list = DB::table('block_list')->where("client_id","=",$client_id)
            ->where(function ($query) use ($FunctionsAPIController,$request){
                $query->where("cpf","=",$FunctionsAPIController->clearCPF($request->user_document))
                    ->orWhere("user_id","=",$request->user_id);
            })
            ->where("blocked","=","true")
            ->get();

            if(!empty($block_list[0])){

                if($block_list[0]->cpf == $FunctionsAPIController->clearCPF($request->user_document)){
                    $json_return = array("message" => "User CPF ".$request->user_document." blocked by FastPayments", "reason" => "Illegal Conditions", "code" => "559");
                }elseif($block_list[0]->user_id == $request->user_id){
                    $json_return = array("message" => "User ID ".$request->user_id." blocked by FastPayments", "reason" => "Illegal Conditions", "code" => "559");
                }

                $path_name = "block-pix-".date("Y-m-d");

                if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                    mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                }

                $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($request));

                return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
                exit();

            }else{


                // if($FunctionsAPIController->validateCPF($request->user_document) == "no"){

                //     $json_return = array("message" => "User document CPF is invalid", "reason" => "Illegal Conditions", "code" => "6659");
                //     return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
                //     exit();

                // }else{

                //     $active_idreg = $client->active_idreg;

                //     if($active_idreg == "yes"){

                //         // rules id reg

                //     }

                    // All checks ok
                    $request['client'] = $client->id;
                    $request['Authorization'] = $authorizationAPI;
                    return $next($request);

                // }
            }

        }
    }
}
