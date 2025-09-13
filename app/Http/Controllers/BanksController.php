<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use \App\Models\{Extract,Clients,Banks,Logs};

use App\Http\Controllers\FunctionsAPIController;

class BanksController extends Controller
{
    //

    public function index(){

        $banks = Banks::orderby('name')->get();
        foreach( $banks as $bank ){
            $bank['total'] += count($bank->clients_invoice);
            $bank['total'] += count($bank->clients_pix);
        }
        $clients = Clients::orderby('name')->get();

        $data = [
            'banks' => $banks,
            'clients' => $clients,
        ];

        return view('banks.index',compact('data'));

    }

    public function create(){

        $data = [
            'model' => null,
            'title' => 'Create Bank Account',
            'url' => url('/banks/store'),
            'button' => 'Save',
        ];

        return view('banks.form',compact('data'));

    }

    public function store(Request $request){

        DB::beginTransaction();
        try {

            $bank = Banks::create($request->bank);

            $log = Logs::create([
                'user_id' =>  auth()->user()->id,
                'type' =>  'add',
                'action' => 'User '.auth()->user()->name.' created a Bank: '.$bank->name.'.',
            ]);

            DB::commit();

            return redirect('banks')->with('success', 'bank created sucessful');

        }catch (Exception $e) {
            DB::rollback();
            return back()->with('error', 'Server error');
        }

    }

    public function getBank(Request $request){

        $id = $request->id;
        return Model('Banks')::find($id);

    }

    public function update(Request $request, Banks $bank){

        DB::beginTransaction();
        try {
            $bank->update($request['bank']);

            Logs::create([
                'user_id' =>  auth()->user()->id,
                'type' =>  'change',
                'action' => 'User '.auth()->user()->name.' updated a Bank: '.$bank->name.'.',
            ]);

            DB::commit();
            return redirect('banks')->with('success', 'bank updated sucessful');
        }catch (Exception $e) {
            DB::rollback();
            return back()->with('error', 'Server error');
        }

    }

    public function destroy(Banks $bank,Request $request){

        DB::beginTransaction();
        try {

            Logs::create([
                'user_id' =>  auth()->user()->id,
                'type' =>  'system',
                'action' => 'User '.auth()->user()->name.' deleted a Bank: '.$bank->name.'.',
            ]);

            $bank->delete();
            DB::commit();
            return redirect('banks')->with('success', 'bank deleted sucessful');
        }catch (Exception $e) {
            DB::rollback();
            return back()->with('error', 'Server error');
        }

    }

    public function restore($id){

        DB::beginTransaction();
		try{
            $bank = Banks::onlyTrashed()->where('id', $id)->first();
            $bank->restore();

            Logs::create([
                'user_id' =>  auth()->user()->id,
                'type' =>  'system',
                'action' => 'User '.auth()->user()->name.' restored a Bank: '.$bank->name.'.',
            ]);

			DB::commit();
			return redirect('/banks')->with('success', 'bank restored sucessful');
		}catch(Exception $e){
			DB::rollback();
			return back()->with('error', 'Server error');
		}

    }

    public function freeze($id){

        DB::beginTransaction();
		try{
            $bank = Banks::where('id', $id)->first();

            if($bank->status == 'ativo'){

                $bank->update(['status'=>'inativo']);
                $message = "The bank was successfully frozen ";


                Logs::create([
                    'user_id' =>  auth()->user()->id,
                    'type' =>  'freeze',
                    'action' => 'User '.auth()->user()->name.' freeze bank: '.$bank->name.'.',
                ]);

            }else{

                $bank->update(['status'=>'ativo']);
                $message = "The bank was successfully activated";

                Logs::create([
                    'user_id' =>  auth()->user()->id,
                    'type' =>  'freeze',
                    'action' => 'User '.auth()->user()->name.' unfreeze bank: '.$bank->name.'.',
                ]);

            }

			DB::commit();
			return response()->json([
                'status' => 'success',
                'message' => $message,
            ]);

		}catch(Exception $e){
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => $e,
            ]);
		}

    }

    public function updateClientsForm($id){

        $bank = Banks::find($id);

        $data['data'] = Banks::find($id);
        if ($bank->name == 'Banco Santander (Brasil) S.A.') {
            $data['clients'] = $bank->clients_santander;
        } elseif($bank->name == 'Banco do Brasil') {
            $data['clients'] = $bank->clients_bb;
        } elseif($bank->name == 'Bradesco') {
            $data['clients'] = $bank->clients_bradesco;
        } elseif($bank->name == 'Caixa Econômica Federal') {
            $data['clients'] = $bank->clients_caixa;
        } elseif($bank->name == 'Itaú Unibanco') {
            $data['clients'] = $bank->clients_itau;
        }

        if( $bank->clients_invoice->count() > 0 ){
            $data['clients'] = $bank['clients_invoice'];
        }
        if( $bank->clients_card->count() > 0 ){
            $data['clients'] = $bank->clients_card;
        }
        if( $bank->clients_pix->count() > 0 ){
            $data['clients'] = $bank->clients_pix;
        }

        return view('banks.form_update_clients', compact('data'));

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

    public function updateClientsData(Request $request){

        $bank = Banks::where("id",$request->id)->first();

        if(isset($bank)){

            DB::beginTransaction();

            try{
                $bank->update([
                    "name" => $request->name,
                    "code" => $request->code,
                    "holder" => $request->holder,
                    "agency" => $request->agency,
                    "type_account" => $request->type_account,
                    "account" => $request->account,
                    "document" => $request->document,
                    "status" => $request->status,
                    "prefix" => $request->prefix,
                ]);

                Logs::create([
                    'user_id' =>  auth()->user()->id,
                    'type' =>  'change',
                    'action' => 'User '.auth()->user()->name.' change all client banks',
                ]);

                DB::commit();

                return response()->json([
                    'status' => 'sucess',
                    'message' => 'Updated Sucessfully',
                ]);

            }catch(Exception $e){
                DB::rollback();

                return response()->json([
                    'status' => 'error',
                    'message' => $e,
                ]);
            }

        }else{
            return response()->json(["message" => "bank not found"]);
        }

    }

    public function exportPayin($id){

        $id_bank = $id;

        $transaction = Transactions::where("type_transaction","=","deposit")
            ->where("id_bank","=",$id_bank)
            ->where("status","=","confirmed")
            ->get();

        $data = [
            'bank' => Banks::where("id","=",$id_bank)->first(),
            'transaction' => $transaction
        ];

        return view('banks.export')->with('data',$data);

    }

    public function getBalanceCelcoin(){

        $FunctionsAPIController = new FunctionsAPIController();

        $bank = Banks::where("id","6")->first();

        $paramsToken = [
            "client_id_celcoin" => $bank->client_id_celcoin,
            "client_secret_celcoin" => $bank->client_secret_celcoin,
            "access_token_celcoin" => $bank->access_token_celcoin,
        ];

        $token_celcoin = $FunctionsAPIController->getAccessTokenCELCOIN($paramsToken);

        if(isset($token_celcoin)){

            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://apicorp.celcoin.com.br/v5/merchant/balance',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer '.$token_celcoin
            ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            return $response;

            $return =  json_decode($response,true);

            return json_encode(array("balance" => "R$ ".number_format($return['balance'],"2",",",".")));

        }

    }

}
