<?php

namespace App\Http\Middleware;

use DB;
use App\Models\{Clients,Keys,Transactions};

use Closure;

class AuthClientWithdrawPostApiV3
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
        // get authorization key
        $Authorization = $request->header('Token');

        if($Authorization !== ""){

            $auth = Keys::where("authorization","=",$Authorization)->get();

            if(!empty($auth[0])){
                $client = $auth[0]->client;

                if($client){
                    return $next($request);
                }else{
                    // Error, Client not found
                    $json_return = array("message" => "Missing Token key");
                    return response()->json($json_return,401,['HTTP/1.0' => 'Unauthorized']);
                }
            }else{
                // Error, authorization key invalid
                $json_return = array("message" => "Error 5006 - invalid Token key");
                return response()->json($json_return,401,['HTTP/1.0' => 'Unauthorized']);
            }
        }else{
            // Error, empty authorization key
            $json_return = array("message" => "Error 5006 - Token key not declared ");
            return response()->json($json_return,401,['HTTP/1.0' => 'Unauthorized']);
        }
    }
}
