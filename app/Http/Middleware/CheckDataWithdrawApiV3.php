<?php

namespace App\Http\Middleware;

use DB;
use App\Models\{Clients,Keys,Transactions};
use App\Http\Controllers\FunctionsAPIController;

use Closure;

class CheckDataWithdrawApiV3
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

        $Authorization = $request->header('Token');
        $auth = Keys::where("authorization","=",$Authorization)->first();
        $client = $auth->client;
        $client_id = $client->id;

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

                $path_name = "block-pix-withdraw-".date("Y-m-d");

                if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                    mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
                }

                $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/log.txt",json_encode($request));

                return response()->json($json_return,422,['HTTP/1.0' => 'Unauthorized']);
                exit();

            }else{

                // if($FunctionsAPIController->validateCPF($request->user_document) == "no"){

                //     $json_return = array("message" => "User document CPF is invalid", "reason" => "Illegal Conditions", "code" => "6659");
                //     return response()->json($json_return,422);
                //     exit();

                // }else{

                    /**
                     * Check if it is possible to register the withdrawal request
                     */
                    $amount = $request->amount;
                    $subs = substr($amount,-3,1);
                    if($subs == "."){
                        $amount = $amount;
                    }elseif($subs == ","){
                        $amount = $FunctionsAPIController->strtodouble($amount);
                    }else{
                        $amount = number_format($amount,2,".","");
                    }

                    $result_check = $FunctionsAPIController->checkBalanceWithdraw($client_id,$amount);

                    if($result_check['message'] != "success"){
                        return response()->json($result_check,422,['HTTP/1.0' => 'Unauthorized']);
                        exit();
                    }else{
                        // All checks ok
                        $request['client'] = $client->id;
                        $request['authorization'] = $Authorization;
                        return $next($request);
                    }

                // }

            }
        }
    }
}
