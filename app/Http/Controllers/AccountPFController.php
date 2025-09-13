<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Clients,Banks,Transactions};
use App\Http\Controllers\FunctionsAPIController;

class AccountPFController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $FunctionsAPIController = new FunctionsAPIController();
        $clientCode = $FunctionsAPIController->gera_pedido($request->user_id);
        $client = Clients::where("id","=",$request->client_id)->first();

        $client_id_celcoin = $client->bankPix->client_id_celcoin;
        $client_secret_celcoin = $client->bankPix->client_secret_celcoin;
        $access_token_celcoin = $client->bankPix->access_token_celcoin;

        $params = [
            'client_id' => $request->client_id,
            'client_id_celcoin' => $client_id_celcoin,
            'client_secret_celcoin' => $client_secret_celcoin,
            'access_token_celcoin' => $access_token_celcoin
        ];

        $token_celcoin = json_decode($this->getTokenCELCOIN($params),true);

        if(isset($token_celcoin)){

            $data = [
                "accountOnboardingType" => "BANKACCOUNT",
                "phoneNumber" => $request->phone,
                "address" => array(
                    "postalCode" => $request->zip_code,
                    "street" => $request->street,
                    "number" => $request->number,
                    "neighborhood" => $request->neighborhood,
                    "city" => $request->city,
                    "state" => $request->state
                ),
                "clientCode" => $clientCode,
                "documentNumber" => $request->document,
                "email" => $request->email,
                "motherName" => $request->mother_name,
                "fullName" => $request->full_name,
                "socialName" => explode(" ",$request->full_name)[0],
                "birthDate" => $request->birth_date,
                "isPoliticallyExposedPerson" => false
            ];

            $token = $token_celcoin['access_token'];

            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://apicorp.celcoin.com.br/baas-onboarding/v1/account/natural-person/create',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data,true),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$token,
                'Content-Type: application/json'
            ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            return $response;

        }else{

            $path_name = "fastlogs-token-celcoin-".date("Y-m-d");

            if (!file_exists('/var/www/html/fastpayments/logs/'.$path_name)) {
                mkdir('/var/www/html/fastpayments/logs/'.$path_name, 0777, true);
            }

            $payload = [
                "date" => date("Y-m-d H:i:s"),
                "client" => $client->name,
                "params" => $params,
                "retur_celcoin" => $token_celcoin
            ];

            $this->registerRecivedsRequests("/var/www/html/fastpayments/logs/".$path_name."/tokencelcoinlog.txt",json_encode($payload));

            $ar = array(
                "code" => "558",
                "message" => "Erro on auth ".$clientCode,
            );

            return json_encode($ar);
        }

    }

    public function getTokenCELCOIN($params = array()){

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apicorp.celcoin.com.br/v5/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array('client_id' => $params['client_id_celcoin'],'grant_type' => 'client_credentials','client_secret' => $params['client_secret_celcoin']),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function getListAccounts($params = array()){

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apicorp.celcoin.com.br/v5/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array('client_id' => $params['client_id_celcoin'],'grant_type' => 'client_credentials','client_secret' => $params['client_secret_celcoin']),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return response()->json(json_decode($response,true));

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        //
        $client = Clients::where("id","=",$request->client_id)->first();

        $client_id_celcoin = $client->bankPix->client_id_celcoin;
        $client_secret_celcoin = $client->bankPix->client_secret_celcoin;
        $access_token_celcoin = $client->bankPix->access_token_celcoin;

        $params = [
            'client_id' => $request->client_id,
            'client_id_celcoin' => $client_id_celcoin,
            'client_secret_celcoin' => $client_secret_celcoin,
            'access_token_celcoin' => $access_token_celcoin
        ];

        $token_celcoin = json_decode($this->getTokenCELCOIN($params),true);

        $account = "30053993926";
        $document = "43892199876";

        if(isset($token_celcoin)){

            $data = [
                "phoneNumber" => $request->phone,
                "email" => $request->email,
                "socialName" => $request->social_name,
                "isPoliticallyExposedPerson" => false,
                "address" => array(
                    "postalCode" => $request->zip_code,
                    "street" => $request->street,
                    "number" => $request->number,
                    "neighborhood" => $request->neighborhood,
                    "city" => $request->city,
                    "state" => $request->state
                )
            ];

            $token = $token_celcoin['access_token'];

            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://sandbox.openfinance.celcoin.dev/baas-accountmanager/v1/account/natural-person?Account='.$account.'&DocumentNumber='.$document,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => json_encode($data,true),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$token
            ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            return $response;

        }else{

            $path_name = "fastlogs-token-celcoin-".date("Y-m-d");

            if (!file_exists('/var/www/html/fastpayments/logs/'.$path_name)) {
                mkdir('/var/www/html/fastpayments/logs/'.$path_name, 0777, true);
            }

            $payload = [
                "date" => date("Y-m-d H:i:s"),
                "client" => $client->name,
                "params" => $params,
                "retur_celcoin" => $token_celcoin
            ];

            $this->registerRecivedsRequests("/var/www/html/fastpayments/logs/".$path_name."/tokencelcoinlog.txt",json_encode($payload));

            $ar = array(
                "code" => "558",
                "message" => "Erro on auth ".$clientCode,
            );

            return json_encode($ar);
        }

    }

    public function createPixKey(Request $request){

        $client = Clients::where("id","=",$request->client_id)->first();

        $client_id_celcoin = $client->bankPix->client_id_celcoin;
        $client_secret_celcoin = $client->bankPix->client_secret_celcoin;
        $access_token_celcoin = $client->bankPix->access_token_celcoin;

        $params = [
            'client_id' => $request->client_id,
            'client_id_celcoin' => $client_id_celcoin,
            'client_secret_celcoin' => $client_secret_celcoin,
            'access_token_celcoin' => $access_token_celcoin
        ];

        $token_celcoin = json_decode($this->getTokenCELCOIN($params),true);

        $token = $token_celcoin['access_token'];

        $account = $request->account;

        $data = [
            "account" => $account,
            "keyType" => "EVP",
            "key" => ""
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apicorp.celcoin.com.br/celcoin-baas-pix-dict-webservice/v1/pix/dict/entry',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data,true),
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer '.$token,
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return response()->json(json_decode($response,true));

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
