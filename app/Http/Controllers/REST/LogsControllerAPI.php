<?php

namespace App\Http\Controllers\REST;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\FunctionsController;
use \App\Models\{Extract,Clients,Transactions,Taxes,Keys,Banks,Logs,HistoryTax,User};

class LogsControllerAPI extends Controller
{
    //
    public function index()
    {
        $request = array(
            'client_id' => null, 'user_id' => null,
            'type' => null,
        );

        if(auth()->user()->level == "merchant"){
            $logs = Logs::where('client_id','=',auth()->user()->client_id)->whereBetween('created_at',[date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')])->orderBy('created_at', 'desc')->get();
            $users = User::where('client_id','=',auth()->user()->client_id)->get();
            $clients = Clients::all()->sortBy("name");
        }else{
            $logs = Logs::whereBetween('created_at',[date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')])->orderBy('created_at', 'desc')->get();
            $users = User::all();
            $clients = Clients::where("id",auth()->user()->client_id)->get();
        }

        $data = [
            'logs' => $logs,
            'users' => $users,
            'clients' => $clients,
            'type' => ['system','add','change','freeze','request','blocklist','whitelist','watchlist']
        ];

        return response()->json($data);;
    }

    public function search(Request $request)
    {
        // return $request;
        if($request->start_date == '' && $request->end_date == ''){
            $first_date = date('Y-m-d').' 00:00:00';
            $last_date = date('Y-m-d').' 23:59:59';
        }else{
            $first_date = date('Y-m-d', strtotime($request->start_date)).' 00:00:00';
            $last_date = date('Y-m-d', strtotime($request->end_date)).' 23:59:59';
        }

        $logs = Logs::whereBetween('created_at',[$first_date,$last_date])->orderBy('created_at', 'desc')->get();

        if($request->client_id != ''){
            $logs = $logs->where('client_id', '=', $request->client_id);
        }

        if($request->user_id != ''){
            $logs = $logs->where('user_id', '=', $request->user_id);
        }

        if($request->type != ''){
            $logs = $logs->where('type', '=', $request->type);
        }

        if(auth()->user()->level == "merchant"){
            $users = User::where('client_id','=',auth()->user()->client_id)->get();
            $clients = Clients::all()->sortBy("name");
        }else{
            $users = User::all();
            $clients = Clients::where("id",auth()->user()->client_id)->get();
        }

        $data = [
            'logs' => $logs,
            'users' => $users,
            'clients' => $clients,
            'type' => ['system','add','change','freeze','request','blocklist','whitelist','watchlist']
        ];
        return response()->json($data);
    }
}
