<?php

namespace App\Http\Controllers\REST;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use \App\Models\{Extract,Clients,Transactions,Taxes,Keys,Banks,Logs,HistoryTax,User};
use App\Http\Controllers\FunctionsController;

class MerchantsControllerAPI extends Controller
{
    //

    public function index(Request $request){

        $first_date = date('Y-m').'-01 00:00:00';
        $last_date  = date('Y-m-t',strtotime($first_date)).' 23:59:59';
        $clients = Clients::with('tax')->get()->sortBy("name");
        $merchants = [];
        $total_fee = 0;

        foreach($clients as $client){

            $fee = Extract::where("client_id",$client->id)
            ->where("type_transaction_extract","cash-out")
            ->whereBetween("created_at",[$first_date,$last_date])
            ->whereIn("description_code",["CM01","CM02","CM03","CM04"])
            ->sum("final_amount");

            $merchants[] = array("client" => $client,"fee_month" => $fee);

            $total_fee += $fee;

        }

        $data = [
            "merchants" => $merchants,
            "total_fee" => $total_fee
        ];

        return response()->json($data);

    }

    public function search(Request $request){

        $first_date = $request->date.'-01 00:00:00';
        $last_date  = date('Y-m-t',strtotime($first_date)).' 23:59:59';
        $clients = Clients::all()->sortBy("name");
        $merchants = [];
        $total_fee = 0;

        foreach($clients as $client){

            $fee = Extract::where("client_id",$client->id)
            ->where("type_transaction_extract","cash-out")
            ->whereBetween("created_at",[$first_date,$last_date])
            ->whereIn("description_code",["CM01","CM02","CM03","CM04"])
            ->sum("final_amount");

            $merchants[] = array("client" => $client,"fee_month" => $fee);

            $total_fee += $fee;

        }

        $data = [
            "merchants" => $merchants,
            "total_fee" => $total_fee
        ];

        return response()->json($data);

    }

    public function store(Request $request)
    {

        $FunctionsController = new FunctionsController();

        DB::beginTransaction();
        try {

            if($request->hasFile('contract')){

                if($request->file('contract')->isValid()){
                    $nome = md5(date('Y-m-d H:i:s'));
                    $extensao = $request->contract->extension();
                    $nameFile = "{$nome}.{$extensao}";

                    $upload = $request->contract->storeAs('contract/', $nameFile, 's3');
                    if(!$upload){
                        return back()->with('error', 'Upload File Error');
                    }
                }

            }

            $taxs = $request->tax;
            foreach($taxs as $r => $y){
                if($y != NULL){
                    $taxs[$r] = $FunctionsController->strtodouble($y);
                }
            }

            $clients = $request->client;
            foreach($clients as $r => $y){
                if($y != NULL){
                    $clients[$r] = $FunctionsController->strtodouble($y);
                }
            }

            $key = Keys::create([
                "authorization" => "",
                "authorization_withdraw_a4p" => "",
                "url_callback" => "",
            ]);

            $tax = Taxes::create([
                "boleto_percent" => $taxs['boleto_percent'],
                "boleto_absolute" => $taxs['boleto_absolute'],
                "min_fee_boleto" => $taxs['min_fee_boleto'],
                "min_boleto" => $taxs['min_boleto'],
                "max_boleto" => $taxs['max_boleto'],
                // "pix_percent" => $taxs['pix_percent'],
                // "pix_absolute" => $taxs['pix_absolute'],
                // "pix" => $taxs['min_fee_pix'],
                // "min_pix" => $taxs['min_pix'],
                // "max_pix" => $taxs['max_pix'],
                "replacement_percent" => $taxs['replacement_percent'],
                "replacement_absolute" => $taxs['replacement_absolute'],
                "min_replacement" => $taxs['min_replacement'],
                "min_deposit" => $taxs['min_deposit'],
                "max_deposit" => $taxs['max_deposit'],
                "withdraw_percent" => $taxs['withdraw_percent'],
                "withdraw_absolute" => $taxs['withdraw_absolute'],
                "min_fee_withdraw" => $taxs['min_fee_withdraw'],
                "min_withdraw" => $taxs['min_withdraw'],
                "max_withdraw" => $taxs['max_withdraw'],
                "remittance_percent" => $taxs['remittance_percent'],
                "remittance_absolute" => $taxs['remittance_absolute'],
                //"withdraw_pix_percent" => $taxs['withdraw_pix_percent'],
                //"withdraw_pix_absolute" => $taxs['withdraw_pix_absolute'],
                "min_fee_withdraw_pix" => $taxs['min_fee_withdraw_pix'],
                // "min_withdraw_pix" => $taxs['min_withdraw_pix'],
                // "max_withdraw_pix" => $taxs['max_withdraw_pix'],
                "boleto_cancel" => $taxs['boleto_cancel'],
                "cc_percent" => $taxs['cc_percent'],
                "cc_absolute" => $taxs['cc_absolute'],
                "min_fee_cc" => $taxs['min_fee_cc'],
                "min_cc" => $taxs['min_cc'],
                "max_cc" => $taxs['max_cc'],
            ]);

            $client = Clients::create([
                "name" => $clients['name'],
                "address" => $clients['address'],
                "contact" => $clients['contact'],
                "document_holder" => $clients['document_holder'],
                "country" => $clients['country'],
                "days_safe_boleto" => $clients['days_safe_boleto'],
                "days_safe_pix" => $clients['days_safe_pix'],
                "days_safe_ted" => $clients['days_safe_ted'],
                "bank_invoice" => $clients['bank_invoice'],
                "bank_pix" => $clients['bank_pix'],
                "bank_ted" => $clients['bank_ted'],
                "bank_withdraw_permition" => $clients['bank_withdraw_permition'],
                "bank_cc" => $clients['bank_cc'],
                "days_safe_cc" => $clients['days_safe_cc'],
            ]);

            $client->update(['tax_id' => $tax->id]);
            $client->update(['key_id' => $key->id]);

            if(isset($nameFile)){
                $client->update(['contract' => $nameFile]);
            }

            Logs::create([
                'user_id' =>  auth()->user()->id,
                'client_id' =>  $client->id,
                'type' =>  'add',
                'action' => 'User '.auth()->user()->name.' created a new Client: '.$client->name.'.',
            ]);

            DB::commit();

            return response()->json(["message" => "success"]);

        }catch (Exception $e) {
            DB::rollback();
            return back()->with('error', 'Server error');
        }
    }

    public function get(Request $request)
    {
        $clients = Clients::where("id",$request->client)->with('tax')->with('key')->first();

        return response()->json($clients);
    }

    public function update(Request $request, Clients $client)
    {

        $FunctionsController = new FunctionsController();

        DB::beginTransaction();
        try {

            $clients = $request->client;
            foreach($clients as $r => $y){
                if($y != NULL){
                    $clients[$r] = $FunctionsController->strtodouble($y);
                }
            }

            $client->update([
                "name" => $clients['name'],
                "address" => $clients['address'],
                "contact" => $clients['contact'],
                "document_holder" => $clients['document_holder'],
                "country" => $clients['country'],
                "days_safe_boleto" => $clients['days_safe_boleto'],
                "days_safe_pix" => $clients['days_safe_pix'],
                "days_safe_ted" => $clients['days_safe_ted'],
                "bank_invoice" => $clients['bank_invoice'],
                "bank_pix" => $clients['bank_pix'],
                "bank_ted" => $clients['bank_ted'],
                "bank_withdraw_permition" => $clients['bank_withdraw_permition'],
                "bank_cc" => $clients['bank_cc'],
                "days_safe_cc" => $clients['days_safe_cc'],
            ]);

            $taxs = $request->tax;
            foreach($taxs as $r => $y){
                if($y != NULL){
                    $taxs[$r] = $FunctionsController->strtodouble($y);
                }
            }

            $tax = $client->tax()->first();

            $tax->update([
                "boleto_percent" => $taxs['boleto_percent'],
                "boleto_absolute" => $taxs['boleto_absolute'],
                "min_fee_boleto" => $taxs['min_fee_boleto'],
                "min_boleto" => $taxs['min_boleto'],
                "max_boleto" => $taxs['max_boleto'],
                //"pix_percent" => $taxs['pix_percent'],
                //"pix_absolute" => $taxs['pix_absolute'],
                // "min_fee_pix" => $taxs['min_fee_pix'],
                // "min_pix" => $taxs['min_pix'],
                // "max_pix" => $taxs['max_pix'],
                "replacement_percent" => $taxs['replacement_percent'],
                "replacement_absolute" => $taxs['replacement_absolute'],
                "min_replacement" => $taxs['min_replacement'],
                "min_deposit" => $taxs['min_deposit'],
                "max_deposit" => $taxs['max_deposit'],
                "withdraw_percent" => $taxs['withdraw_percent'],
                "withdraw_absolute" => $taxs['withdraw_absolute'],
                "min_fee_withdraw" => $taxs['min_fee_withdraw'],
                "min_withdraw" => $taxs['min_withdraw'],
                "max_withdraw" => $taxs['max_withdraw'],
                "remittance_percent" => $taxs['remittance_percent'],
                "remittance_absolute" => $taxs['remittance_absolute'],
                //"withdraw_pix_percent" => $taxs['withdraw_pix_percent'],
                //"withdraw_pix_absolute" => $taxs['withdraw_pix_absolute'],
                // "min_fee_withdraw_pix" => $taxs['min_fee_withdraw_pix'],
                // "min_withdraw_pix" => $taxs['min_withdraw_pix'],
                // "max_withdraw_pix" => $taxs['max_withdraw_pix'],
                "boleto_cancel" => $taxs['boleto_cancel'],
                "cc_percent" => $taxs['cc_percent'],
                "cc_absolute" => $taxs['cc_absolute'],
                "min_fee_cc" => $taxs['min_fee_cc'],
                "min_cc" => $taxs['min_cc'],
                "max_cc" => $taxs['max_cc'],
                "max_fee_withdraw" => $taxs['max_fee_withdraw'],
            ]);

            Logs::create([
                'user_id' =>  auth()->user()->id,
                'client_id' =>  $client->id,
                'type' =>  'change',
                'action' => 'User '.auth()->user()->name.' updated a Client: '.$client->name.'.',
            ]);

            DB::commit();

            return response()->json(['message' => 'success']);
        }catch (Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'error']);
        }

    }

    public function update_webhook(Request $request, Clients $client)
    {
        //check if client exists
        if(!$client){
            return response()->json(['message', 'Error, client not found']);
        }

        DB::beginTransaction();
        try {

            $client->key->update([
                'url_callback' => $request->url_callback,
                'url_callback_withdraw' => $request->url_callback_withdraw,
            ]);

            DB::commit();

            $data = [
                "message" => "Url callback update!",
            ];

            return response()->json($data);

        }catch (Exception $e) {

            DB::rollback();

            $data = [
                "message" => "Error on update Token!"
            ];

            return response()->json($data,401);

        }
    }

    public function info(Request $request){

        $data = [
            'model' => auth()->user()->client,
            'banks' => Banks::all(),
        ];

        return view('merchants.infos')->with('data',$data);

    }

    public function api()
    {

        if(auth()->user()->client->id != ''){
            $client = Clients::where('id', '=', auth()->user()->client->id)->first();
        }else{
            $client = null;
        }
        $data = [
            'model' => null,
            'client' => $client,
        ];
        return view('merchants.api')->with('data',$data);
    }


    //update url withdraw
    public function update_api_keys(Clients $client)
    {

        $FunctionsController = new FunctionsController();

        //check if client exists
        if(!$client){
            return response()->json(['message', 'Error, client not found']);
        }

        $id_user = auth()->user()->id;
        $token = sha1(md5($FunctionsController->geraSenha(8,false,true,false,false).$id_user));

        DB::beginTransaction();
        try {

            $client->key->update([
                'authorization' => $token,
                'authorization_withdraw_a4p' => $token,
            ]);

            DB::commit();

            $data = [
                "message" => "Token update!",
                "authorization" => $token
            ];

            return response()->json($data);

        }catch (Exception $e) {
            DB::rollback();

            $data = [
                "message" => "Error on update Token!"
            ];

            return response()->json($data,401);
        }
    }

    public function postmanCollection()
    {
        return view('merchants.postman');
    }

}
