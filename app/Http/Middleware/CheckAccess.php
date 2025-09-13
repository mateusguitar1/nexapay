<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next)
    {
        if(auth()->user()->haspermitions){
            $afpermitions = auth()->user()->haspermitions;
            $permitions = [];
            foreach($afpermitions as $row){
                array_push($permitions,$row->permition_id);
            }
        }else{
            $permitions = [];
        }


        if($request->getPathInfo()== '/infos'){
            if(in_array(5,$permitions)){
                return $next($request);
            }else{
                return redirect('home')->with('warning', 'You are not authorized to access this page');
            }
        }else if($request->getPathInfo()== '/solicitation-refund'){
            if(in_array(2,$permitions)){
                return $next($request);
            }else{
                return redirect('home')->with('warning', 'You are not authorized to access this page');
            }
        }else if($request->getPathInfo()== '/solicitation-withdrawal'){
            // if(in_array(1,$permitions)){
                return $next($request);
            // }else{
            //     return redirect('home')->with('warning', 'You are not authorized to access this page');
            // }
        }else if($request->getPathInfo()== '/report'){
            if(in_array(4,$permitions)){
                return $next($request);
            }else{
                return redirect('home')->with('warning', 'You are not authorized to access this page');
            }
        }else if($request->getPathInfo()== '/users'){
            if(in_array(6,$permitions)){
                return $next($request);
            }else{
                return redirect('home')->with('warning', 'You are not authorized to access this page');
            }
        }else if($request->getPathInfo() == '/api'){
            if(in_array(8,$permitions)){
                return $next($request);
            }else{
                return redirect('home')->with('warning', 'You are not authorized to access this page');
            }
        }else if($request->getPathInfo()== '/client-users'){
            if(in_array(12,$permitions)){
                return $next($request);
            }else{
                return redirect('home')->with('warning', 'You are not authorized to access this page');
            }
        }else if($request->getPathInfo()== '/users/create'){
            if(in_array(6,$permitions)){
                return $next($request);
            }else{
                return redirect('home')->with('warning', 'You are not authorized to access this page');
            }
        }

		return $next($request);


    }
}
