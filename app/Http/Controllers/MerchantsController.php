<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use \App\Models\{Extract,Clients,Transactions,Taxes,Keys,Banks,Logs,HistoryTax,User};
use App\Http\Controllers\FunctionsController;

class MerchantsController extends Controller
{
    //

    public function index(Request $request){

        $first_date = date('Y-m').'-01 00:00:00';
        $last_date  = date('Y-m-t',strtotime($first_date)).' 23:59:59';

        $data = [
            'merchants' => Clients::all()->sortBy("name"),
            'date' => null,
            'first_date' => $first_date,
            'last_date' => $last_date
        ];

        return view('merchants.index',compact('data'));

    }

    public function search(Request $request){

        $first_date = $request->date.'-01 00:00:00';
        $last_date  = date('Y-m-t',strtotime($first_date)).' 23:59:59';

        $data = [
            'merchants' => Clients::all()->sortBy("name"),
            'date' => $request->date,
            'first_date' => $first_date,
            'last_date' => $last_date
        ];

        return view('merchants.index',compact('data'));

    }

    public function create()
    {
        $data = [
            'model' => null,
            'banks' => Banks::all(),
            'title' => 'Create Client',
            'url' => url('/merchants/store'),
            'button' => 'Save',
        ];
        return view('merchants.form')->with('data',$data);
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

            // $keys = $request->key;
            // $keys['minamount_boletofirst'] = $FunctionsController->strtodouble($keys['minamount_boletofirst']);
            // $keys['authorization'] = "";
            // $keys['authorization_withdraw_a4p'] = "";
            // $keys['url_callback'] = "";

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
                "pix_percent" => $taxs['pix_percent'],
                "pix_absolute" => $taxs['pix_absolute'],
                "pix" => $taxs['min_fee_pix'],
                "min_pix" => $taxs['min_pix'],
                "max_pix" => $taxs['max_pix'],
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
                "withdraw_pix_percent" => $taxs['withdraw_pix_percent'],
                "withdraw_pix_absolute" => $taxs['withdraw_pix_absolute'],
                "min_fee_withdraw_pix" => $taxs['min_fee_withdraw_pix'],
                "min_withdraw_pix" => $taxs['min_withdraw_pix'],
                "max_withdraw_pix" => $taxs['max_withdraw_pix'],
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
                "currency" => $clients['currency'],
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

            return redirect('merchants')->with('success', 'Client created sucessful');

        }catch (Exception $e) {
            DB::rollback();
            return back()->with('error', 'Server error');
        }
    }

    public function show(Clients $client)
    {
        return [
            'client' => $client,
            'taxes' => $client->tax,
            'keys' => $client->key,
            'bank_invoice' => $client->bankInvoice,
            'bank_pix' => $client->bankPix,
            'bank_withdraw_permition' => $client->bankWithdrawPix,
            'bank_ted' => $client->bankTed,
        ];
    }

    public function edit(Clients $client)
    {
        $FunctionsController = new FunctionsController();

        $taxs = $client->tax;

        foreach($taxs as $r => $y){
            if(is_numeric($y)){
                $taxs[$r] = $FunctionsController->doubletostr($y);
            }
        }

        // $clients = $client;
        // if($clients['invoice_shop_base_fee'] != null){ $clients['invoice_shop_base_fee'] = $FunctionsController->doubletostr($clients['invoice_shop_base_fee']); }
        // if($clients['card_base_fee'] != null){ $clients['card_base_fee'] = $FunctionsController->doubletostr($clients['card_base_fee']); }
        // if($clients['min_boleto_first'] != null){ $clients['min_boleto_first'] = $FunctionsController->doubletostr($clients['min_boleto_first']); }

        // $history_tax = HistoryTax::where("client_id","=",$client->id)->orderByRaw("created_at DESC")->get();

        $data = [
            'model' => $client,
            'taxs' => $taxs,
            'title' => 'Edit Merchant',
            'banks' => Banks::all(),
            'url' => url("/merchants/$client->id/update"),
            'button' => 'Edit',
        ];

        return view('merchants.form',compact('data'));
    }

    public function update(Request $request, Clients $client)
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

            //

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
                "currency" => $clients['currency'],
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

            if(isset($nameFile)){
                if (Storage::exists('upload/contract/'.$client->contract)) {
                    Storage::delete('upload/contract/'.$client->contract);
                }
                $client->update(['contract' => $nameFile]);
            }


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
                "pix_percent" => $taxs['pix_percent'],
                "pix_absolute" => $taxs['pix_absolute'],
                "min_fee_pix" => $taxs['min_fee_pix'],
                "min_pix" => $taxs['min_pix'],
                "max_pix" => $taxs['max_pix'],
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
                "withdraw_pix_percent" => $taxs['withdraw_pix_percent'],
                "withdraw_pix_absolute" => $taxs['withdraw_pix_absolute'],
                "min_fee_withdraw_pix" => $taxs['min_fee_withdraw_pix'],
                "min_withdraw_pix" => $taxs['min_withdraw_pix'],
                "max_withdraw_pix" => $taxs['max_withdraw_pix'],
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

            return redirect('merchants')->with('success', 'Merchant updated sucessful');
        }catch (Exception $e) {
            DB::rollback();
            return back()->with('error', 'Server error');
        }

    }

    public function update_webhook(Request $request)
    {
        if($request->client_id != ''){
            $client = Clients::where('id', '=', $request->client_id)->first();
        }else{
            return back()->with('error', 'Client not selected');
        }

        DB::beginTransaction();
        try {

            $client->key->update([
                'url_callback' => $request->url_callback,
                'url_callback_withdraw' => $request->url_callback_withdraw,
            ]);

            DB::commit();
            return back()->with('success', 'Success');

        }catch (Exception $e) {

            DB::rollback();
            return back()->with('error', 'Server error');

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
    public function update_api_keys($id)
    {

        $FunctionsController = new FunctionsController();

        //update
        if($id != ''){
            $client = Clients::where('id', '=', $id)->first();
        }else{
            return back()->with('error', 'Client not selected');
        }

        $id_user = auth()->user()->id;
        $token = sha1(md5($FunctionsController->geraSenha(8,false,true,false,false).$id_user));

        DB::beginTransaction();
        try {

            $client->key->update([
                'authorization' => $token,
                'authorization_withdraw_a4p' => $token,
            ]);

            //add notification
            $users = User::where('client_id', '=', $client->id)->where('id', '!=' , auth()->user()->id)->get();
            $users_ids = "";
            foreach($users as $user){
                $users_ids.= "'".$user->id."',";
            }

            $users_ids = substr($users_ids,0,-1);
            $title = "User update API keys";


            $description =
                "<table align='center' bgcolor='#EFEEEA' border='0' cellpadding='0' cellspacing='0' height='100%' width='100%'>
                <tbody>
                    <tr>
                        <td align='center' valign='top' id='m_-2000320271114661702bodyCell' style='padding-bottom:60px;padding-top: 40px;'>
                            <table align='center' border='0' cellpadding='0' cellspacing='0' width='100%'>
                                <tbody>
                                    <tr>
                                        <td align='center' valign='top'>
                                            <table align='center' bgcolor='#FFFFFF' border='0' cellpadding='0' cellspacing='0' style='background-color:#ffffff;max-width:640px' width='100%'>
                                                <tbody>

                                                    <tr>
                                                        <td align='center' valign='top' bgcolor='#FFFFFF' style='padding-right:40px;padding-left:40px;'>
                                                            <a href='https://www.FastPayments.com' style='text-decoration:none' target='_blank'><img alt='FastPayments' src='".asset('images/all4pay_logo_ret.png')."' width='230' style='border:0;color:#ffffff;font-size:12px;font-weight:400;height:auto;letter-spacing:-1px;padding:15px;margin:0;outline:none;text-align:center;text-decoration:none' class='CToWUd'></a>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td align='center' valign='top' bgcolor='#FFFFFF' style='padding-right:40px;padding-left:40px'>
                                                            <h1 style='color:#241c15;font-size:20px;font-style:normal;font-weight:400;line-height:42px;letter-spacing:normal;margin:0;padding:0;text-align:center'>User ".auth()->user()->name." update withdraw keys.</h1><p>Token: ".$token."</p><p> at ".date('d/m/Y H:i')."</p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td align='center' valign='top' style='border-top:2px solid #efeeea;color:#6a655f;font-size:12px;font-weight:400;line-height:24px;padding-top:40px;padding-bottom:40px;text-align:center'>
                                                            <p style='color:#6a655f;font-size:12px;font-weight:400;line-height:24px;padding:0 20px;margin:0;text-align:center'>©".date('Y')."<span class='il'>FastPayments</span><sup>®</sup>, All Rights Reserved.</a></p>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>";

            DB::commit();
            return back()->with('success', 'Success');

        }catch (Exception $e) {
            DB::rollback();
            return back()->with('error', 'Server error');
        }
    }

    public function postmanCollection()
    {
        return view('merchants.postman');
    }

    public function charge(Request $request){
        $FunctionsAPIController = new FunctionsAPIController();

        if(auth()->user()->level == "master"){
            $clientt = Clients::where("id",8)->first();
            $token = $clientt->key->authorization;
        }else{
            $token = auth()->user()->client->key->authorization;
        }

        $data = [
            "order_id" => "NXP".generateRandomString(10),
            "user_id" => "1234567",
            "user_document" => "91969747021",
            "amount" => $FunctionsAPIController->strtodouble($request->amount),
            "method" => "pix",
            "user_address" => "---",
            "user_district" => "---",
            "user_city" => "---",
            "user_uf" => "---",
            "user_cep" => "---"
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://apirestnexapay.financebaking.com/api/deposit",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Token: '.$token,
                'Accept: application/json',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $return = json_decode($response,true);

        return response()->json($return);
    }

}
