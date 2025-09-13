<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use DB;
use App\Models\{Clients,Keys,Transactions};

class AuthClientDepositGetAPI
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // get authorization key
        $authorization = $request->header('Token');

        if($authorization != ""){
            $key = Keys::where("authorization",$authorization)->first();

            if($key){

                $client = $key->client;

                $request['client'] = $client->id;
                $request['authorization'] = $authorization;
                return $next($request);

            }else{
                // Error, authorization key invalid
                $json_return = array("message" => "Error 5006 - Invalid Token");
                return response()->json($json_return,401,['HTTP/1.0' => 'Unauthorized']);
            }
        }else{
            // Error, empty authorization key
            $json_return = array("message" => "Error 5006 - Token not declared ");
            return response()->json($json_return,401,['HTTP/1.0' => 'Unauthorized']);
        }
    }
}
