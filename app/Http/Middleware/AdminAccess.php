<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminAccess
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

        $currentAction = \Route::currentRouteAction();
        list($currentroute, $method) = explode('@', $currentAction);

        $currentroute = preg_replace('/.*\\\/', '', $currentroute);


        if(auth()->user()->level == 'master'){
            return $next($request);
        }else{
            if($currentroute == "AproveWithdrawController" && auth()->user()->level == 'payment'){
                return $next($request);
            }elseif($currentroute != "AproveWithdrawController" && auth()->user()->level == 'payment'){
                // Error, user haven't access
                return redirect('AproveWithdrawController')->with('warning', 'You are not authorized to access this page');
            }
            // Error, user haven't access
            return redirect('home')->with('warning', 'You are not authorized to access this page');
        }
    }
}
