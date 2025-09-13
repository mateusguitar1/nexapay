<?php

namespace App\Http\Controllers\REST;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use \App\Models\{Extract,Clients,Banks,Logs};

use App\Http\Controllers\FunctionsAPIController;

class BanksControllerAPI extends Controller
{
    //

    public function index(){

        $banks = Banks::orderby('name')->with('clients_invoice')->with('clients_pix')->get();
        foreach( $banks as $bank ){
            $bank['total'] += count($bank->clients_invoice);
            $bank['total'] += count($bank->clients_pix);
            $bank['flag'] = getflagname($bank->code,$bank->name);
        }
        $clients = Clients::orderby('name')->get();

        $data = [
            'banks' => $banks,
            'clients' => $clients,
        ];

        return response()->json($data);

    }

    public function store(Request $request){

        DB::beginTransaction();
        try {

            $bank = Banks::create([
                "code" => $request->code,
                "name" => $request->name,
                "holder" => $request->holder,
                "type_account" => $request->type_account,
                "agency" => $request->agency,
                "account" => $request->account,
                "document" => $request->document,
                "status" => $request->status
            ]);

            $log = Logs::create([
                'user_id' =>  auth()->user()->id,
                'type' =>  'add',
                'action' => 'User '.auth()->user()->name.' created a Bank: '.$bank->name.'.',
            ]);

            DB::commit();

            return response()->json(["bank" => $bank,"message" => "Bank created sucessful", "status" => "success"]);

        }catch (Exception $e) {
            DB::rollback();
            return response()->json(["message" => "Server error", "status" => "error"],401);
        }

    }

    public function getBank(Request $request){

        $id = $request->id;
        return response()->json(Model('Banks')::find($id));

    }

    public function update(Request $request, Banks $bank){

        DB::beginTransaction();
        try {

            $bank->update([
                "code" => $request->code,
                "name" => $request->name,
                "holder" => $request->holder,
                "type_account" => $request->type_account,
                "agency" => $request->agency,
                "account" => $request->account,
                "document" => $request->document,
                "status" => $request->status
            ]);

            Logs::create([
                'user_id' =>  auth()->user()->id,
                'type' =>  'change',
                'action' => 'User '.auth()->user()->name.' updated a Bank: '.$bank->name.'.',
            ]);

            DB::commit();
            return response()->json(["message" => "updated"]);
        }catch (Exception $e) {
            DB::rollback();
            return response()->json(["message" => "error"]);
        }

    }

    public function updateClients(Request $request){

        $count = count($request->clients);
        $clients_update = $request->clients;
        $methods_update = $request->method;
        $list_clients = [];
        $list_clients_methods = [];

        for($i = 0;$i <= ($count - 1);$i++){
            if($methods_update[$i+1] != "none"){
                array_push($list_clients,$clients_update[$i+1]);
                array_push($list_clients_methods,array($clients_update[$i+1] => $methods_update[$i+1]));
            }
        }

        DB::beginTransaction();
		try{
            $clients = Clients::whereIn("id",$list_clients)->get();

            foreach($clients as $client){
                foreach($list_clients_methods as $line){
                    if(isset($line[$client->id])){

                        if($line[$client->id] == 'invoice' ){
                            $client->update(['bank_invoice' => $request->bank_id]) ;
                        }

                        // if($line[$client->id] == 'credit_card' ){
                        //     $client->update(['bank_credit_card' => $request->bank_id]) ;
                        // }

                        if($line[$client->id] == 'bank_pix' ){
                            $client->update(['bank_pix' => $request->bank_id]) ;
                        }

                        DB::commit();
                    }
                }
            }

            Logs::create([
                'user_id' =>  auth()->user()->id,
                'type' =>  'change',
                'action' => 'User '.auth()->user()->name.' change all client banks',
            ]);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Updated Successfully',
            ]);

		}catch(Exception $e){
			DB::rollback();
			return response()->json([
                'status' => 'error',
                'message' => $e,
            ]);
        }

    }
}
