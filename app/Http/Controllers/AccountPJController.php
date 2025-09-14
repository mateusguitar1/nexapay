<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\{Clients,Banks,Transactions};
use App\Http\Controllers\FunctionsAPIController;

class AccountPJController extends Controller
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
                "clientCode" => $clientCode,
                "accountOnboardingType" => "BANKACCOUNT",
                "documentNumber" => $request->business_document,
                "contactNumber" => $request->business_phone,
                "businessEmail" => $request->business_email,
                "businessName" => $request->business_name,
                "tradingName" => $request->fantasy_name,
                "owner" => [
                    array(
                    "documentNumber" => $request->owner_document,
                    "fullName" => $request->owner_name,
                    "phoneNumber" => $request->owner_phone,
                    "email" => $request->owner_email,
                    "motherName" => $request->owner_mother_name,
                    "socialName" => explode(" ",$request->owner_name)[0],
                    "birthDate" => $request->owner_brith_date,
                    "address" => [
                        "postalCode" => $request->owner_zip_code,
                        "street" => $request->owner_address,
                        "number" => $request->owner_number,
                        "addressComplement" => $request->owner_complement,
                        "neighborhood" => $request->owner_neighborhood,
                        "city" => $request->owner_city,
                        "state" => $request->owner_state
                    ],
                    "isPoliticallyExposedPerson" => false
                    )
                ],
                "businessAddress" => [
                    "postalCode" => $request->business_zip_copde,
                    "street" => $request->business_address,
                    "number" => $request->business_number,
                    "addressComplement" => $request->business_complement,
                    "neighborhood" => $request->business_neighborhood,
                    "city" => $request->business_city,
                    "state" => $request->business_state
                ]
            ];

            $token = $token_celcoin['access_token'];

            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://apicorp.celcoin.com.br/baas-onboarding/v1/account/business/create',
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

            if (curl_errno($curl)) {
                $error_msg = curl_error($curl);

                return response()->json(["error" => $error_msg]);
            }

            curl_close($curl);

            return $response;

        }else{

            $path_name = "fastlogs-token-celcoin-".date("Y-m-d");

            if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
            }

            $payload = [
                "date" => date("Y-m-d H:i:s"),
                "client" => $client->name,
                "params" => $params,
                "retur_celcoin" => $token_celcoin
            ];

            $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/tokencelcoinlog.txt",json_encode($payload));

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
                "businessEmail" => "andre.b.arruda@medtronic.com",
                "contactNumber" => "+551123133990",
                "businessAddress" => [
                    "postalCode" => "04576010",
                    "street" => "Avenida Jornalista Roberto Marinho",
                    "number" => "85",
                    "addressComplement" => "Andar 9 Parte A Andar 10 Parte A",
                    "neighborhood" => "Cidade Moncoes",
                    "city" => "São Paulo",
                    "state" => "SP"
                ],
                "owners" => [
                    array(
                        "documentNumber" => "38112507287",
                        "address" => [
                            "postalCode" => "88138410",
                            "street" => "Rua Maria Ignácia Fagundes",
                            "number" => "823",
                            "addressComplement" => "",
                            "neighborhood" => "Praia de Fora",
                            "city" => "Palhoça",
                            "state" => "SC"
                        ],
                        "fullName" => "Icaro Pinheiro Sereni",
                        "phoneNumber" => "+5548996368016",
                        "email" => "icaro.pinheiro43@gmail.com",
                        "socialName" => "Icaro",
                        "birthDate" => "30-08-1974",
                        "motherName" => "Terezinha de Jesus Pinheiro Sereni",
                        "isPoliticallyExposedPerson" => false
                    )
                ]
            ];

            $token = $token_celcoin['access_token'];

            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://apicorp.celcoin.com.br/baas-accountmanager/v1/account/business?Account=411483316&DocumentNumber=01772798000152',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => json_encode($data,true),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$token,
                'Content-Type: application/json'
            ),
            ));

            $response = curl_exec($curl);

            if (curl_errno($curl)) {
                $error_msg = curl_error($curl);

                return response()->json(["error" => $error_msg]);
            }

            curl_close($curl);

            return $response;

        }else{

            $path_name = "fastlogs-token-celcoin-".date("Y-m-d");

            if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
            }

            $payload = [
                "date" => date("Y-m-d H:i:s"),
                "client" => $client->name,
                "params" => $params,
                "retur_celcoin" => $token_celcoin
            ];

            $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/tokencelcoinlog.txt",json_encode($payload));

            $ar = array(
                "code" => "558",
                "message" => "Erro on auth ".$clientCode,
            );

            return json_encode($ar);
        }
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

    public function getStatusAccount(Request $request){

        $FunctionsAPIController = new FunctionsAPIController();
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

        $onboardingId = $request->onboarding_id;
        // $clientCode = $request->client_code;

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apicorp.celcoin.com.br/baas-onboarding/v1/account/check?onboardingId='.$onboardingId,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$token
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return response()->json(json_decode($response,true));


    }

    public function listAccounts(Request $request){

        $FunctionsAPIController = new FunctionsAPIController();
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

        $dateStart = $request->date_start;
        $dateEnd = $request->date_end;

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apicorp.celcoin.com.br/baas-accountmanager/v1/account/fetch-all?DateFrom='.$dateStart.'&DateTo='.$dateEnd,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$token
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return response()->json(json_decode($response,true));

    }

    public function sendDocumentsCelcoin(Request $request){

        $FunctionsAPIController = new FunctionsAPIController();
        $client = Clients::where("id",$request->client_id)->first();

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

        if($request->hasFile('arquivo')){

            if($request->file('arquivo')->isValid()){
                $nome = md5(date('Y-m-d H:i:s'));
                $extensao = $request->arquivo->extension();
                $nameFile = "{$nome}.{$extensao}";

                $upload = $request->arquivo->storeAs('upload/upcomprovante/', $nameFile);

            }
        }

        $nameFile = ( !empty($nameFile) ? $nameFile : Null );

        $total_file = '/var/www/html/fastpayments/storage/app/upload/upcomprovante/'.$nameFile;

        $cFile = curl_file_create($total_file);

        // return response()->json(["total_file" => $total_file]);

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apicorp.celcoin.com.br/celcoinkyc/document/v1/fileupload',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_POST => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array('filetype' => 'KYC_EXTERNO','documentnumber' => $request->document,'front'=> $cFile),
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Content-Type: multipart/form-data',
            'Authorization: Bearer '.$token
        ),
        ));

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);

            return response()->json(["error" => $error_msg,"status" => "erro ao enviar documento"]);
        }

        curl_close($curl);

        $resp = json_decode($response,true);

        return response()->json($resp);

    }

    public function sendCelcoinKYC(Request $request)
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
                "companyType" => "PJ",
                "businessAddress" => [
                    "postalCode" => $request['businessAddress']['postalCode'],
                    "street" => $request['businessAddress']['street'],
                    "number" => $request['businessAddress']['number'],
                    "addressComplement" => $request['businessAddress']['addressComplement'],
                    "neighborhood" => $request['businessAddress']['neighborhood'],
                    "state" => $request['businessAddress']['state'],
                    "city" => $request['businessAddress']['city']
                ],
                "onboardingType" => "BAAS",
                "clientCode" => $request['clientCode'],
                "contactNumber" => $request['contactNumber'],
                "documentNumber" => $request['documentNumber'],
                "businessEmail" => $request['businessEmail'],
                "businessName" => $request['businessName'],
                "tradingName" => $request['tradingName'],
                "owner" => [
                    "address" => [
                        "postalCode" => $request['owner'][0]['address']['postalCode'],
                        "street" => $request['owner'][0]['address']['street'],
                        "number" => $request['owner'][0]['address']['number'],
                        "neighborhood" => $request['owner'][0]['address']['neighborhood'],
                        "city" => $request['owner'][0]['address']['city'],
                        "state" => $request['owner'][0]['address']['state']
                    ],
                    "isPoliticallyExposedPerson" => false,
                    "ownerType" => "REPRESENTANTE",
                    "fullName" => $request['owner'][0]['fullName'],
                    "documentNumber" => $request['owner'][0]['documentNumber'],
                    "phoneNumber" => $request['owner'][0]['phoneNumber'],
                    "email" => $request['owner'][0]['email'],
                    "motherName" => $request['owner'][0]['motherName'],
                    "socialName" => $request['owner'][0]['socialName'],
                    "birthDate" => $request['owner'][0]['birthDate']
                ]
            ];

            $token = $token_celcoin['access_token'];

            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://apicorp.celcoin.com.br/onboarding/v1/onboarding-proposal/legal-person',
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

            if (curl_errno($curl)) {
                $error_msg = curl_error($curl);

                return response()->json(["error" => $error_msg]);
            }

            curl_close($curl);

            return $response;

        }else{

            $path_name = "fastlogs-token-celcoin-".date("Y-m-d");

            if (!file_exists('/var/www/html/nexapay/logs/'.$path_name)) {
                mkdir('/var/www/html/nexapay/logs/'.$path_name, 0777, true);
            }

            $payload = [
                "date" => date("Y-m-d H:i:s"),
                "client" => $client->name,
                "params" => $params,
                "retur_celcoin" => $token_celcoin
            ];

            $FunctionsAPIController->registerRecivedsRequests("/var/www/html/nexapay/logs/".$path_name."/tokencelcoinlog.txt",json_encode($payload));

            $ar = array(
                "code" => "558",
                "message" => "Erro on auth ".$clientCode,
            );

            return json_encode($ar);
        }
    }
}
