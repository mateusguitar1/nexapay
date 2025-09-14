<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\{Transactions,User,Clients,Banks};
use DB;

use App\Http\Controllers\FunctionsController;

class PerformWithdrawalPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transaction_id;
    protected $bank_withdraw_id;
    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($transaction_id,$bank_withdraw_id)
    {
        //
        $this->transaction_id = $transaction_id;
        $this->bank_withdraw_id = $bank_withdraw_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //

         $FunctionsController = new FunctionsController();

         $transaction = Transactions::where("id",$this->transaction_id)->first();
         $bank = Banks::where("id",$this->bank_withdraw_id)->first();
         $client = Clients::where("id",$transaction->client_id)->first();

         //   $url_callback = $client->key->url_callback_withdraw;
         $url_callback = 'https://webhook.site/62d9ff58-02e4-4ae2-b7ee-f50590e567f4';

         $user_account_data = json_decode(base64_decode($transaction->user_account_data),true);

         $returnISPB = $this->returnISPB(intval($user_account_data['bank_code']));

         if($returnISPB['ispb'] == "not found"){

            DB::beginTransaction();
            try{

                  $transaction->update([
                     "status" => "canceled",
                     "observation" => "ISPB not found",
                     "reason_status" => "ISPB not found",
                     "id_bank" => $bank->id
                  ]);

                  DB::commit();

            }catch(Exception $e){
                  DB::rollback();
            }

         }else{
            $ispb = $returnISPB['ispb'];
         }

         if(strlen($ispb) < 8){
            $cn = (8 - strlen($ispb));
            for($i = 0;$i <= ($cn - 1);$i++){
                  $ispb = "0".$ispb;
            }
         }

         $data = [
            "ida4p" => $transaction->id,
            "order_id" => $transaction->order_id,
            "user_name" => $user_account_data['name'],
            "user_document" => $user_account_data['document'],
            "ispb" => $ispb,
            "bank_code" => $user_account_data['bank_code'],
            "account_type" => $user_account_data['operation_bank'],
            "bank_agency" => $user_account_data['agency'],
            "bank_account_number" => $user_account_data['account_number'],
            "amount" => $transaction->amount_solicitation
         ];

         // Acesso BS2
         $username_bs2 = $bank->username_bs2;
         $password_bs2 = $bank->password_bs2;
         $client_id_bs2 = $bank->client_id_bs2;
         $client_secret_bs2 = $bank->client_secret_bs2;

         $tk = json_decode($FunctionsController->getTokenBS2PIX($client_id_bs2,$client_secret_bs2,"prod"),true);

         if(isset($tk['access_token'])){

            $token_bs2 = $tk['access_token'];

            $getPaymentId = json_decode($this->CreateManualPaymentId($data,$token_bs2),true);

            if(isset($getPaymentId['pagamentoId'])){

                  $paymentId = $getPaymentId['pagamentoId'];

                  $finish_payment = $this->ConfirmManualPaymentId($paymentId,$data,$token_bs2);

                  if($finish_payment['message'] == "success"){

                     DB::beginTransaction();
                     try{

                        $date_confirm = date("Y-m-d H:i:s");

                        $transaction->update([
                              "status" => "confirmed",
                              "id_bank" => $bank->id,
                              "paid_date" => $date_confirm,
                              "final_date" => $date_confirm,
                              "payment_id" => $paymentId,
                              "data_bank" => json_encode($getPaymentId,true)
                        ]);

                        DB::commit();

                     }catch(Exception $e){
                        DB::rollback();
                     }

                     array_push($getPaymentId,$finish_payment);

                     // set post fields
                     $post = [
                        "order_id" => $transaction->order_id,
                        "user_id" => $transaction->user_id,
                        "solicitation_date" => $transaction->solicitation_date,
                        "paid_date" => $date_confirm,
                        "code_identify" => $transaction->code,
                        "amount_solicitation" => $transaction->amount_solicitation,
                        "amount_confirmed" => $transaction->amount_solicitation,
                        "status" => "confirmed"
                     ];

                     $post_field = json_encode($post);

                     $ch = curl_init($url_callback);
                     curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                     curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field),'authorization:'.$client->key->authorization_withdraw));
                     curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

                     // execute!
                     $response = curl_exec($ch);
                     $http_status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                     // close the connection, release resources used
                     curl_close($ch);

                     $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/performwithdrawalpayment.txt",json_encode($post));

                     $post_field = json_encode(["transaction_id" => $transaction->id]);

                     $ch = curl_init("https://xdash.FastPayments.com/api/receiptWithdraw");
                     curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                     curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($post_field)));
                     curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);

                     // execute!
                     $responsenew = curl_exec($ch);
                     $http_status  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                     // close the connection, release resources used
                     curl_close($ch);

                     $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/sendreceiptfront.txt",json_encode($responsenew));

                  }else{

                     array_push($getPaymentId,$finish_payment);

                     $curl = curl_init();

                     curl_setopt_array($curl, array(
                     CURLOPT_URL => $url_callback,
                     CURLOPT_RETURNTRANSFER => true,
                     CURLOPT_ENCODING => '',
                     CURLOPT_MAXREDIRS => 10,
                     CURLOPT_TIMEOUT => 0,
                     CURLOPT_SSL_VERIFYHOST => 0,
                     CURLOPT_SSL_VERIFYPEER => 0,
                     CURLOPT_FOLLOWLOCATION => true,
                     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                     CURLOPT_CUSTOMREQUEST => 'POST',
                     CURLOPT_POSTFIELDS => json_encode($getPaymentId),
                     CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                     ),
                     ));

                     $response = curl_exec($curl);

                     curl_close($curl);

                     $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/performwithdrawalpaymentcancel.txt",json_encode($getPaymentId));

                  }

            }else{

                  $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/performwithdrawalpayment.txt",json_encode(['message' => 'error paymentId','return' => $getPaymentId]));

            }


         }else{

            $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/performwithdrawalpayment.txt",json_encode(['message' => 'error token','return' => $tk]));

         }

    }

    public function CreateManualPaymentId($data = array(),$token_bs2){

        $FunctionsController = new FunctionsController();

        $count_tp_doc = strlen($data['user_document']);
        if($count_tp_doc <= 11){
            $type_document = "CPF";
        }else{
            $type_document = "CNPJ";
        }

        switch($data['account_type']){
            case"corrente": $type_account = "ContaCorrente"; break;
            case"poupanca": $type_account = "Poupanca"; break;
            default: $type_account = "ContaCorrente";
        }


        $post = [
            "recebedor" => [
                "ispb" => $data['ispb'],
                "conta" => [
                    "agencia" => $data['bank_agency'],
                    "numero" => $data['bank_account_number'],
                    "tipo" => $type_account
                ],
                "pessoa" => [
                    "documento" => $data['user_document'],
                    "tipoDocumento" => $type_document,
                    "nome" => $data['user_name'],
                    "nomeFantasia" => $data['user_name'],
                ],
            ]
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.bs2.com/pix/direto/forintegration/v1/pagamentos/manual',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($post),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$token_bs2,
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/performwithdrawalpaymentsolicitation.txt",json_encode(json_decode($response,true)));

        return $response;

    }

    public function ConfirmManualPaymentId($paymentId,$data = array(),$token_bs2){

         $FunctionsController = new FunctionsController();

         $count_tp_doc = strlen($data['user_document']);
         if($count_tp_doc <= 11){
            $type_document = "CPF";
            $name_user = $data['user_name'];
            $social_user = null;
         }else{
            $type_document = "CNPJ";
         }

         switch($data['account_type']){
            case"corrente": $type_account = "ContaCorrente"; break;
            case"poupanca": $type_account = "Poupanca"; break;
            default: $type_account = "ContaCorrente";
         }


         $post = [
            "recebedor" => [
                  "ispb" => $data['ispb'],
                  "conta" => [
                     "agencia" => $data['bank_agency'],
                     "numero" => $data['bank_account_number'],
                     "tipo" => $type_account
                  ],
                  "pessoa" => [
                     "documento" => $data['user_document'],
                     "tipoDocumento" => $type_document,
                     "nome" => $data['user_name'],
                     "nomeFantasia" => $data['user_name'],
                  ],
            ],
            "valor" => floatval($data['amount']),
            "campoLivre" => "Pagamento ORDER ID ".$data['order_id']
         ];

         $curl = curl_init();

         curl_setopt_array($curl, array(
         CURLOPT_URL => 'https://api.bs2.com/pix/direto/forintegration/v1/pagamentos/'.$paymentId.'/confirmacao',
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_ENCODING => '',
         CURLOPT_MAXREDIRS => 10,
         CURLOPT_TIMEOUT => 0,
         CURLOPT_SSL_VERIFYHOST => 0,
         CURLOPT_SSL_VERIFYPEER => 0,
         CURLOPT_FOLLOWLOCATION => true,
         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         CURLOPT_CUSTOMREQUEST => 'POST',
         CURLOPT_POSTFIELDS => json_encode($post),
         CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$token_bs2,
         ),
         ));

         $response = curl_exec($curl);
         $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

         curl_close($curl);

         $FunctionsController->registerRecivedsRequests("/var/www/html/nexapay/logs/performwithdrawalpaymentconfirm.txt",json_encode(json_decode($response,true)));

         if($httpcode == 202){
            return ["message" => "success", "http_code" => $httpcode];
         }else{
            return ["message" => "error", "http_code" => $httpcode];
         }

    }

    public function returnISPB($bank_code){

        $json = '[
            {
               "bank_name":"BCO DO BRASIL S.A.",
               "bank_code":"1",
               "ispb":"0"
            },
            {
               "bank_name":"BCO DA AMAZONIA S.A.",
               "bank_code":"3",
               "ispb":"4902979"
            },
            {
               "bank_name":"BCO DO NORDESTE DO BRASIL S.A.",
               "bank_code":"4",
               "ispb":"7237373"
            },
            {
               "bank_name":"BNDES",
               "bank_code":"7",
               "ispb":"33657248"
            },
            {
               "bank_name":"CREDICOAMO",
               "bank_code":"10",
               "ispb":"81723108"
            },
            {
               "bank_name":"C.SUISSE HEDGING-GRIFFO CV S/A",
               "bank_code":"11",
               "ispb":"61809182"
            },
            {
               "bank_name":"BANCO INBURSA",
               "bank_code":"12",
               "ispb":"4866275"
            },
            {
               "bank_name":"STATE STREET BR S.A. BCO COMERCIAL",
               "bank_code":"14",
               "ispb":"9274232"
            },
            {
               "bank_name":"UBS BRASIL CCTVM S.A.",
               "bank_code":"15",
               "ispb":"2819125"
            },
            {
               "bank_name":"CCM DESP TRÂNS SC E RS",
               "bank_code":"16",
               "ispb":"4715685"
            },
            {
               "bank_name":"BNY MELLON BCO S.A.",
               "bank_code":"17",
               "ispb":"42272526"
            },
            {
               "bank_name":"BCO TRICURY S.A.",
               "bank_code":"18",
               "ispb":"57839805"
            },
            {
               "bank_name":"BCO BANESTES S.A.",
               "bank_code":"21",
               "ispb":"28127603"
            },
            {
               "bank_name":"BCO BANDEPE S.A.",
               "bank_code":"24",
               "ispb":"10866788"
            },
            {
               "bank_name":"BCO ALFA S.A.",
               "bank_code":"25",
               "ispb":"3323840"
            },
            {
               "bank_name":"BANCO ITAÚ CONSIGNADO S.A.",
               "bank_code":"29",
               "ispb":"33885724"
            },
            {
               "bank_name":"BCO SANTANDER (BRASIL) S.A.",
               "bank_code":"33",
               "ispb":"90400888"
            },
            {
               "bank_name":"BCO BBI S.A.",
               "bank_code":"36",
               "ispb":"6271464"
            },
            {
               "bank_name":"BCO DO EST. DO PA S.A.",
               "bank_code":"37",
               "ispb":"4913711"
            },
            {
               "bank_name":"BCO CARGILL S.A.",
               "bank_code":"40",
               "ispb":"3609817"
            },
            {
               "bank_name":"BCO DO ESTADO DO RS S.A.",
               "bank_code":"41",
               "ispb":"92702067"
            },
            {
               "bank_name":"BCO DO EST. DE SE S.A.",
               "bank_code":"47",
               "ispb":"13009717"
            },
            {
               "bank_name":"CONFIDENCE CC S.A.",
               "bank_code":"60",
               "ispb":"4913129"
            },
            {
               "bank_name":"HIPERCARD BM S.A.",
               "bank_code":"62",
               "ispb":"3012230"
            },
            {
               "bank_name":"BANCO BRADESCARD",
               "bank_code":"63",
               "ispb":"4184779"
            },
            {
               "bank_name":"GOLDMAN SACHS DO BRASIL BM S.A",
               "bank_code":"64",
               "ispb":"4332281"
            },
            {
               "bank_name":"BCO ANDBANK S.A.",
               "bank_code":"65",
               "ispb":"48795256"
            },
            {
               "bank_name":"BCO MORGAN STANLEY S.A.",
               "bank_code":"66",
               "ispb":"2801938"
            },
            {
               "bank_name":"BCO CREFISA S.A.",
               "bank_code":"69",
               "ispb":"61033106"
            },
            {
               "bank_name":"BRB - BCO DE BRASILIA S.A.",
               "bank_code":"70",
               "ispb":"208"
            },
            {
               "bank_name":"BCO. J.SAFRA S.A.",
               "bank_code":"74",
               "ispb":"3017677"
            },
            {
               "bank_name":"BCO ABN AMRO S.A.",
               "bank_code":"75",
               "ispb":"3532415"
            },
            {
               "bank_name":"BCO KDB BRASIL S.A.",
               "bank_code":"76",
               "ispb":"7656500"
            },
            {
               "bank_name":"BANCO INTER",
               "bank_code":"77",
               "ispb":"416968"
            },
            {
               "bank_name":"HAITONG BI DO BRASIL S.A.",
               "bank_code":"78",
               "ispb":"34111187"
            },
            {
               "bank_name":"BCO ORIGINAL DO AGRO S/A",
               "bank_code":"79",
               "ispb":"9516419"
            },
            {
               "bank_name":"B&T CC LTDA.",
               "bank_code":"80",
               "ispb":"73622748"
            },
            {
               "bank_name":"BANCOSEGURO S.A.",
               "bank_code":"81",
               "ispb":"10264663"
            },
            {
               "bank_name":"BANCO TOPÁZIO S.A.",
               "bank_code":"82",
               "ispb":"7679404"
            },
            {
               "bank_name":"BCO DA CHINA BRASIL S.A.",
               "bank_code":"83",
               "ispb":"10690848"
            },
            {
               "bank_name":"UNIPRIME DO BRASIL - CC LTDA.",
               "bank_code":"84",
               "ispb":"2398976"
            },
            {
               "bank_name":"COOPCENTRAL AILOS",
               "bank_code":"85",
               "ispb":"5463212"
            },
            {
               "bank_name":"BANCO RANDON S.A.",
               "bank_code":"88",
               "ispb":"11476673"
            },
            {
               "bank_name":"CREDISAN CC",
               "bank_code":"89",
               "ispb":"62109566"
            },
            {
               "bank_name":"BRK S.A. CFI",
               "bank_code":"92",
               "ispb":"12865507"
            },
            {
               "bank_name":"POLOCRED SCMEPP LTDA.",
               "bank_code":"93",
               "ispb":"7945233"
            },
            {
               "bank_name":"BANCO FINAXIS",
               "bank_code":"94",
               "ispb":"11758741"
            },
            {
               "bank_name":"TRAVELEX BANCO DE CÂMBIO S.A.",
               "bank_code":"95",
               "ispb":"11703662"
            },
            {
               "bank_name":"BCO B3 S.A.",
               "bank_code":"96",
               "ispb":"997185"
            },
            {
               "bank_name":"CREDISIS CENTRAL DE COOPERATIVAS DE CRÉDITO LTDA.",
               "bank_code":"97",
               "ispb":"4632856"
            },
            {
               "bank_name":"CREDIALIANÇA CCR",
               "bank_code":"98",
               "ispb":"78157146"
            },
            {
               "bank_name":"UNIPRIME CENTRAL CCC LTDA.",
               "bank_code":"99",
               "ispb":"3046391"
            },
            {
               "bank_name":"PLANNER CV S.A.",
               "bank_code":"100",
               "ispb":"806535"
            },
            {
               "bank_name":"RENASCENCA DTVM LTDA",
               "bank_code":"101",
               "ispb":"62287735"
            },
            {
               "bank_name":"XP INVESTIMENTOS CCTVM S/A",
               "bank_code":"102",
               "ispb":"2332886"
            },
            {
               "bank_name":"CAIXA ECONOMICA FEDERAL",
               "bank_code":"104",
               "ispb":"360305"
            },
            {
               "bank_name":"LECCA CFI S.A.",
               "bank_code":"105",
               "ispb":"7652226"
            },
            {
               "bank_name":"BCO BOCOM BBM S.A.",
               "bank_code":"107",
               "ispb":"15114366"
            },
            {
               "bank_name":"PORTOCRED S.A. - CFI",
               "bank_code":"108",
               "ispb":"1800019"
            },
            {
               "bank_name":"OLIVEIRA TRUST DTVM S.A.",
               "bank_code":"111",
               "ispb":"36113876"
            },
            {
               "bank_name":"MAGLIANO S ACTVM",
               "bank_code":"113",
               "ispb":"61723847"
            },
            {
               "bank_name":"CENTRAL COOPERATIVA DE CRÉDITO NO ESTADO DO ESPÍRITO SANTO",
               "bank_code":"114",
               "ispb":"5790149"
            },
            {
               "bank_name":"ADVANCED CC LTDA",
               "bank_code":"117",
               "ispb":"92856905"
            },
            {
               "bank_name":"BCO WESTERN UNION",
               "bank_code":"119",
               "ispb":"13720915"
            },
            {
               "bank_name":"BCO RODOBENS S.A.",
               "bank_code":"120",
               "ispb":"33603457"
            },
            {
               "bank_name":"BCO AGIBANK S.A.",
               "bank_code":"121",
               "ispb":"10664513"
            },
            {
               "bank_name":"BCO BRADESCO BERJ S.A.",
               "bank_code":"122",
               "ispb":"33147315"
            },
            {
               "bank_name":"BCO WOORI BANK DO BRASIL S.A.",
               "bank_code":"124",
               "ispb":"15357060"
            },
            {
               "bank_name":"BANCO GENIAL",
               "bank_code":"125",
               "ispb":"45246410"
            },
            {
               "bank_name":"BR PARTNERS BI",
               "bank_code":"126",
               "ispb":"13220493"
            },
            {
               "bank_name":"CODEPE CVC S.A.",
               "bank_code":"127",
               "ispb":"9512542"
            },
            {
               "bank_name":"MS BANK S.A. BCO DE CÂMBIO",
               "bank_code":"128",
               "ispb":"19307785"
            },
            {
               "bank_name":"UBS BRASIL BI S.A.",
               "bank_code":"129",
               "ispb":"18520834"
            },
            {
               "bank_name":"CARUANA SCFI",
               "bank_code":"130",
               "ispb":"9313766"
            },
            {
               "bank_name":"TULLETT PREBON BRASIL CVC LTDA",
               "bank_code":"131",
               "ispb":"61747085"
            },
            {
               "bank_name":"ICBC DO BRASIL BM S.A.",
               "bank_code":"132",
               "ispb":"17453575"
            },
            {
               "bank_name":"CRESOL CONFEDERAÇÃO",
               "bank_code":"133",
               "ispb":"10398952"
            },
            {
               "bank_name":"BGC LIQUIDEZ DTVM LTDA",
               "bank_code":"134",
               "ispb":"33862244"
            },
            {
               "bank_name":"CONF NAC COOP CENTRAIS UNICRED",
               "bank_code":"136",
               "ispb":"315557"
            },
            {
               "bank_name":"GET MONEY CC LTDA",
               "bank_code":"138",
               "ispb":"10853017"
            },
            {
               "bank_name":"INTESA SANPAOLO BRASIL S.A. BM",
               "bank_code":"139",
               "ispb":"55230916"
            },
            {
               "bank_name":"NU INVEST CORRETORA DE VALORES S.A.",
               "bank_code":"140",
               "ispb":"62169875"
            },
            {
               "bank_name":"BROKER BRASIL CC LTDA.",
               "bank_code":"142",
               "ispb":"16944141"
            },
            {
               "bank_name":"TREVISO CC S.A.",
               "bank_code":"143",
               "ispb":"2992317"
            },
            {
               "bank_name":"BEXS BCO DE CAMBIO S.A.",
               "bank_code":"144",
               "ispb":"13059145"
            },
            {
               "bank_name":"LEVYCAM CCV LTDA",
               "bank_code":"145",
               "ispb":"50579044"
            },
            {
               "bank_name":"GUITTA CC LTDA",
               "bank_code":"146",
               "ispb":"24074692"
            },
            {
               "bank_name":"FACTA S.A. CFI",
               "bank_code":"149",
               "ispb":"15581638"
            },
            {
               "bank_name":"ICAP DO BRASIL CTVM LTDA.",
               "bank_code":"157",
               "ispb":"9105360"
            },
            {
               "bank_name":"CASA CREDITO S.A. SCM",
               "bank_code":"159",
               "ispb":"5442029"
            },
            {
               "bank_name":"COMMERZBANK BRASIL S.A. - BCO MÚLTIPLO",
               "bank_code":"163",
               "ispb":"23522214"
            },
            {
               "bank_name":"BRL TRUST DTVM SA",
               "bank_code":"173",
               "ispb":"13486793"
            },
            {
               "bank_name":"PEFISA S.A. - C.F.I.",
               "bank_code":"174",
               "ispb":"43180355"
            },
            {
               "bank_name":"GUIDE",
               "bank_code":"177",
               "ispb":"65913436"
            },
            {
               "bank_name":"CM CAPITAL MARKETS CCTVM LTDA",
               "bank_code":"180",
               "ispb":"2685483"
            },
            {
               "bank_name":"SOCRED SA - SCMEPP",
               "bank_code":"183",
               "ispb":"9210106"
            },
            {
               "bank_name":"BCO ITAÚ BBA S.A.",
               "bank_code":"184",
               "ispb":"17298092"
            },
            {
               "bank_name":"ATIVA S.A. INVESTIMENTOS CCTVM",
               "bank_code":"188",
               "ispb":"33775974"
            },
            {
               "bank_name":"HS FINANCEIRA",
               "bank_code":"189",
               "ispb":"7512441"
            },
            {
               "bank_name":"SERVICOOP",
               "bank_code":"190",
               "ispb":"3973814"
            },
            {
               "bank_name":"NOVA FUTURA CTVM LTDA.",
               "bank_code":"191",
               "ispb":"4257795"
            },
            {
               "bank_name":"PARMETAL DTVM LTDA",
               "bank_code":"194",
               "ispb":"20155248"
            },
            {
               "bank_name":"VALOR SCD S.A.",
               "bank_code":"195",
               "ispb":"7799277"
            },
            {
               "bank_name":"FAIR CC S.A.",
               "bank_code":"196",
               "ispb":"32648370"
            },
            {
               "bank_name":"STONE PAGAMENTOS S.A.",
               "bank_code":"197",
               "ispb":"16501555"
            },
            {
               "bank_name":"BANCO BTG PACTUAL S.A.",
               "bank_code":"208",
               "ispb":"30306294"
            },
            {
               "bank_name":"BANCO ORIGINAL",
               "bank_code":"212",
               "ispb":"92894922"
            },
            {
               "bank_name":"BCO ARBI S.A.",
               "bank_code":"213",
               "ispb":"54403563"
            },
            {
               "bank_name":"BANCO JOHN DEERE S.A.",
               "bank_code":"217",
               "ispb":"91884981"
            },
            {
               "bank_name":"BCO BS2 S.A.",
               "bank_code":"218",
               "ispb":"71027866"
            },
            {
               "bank_name":"BCO CRÉDIT AGRICOLE BR S.A.",
               "bank_code":"222",
               "ispb":"75647891"
            },
            {
               "bank_name":"BCO FIBRA S.A.",
               "bank_code":"224",
               "ispb":"58616418"
            },
            {
               "bank_name":"BANCO CIFRA",
               "bank_code":"233",
               "ispb":"62421979"
            },
            {
               "bank_name":"BCO BRADESCO S.A.",
               "bank_code":"237",
               "ispb":"60746948"
            },
            {
               "bank_name":"BCO CLASSICO S.A.",
               "bank_code":"241",
               "ispb":"31597552"
            },
            {
               "bank_name":"BANCO MASTER",
               "bank_code":"243",
               "ispb":"33923798"
            },
            {
               "bank_name":"BCO ABC BRASIL S.A.",
               "bank_code":"246",
               "ispb":"28195667"
            },
            {
               "bank_name":"BANCO INVESTCRED UNIBANCO S.A.",
               "bank_code":"249",
               "ispb":"61182408"
            },
            {
               "bank_name":"BCV - BCO; CRÉDITO E VAREJO S.A.",
               "bank_code":"250",
               "ispb":"50585090"
            },
            {
               "bank_name":"BEXS CC S.A.",
               "bank_code":"253",
               "ispb":"52937216"
            },
            {
               "bank_name":"PARANA BCO S.A.",
               "bank_code":"254",
               "ispb":"14388334"
            },
            {
               "bank_name":"MONEYCORP BCO DE CÂMBIO S.A.",
               "bank_code":"259",
               "ispb":"8609934"
            },
            {
               "bank_name":"NU PAGAMENTOS - IP",
               "bank_code":"260",
               "ispb":"18236120"
            },
            {
               "bank_name":"BCO FATOR S.A.",
               "bank_code":"265",
               "ispb":"33644196"
            },
            {
               "bank_name":"BCO CEDULA S.A.",
               "bank_code":"266",
               "ispb":"33132044"
            },
            {
               "bank_name":"BARI CIA HIPOTECÁRIA",
               "bank_code":"268",
               "ispb":"14511781"
            },
            {
               "bank_name":"BCO HSBC S.A.",
               "bank_code":"269",
               "ispb":"53518684"
            },
            {
               "bank_name":"SAGITUR CC LTDA",
               "bank_code":"270",
               "ispb":"61444949"
            },
            {
               "bank_name":"IB CCTVM S.A.",
               "bank_code":"271",
               "ispb":"27842177"
            },
            {
               "bank_name":"AGK CC S.A.",
               "bank_code":"272",
               "ispb":"250699"
            },
            {
               "bank_name":"CCR DE SÃO MIGUEL DO OESTE",
               "bank_code":"273",
               "ispb":"8253539"
            },
            {
               "bank_name":"MONEY PLUS SCMEPP LTDA",
               "bank_code":"274",
               "ispb":"11581339"
            },
            {
               "bank_name":"BCO SENFF S.A.",
               "bank_code":"276",
               "ispb":"11970623"
            },
            {
               "bank_name":"GENIAL INVESTIMENTOS CVM S.A.",
               "bank_code":"278",
               "ispb":"27652684"
            },
            {
               "bank_name":"CCR DE PRIMAVERA DO LESTE",
               "bank_code":"279",
               "ispb":"26563270"
            },
            {
               "bank_name":"WILL FINANCEIRA S.A.CFI",
               "bank_code":"280",
               "ispb":"23862762"
            },
            {
               "bank_name":"CCR COOPAVEL",
               "bank_code":"281",
               "ispb":"76461557"
            },
            {
               "bank_name":"RB INVESTIMENTOS DTVM LTDA.",
               "bank_code":"283",
               "ispb":"89960090"
            },
            {
               "bank_name":"FRENTE CC LTDA.",
               "bank_code":"285",
               "ispb":"71677850"
            },
            {
               "bank_name":"CCR DE OURO",
               "bank_code":"286",
               "ispb":"7853842"
            },
            {
               "bank_name":"CAROL DTVM LTDA.",
               "bank_code":"288",
               "ispb":"62237649"
            },
            {
               "bank_name":"DECYSEO CC LTDA.",
               "bank_code":"289",
               "ispb":"94968518"
            },
            {
               "bank_name":"PAGSEGURO S.A.",
               "bank_code":"290",
               "ispb":"8561701"
            },
            {
               "bank_name":"BS2 DTVM S.A.",
               "bank_code":"292",
               "ispb":"28650236"
            },
            {
               "bank_name":"LASTRO RDV DTVM LTDA",
               "bank_code":"293",
               "ispb":"71590442"
            },
            {
               "bank_name":"VISION S.A. CC",
               "bank_code":"296",
               "ispb":"4062902"
            },
            {
               "bank_name":"VIPS CC LTDA.",
               "bank_code":"298",
               "ispb":"17772370"
            },
            {
               "bank_name":"BCO SOROCRED S.A. - BM",
               "bank_code":"299",
               "ispb":"4814563"
            },
            {
               "bank_name":"BCO LA NACION ARGENTINA",
               "bank_code":"300",
               "ispb":"33042151"
            },
            {
               "bank_name":"BPP IP S.A.",
               "bank_code":"301",
               "ispb":"13370835"
            },
            {
               "bank_name":"PORTOPAR DTVM LTDA",
               "bank_code":"306",
               "ispb":"40303299"
            },
            {
               "bank_name":"TERRA INVESTIMENTOS DTVM",
               "bank_code":"307",
               "ispb":"3751794"
            },
            {
               "bank_name":"CAMBIONET CC LTDA",
               "bank_code":"309",
               "ispb":"14190547"
            },
            {
               "bank_name":"VORTX DTVM LTDA.",
               "bank_code":"310",
               "ispb":"22610500"
            },
            {
               "bank_name":"DOURADA CORRETORA",
               "bank_code":"311",
               "ispb":"76641497"
            },
            {
               "bank_name":"HSCM SCMEPP LTDA.",
               "bank_code":"312",
               "ispb":"7693858"
            },
            {
               "bank_name":"AMAZÔNIA CC LTDA.",
               "bank_code":"313",
               "ispb":"16927221"
            },
            {
               "bank_name":"PI DTVM S.A.",
               "bank_code":"315",
               "ispb":"3502968"
            },
            {
               "bank_name":"BCO BMG S.A.",
               "bank_code":"318",
               "ispb":"61186680"
            },
            {
               "bank_name":"OM DTVM LTDA",
               "bank_code":"319",
               "ispb":"11495073"
            },
            {
               "bank_name":"BCO CCB BRASIL S.A.",
               "bank_code":"320",
               "ispb":"7450604"
            },
            {
               "bank_name":"CREFAZ SCMEPP LTDA",
               "bank_code":"321",
               "ispb":"18188384"
            },
            {
               "bank_name":"CCR DE ABELARDO LUZ",
               "bank_code":"322",
               "ispb":"1073966"
            },
            {
               "bank_name":"MERCADO PAGO",
               "bank_code":"323",
               "ispb":"10573521"
            },
            {
               "bank_name":"CARTOS SCD S.A.",
               "bank_code":"324",
               "ispb":"21332862"
            },
            {
               "bank_name":"ÓRAMA DTVM S.A.",
               "bank_code":"325",
               "ispb":"13293225"
            },
            {
               "bank_name":"PARATI - CFI S.A.",
               "bank_code":"326",
               "ispb":"3311443"
            },
            {
               "bank_name":"CECM FABRIC CALÇADOS SAPIRANGA",
               "bank_code":"328",
               "ispb":"5841967"
            },
            {
               "bank_name":"QI SCD S.A.",
               "bank_code":"329",
               "ispb":"32402502"
            },
            {
               "bank_name":"BANCO BARI S.A.",
               "bank_code":"330",
               "ispb":"556603"
            },
            {
               "bank_name":"FRAM CAPITAL DTVM S.A.",
               "bank_code":"331",
               "ispb":"13673855"
            },
            {
               "bank_name":"ACESSO SOLUCOES PAGAMENTO SA",
               "bank_code":"332",
               "ispb":"13140088"
            },
            {
               "bank_name":"BANCO DIGIO",
               "bank_code":"335",
               "ispb":"27098060"
            },
            {
               "bank_name":"BCO C6 S.A.",
               "bank_code":"336",
               "ispb":"31872495"
            },
            {
               "bank_name":"SUPER PAGAMENTOS E ADMINISTRACAO DE MEIOS ELETRONICOS S.A.",
               "bank_code":"340",
               "ispb":"9554480"
            },
            {
               "bank_name":"ITAÚ UNIBANCO S.A.",
               "bank_code":"341",
               "ispb":"60701190"
            },
            {
               "bank_name":"CREDITAS SCD",
               "bank_code":"342",
               "ispb":"32997490"
            },
            {
               "bank_name":"FFA SCMEPP LTDA.",
               "bank_code":"343",
               "ispb":"24537861"
            },
            {
               "bank_name":"BCO XP S.A.",
               "bank_code":"348",
               "ispb":"33264668"
            },
            {
               "bank_name":"AL5 S.A. CFI",
               "bank_code":"349",
               "ispb":"27214112"
            },
            {
               "bank_name":"CREHNOR LARANJEIRAS",
               "bank_code":"350",
               "ispb":"1330387"
            },
            {
               "bank_name":"TORO CTVM S.A.",
               "bank_code":"352",
               "ispb":"29162769"
            },
            {
               "bank_name":"NECTON INVESTIMENTOS S.A CVM",
               "bank_code":"354",
               "ispb":"52904364"
            },
            {
               "bank_name":"ÓTIMO SCD S.A.",
               "bank_code":"355",
               "ispb":"34335592"
            },
            {
               "bank_name":"MIDWAY S.A. - SCFI",
               "bank_code":"358",
               "ispb":"9464032"
            },
            {
               "bank_name":"ZEMA CFI S/A",
               "bank_code":"359",
               "ispb":"5351887"
            },
            {
               "bank_name":"TRINUS CAPITAL DTVM",
               "bank_code":"360",
               "ispb":"2276653"
            },
            {
               "bank_name":"CIELO S.A.",
               "bank_code":"362",
               "ispb":"1027058"
            },
            {
               "bank_name":"SINGULARE CTVM S.A.",
               "bank_code":"363",
               "ispb":"62285390"
            },
            {
               "bank_name":"GERENCIANET",
               "bank_code":"364",
               "ispb":"9089356"
            },
            {
               "bank_name":"SIMPAUL",
               "bank_code":"365",
               "ispb":"68757681"
            },
            {
               "bank_name":"BCO SOCIETE GENERALE BRASIL",
               "bank_code":"366",
               "ispb":"61533584"
            },
            {
               "bank_name":"VITREO DTVM S.A.",
               "bank_code":"367",
               "ispb":"34711571"
            },
            {
               "bank_name":"BCO CSF S.A.",
               "bank_code":"368",
               "ispb":"8357240"
            },
            {
               "bank_name":"BCO MIZUHO S.A.",
               "bank_code":"370",
               "ispb":"61088183"
            },
            {
               "bank_name":"WARREN CVMC LTDA",
               "bank_code":"371",
               "ispb":"92875780"
            },
            {
               "bank_name":"UP.P SEP S.A.",
               "bank_code":"373",
               "ispb":"35977097"
            },
            {
               "bank_name":"REALIZE CFI S.A.",
               "bank_code":"374",
               "ispb":"27351731"
            },
            {
               "bank_name":"BCO J.P. MORGAN S.A.",
               "bank_code":"376",
               "ispb":"33172537"
            },
            {
               "bank_name":"BMS SCD S.A.",
               "bank_code":"377",
               "ispb":"17826860"
            },
            {
               "bank_name":"BBC LEASING",
               "bank_code":"378",
               "ispb":"1852137"
            },
            {
               "bank_name":"CECM COOPERFORTE",
               "bank_code":"379",
               "ispb":"1658426"
            },
            {
               "bank_name":"PICPAY",
               "bank_code":"380",
               "ispb":"22896431"
            },
            {
               "bank_name":"BCO MERCEDES-BENZ S.A.",
               "bank_code":"381",
               "ispb":"60814191"
            },
            {
               "bank_name":"FIDUCIA SCMEPP LTDA",
               "bank_code":"382",
               "ispb":"4307598"
            },
            {
               "bank_name":"JUNO",
               "bank_code":"383",
               "ispb":"21018182"
            },
            {
               "bank_name":"GLOBAL SCM LTDA",
               "bank_code":"384",
               "ispb":"11165756"
            },
            {
               "bank_name":"NU FINANCEIRA S.A. CFI",
               "bank_code":"386",
               "ispb":"30680829"
            },
            {
               "bank_name":"BCO TOYOTA DO BRASIL S.A.",
               "bank_code":"387",
               "ispb":"3215790"
            },
            {
               "bank_name":"BCO MERCANTIL DO BRASIL S.A.",
               "bank_code":"389",
               "ispb":"17184037"
            },
            {
               "bank_name":"BCO GM S.A.",
               "bank_code":"390",
               "ispb":"59274605"
            },
            {
               "bank_name":"CCR DE IBIAM",
               "bank_code":"391",
               "ispb":"8240446"
            },
            {
               "bank_name":"BCO VOLKSWAGEN S.A",
               "bank_code":"393",
               "ispb":"59109165"
            },
            {
               "bank_name":"BCO BRADESCO FINANC. S.A.",
               "bank_code":"394",
               "ispb":"7207996"
            },
            {
               "bank_name":"F D GOLD DTVM LTDA",
               "bank_code":"395",
               "ispb":"8673569"
            },
            {
               "bank_name":"HUB PAGAMENTOS",
               "bank_code":"396",
               "ispb":"13884775"
            },
            {
               "bank_name":"LISTO SCD S.A.",
               "bank_code":"397",
               "ispb":"34088029"
            },
            {
               "bank_name":"IDEAL CTVM S.A.",
               "bank_code":"398",
               "ispb":"31749596"
            },
            {
               "bank_name":"KIRTON BANK",
               "bank_code":"399",
               "ispb":"1701201"
            },
            {
               "bank_name":"COOP CREDITAG",
               "bank_code":"400",
               "ispb":"5491616"
            },
            {
               "bank_name":"IUGU IP S.A.",
               "bank_code":"401",
               "ispb":"15111975"
            },
            {
               "bank_name":"COBUCCIO SCD S.A.",
               "bank_code":"402",
               "ispb":"36947229"
            },
            {
               "bank_name":"CORA SCD S.A.",
               "bank_code":"403",
               "ispb":"37880206"
            },
            {
               "bank_name":"SUMUP SCD S.A.",
               "bank_code":"404",
               "ispb":"37241230"
            },
            {
               "bank_name":"ACCREDITO SCD S.A.",
               "bank_code":"406",
               "ispb":"37715993"
            },
            {
               "bank_name":"ÍNDIGO INVESTIMENTOS DTVM LTDA.",
               "bank_code":"407",
               "ispb":"329598"
            },
            {
               "bank_name":"BONUSPAGO SCD S.A.",
               "bank_code":"408",
               "ispb":"36586946"
            },
            {
               "bank_name":"PLANNER SCM S.A.",
               "bank_code":"410",
               "ispb":"5684234"
            },
            {
               "bank_name":"VIA CERTA FINANCIADORA S.A. - CFI",
               "bank_code":"411",
               "ispb":"5192316"
            },
            {
               "bank_name":"BCO CAPITAL S.A.",
               "bank_code":"412",
               "ispb":"15173776"
            },
            {
               "bank_name":"BCO BV S.A.",
               "bank_code":"413",
               "ispb":"1858774"
            },
            {
               "bank_name":"WORK SCD S.A.",
               "bank_code":"414",
               "ispb":"37526080"
            },
            {
               "bank_name":"LAMARA SCD S.A.",
               "bank_code":"416",
               "ispb":"19324634"
            },
            {
               "bank_name":"ZIPDIN SCD S.A.",
               "bank_code":"418",
               "ispb":"37414009"
            },
            {
               "bank_name":"NUMBRS SCD S.A.",
               "bank_code":"419",
               "ispb":"38129006"
            },
            {
               "bank_name":"CC LAR CREDI",
               "bank_code":"421",
               "ispb":"39343350"
            },
            {
               "bank_name":"BCO SAFRA S.A.",
               "bank_code":"422",
               "ispb":"58160789"
            },
            {
               "bank_name":"COLUNA S.A. DTVM",
               "bank_code":"423",
               "ispb":"460065"
            },
            {
               "bank_name":"SOCINAL S.A. CFI",
               "bank_code":"425",
               "ispb":"3881423"
            },
            {
               "bank_name":"BIORC FINANCEIRA - CFI S.A.",
               "bank_code":"426",
               "ispb":"11285104"
            },
            {
               "bank_name":"CRED-UFES",
               "bank_code":"427",
               "ispb":"27302181"
            },
            {
               "bank_name":"CRED-SYSTEM SCD S.A.",
               "bank_code":"428",
               "ispb":"39664698"
            },
            {
               "bank_name":"CREDIARE CFI S.A.",
               "bank_code":"429",
               "ispb":"5676026"
            },
            {
               "bank_name":"CCR SEARA",
               "bank_code":"430",
               "ispb":"204963"
            },
            {
               "bank_name":"BR-CAPITAL DTVM S.A.",
               "bank_code":"433",
               "ispb":"44077014"
            },
            {
               "bank_name":"DELCRED SCD S.A.",
               "bank_code":"435",
               "ispb":"38224857"
            },
            {
               "bank_name":"PLANNER TRUSTEE DTVM LTDA",
               "bank_code":"438",
               "ispb":"67030395"
            },
            {
               "bank_name":"ID CTVM",
               "bank_code":"439",
               "ispb":"16695922"
            },
            {
               "bank_name":"CREDIBRF COOP",
               "bank_code":"440",
               "ispb":"82096447"
            },
            {
               "bank_name":"MAGNETIS - DTVM",
               "bank_code":"442",
               "ispb":"87963450"
            },
            {
               "bank_name":"TRINUS SCD S.A.",
               "bank_code":"444",
               "ispb":"40654622"
            },
            {
               "bank_name":"PLANTAE CFI",
               "bank_code":"445",
               "ispb":"35551187"
            },
            {
               "bank_name":"MIRAE ASSET CCTVM LTDA",
               "bank_code":"447",
               "ispb":"12392983"
            },
            {
               "bank_name":"HEMERA DTVM LTDA.",
               "bank_code":"448",
               "ispb":"39669186"
            },
            {
               "bank_name":"DMCARD SCD S.A.",
               "bank_code":"449",
               "ispb":"37555231"
            },
            {
               "bank_name":"FITBANK PAGAMENTOS ELETRONICOS S.A.",
               "bank_code":"450",
               "ispb":"13203354"
            },
            {
               "bank_name":"CREDIFIT SCD S.A.",
               "bank_code":"452",
               "ispb":"39676772"
            },
            {
               "bank_name":"BCO MUFG BRASIL S.A.",
               "bank_code":"456",
               "ispb":"60498557"
            },
            {
               "bank_name":"UY3 SCD S/A",
               "bank_code":"457",
               "ispb":"39587424"
            },
            {
               "bank_name":"HEDGE INVESTMENTS DTVM LTDA.",
               "bank_code":"458",
               "ispb":"7253654"
            },
            {
               "bank_name":"CCM SERV. PÚBLICOS SP",
               "bank_code":"459",
               "ispb":"4546162"
            },
            {
               "bank_name":"ASAAS IP S.A.",
               "bank_code":"461",
               "ispb":"19540550"
            },
            {
               "bank_name":"STARK SCD S.A.",
               "bank_code":"462",
               "ispb":"39908427"
            },
            {
               "bank_name":"BCO SUMITOMO MITSUI BRASIL S.A.",
               "bank_code":"464",
               "ispb":"60518222"
            },
            {
               "bank_name":"BCO CAIXA GERAL BRASIL S.A.",
               "bank_code":"473",
               "ispb":"33466988"
            },
            {
               "bank_name":"CITIBANK N.A.",
               "bank_code":"477",
               "ispb":"33042953"
            },
            {
               "bank_name":"BCO ITAUBANK S.A.",
               "bank_code":"479",
               "ispb":"60394079"
            },
            {
               "bank_name":"DEUTSCHE BANK S.A.BCO ALEMAO",
               "bank_code":"487",
               "ispb":"62331228"
            },
            {
               "bank_name":"JPMORGAN CHASE BANK",
               "bank_code":"488",
               "ispb":"46518205"
            },
            {
               "bank_name":"ING BANK N.V.",
               "bank_code":"492",
               "ispb":"49336860"
            },
            {
               "bank_name":"BCO LA PROVINCIA B AIRES BCE",
               "bank_code":"495",
               "ispb":"44189447"
            },
            {
               "bank_name":"BCO CREDIT SUISSE S.A.",
               "bank_code":"505",
               "ispb":"32062580"
            },
            {
               "bank_name":"SENSO CCVM S.A.",
               "bank_code":"545",
               "ispb":"17352220"
            },
            {
               "bank_name":"BCO LUSO BRASILEIRO S.A.",
               "bank_code":"600",
               "ispb":"59118133"
            },
            {
               "bank_name":"BCO INDUSTRIAL DO BRASIL S.A.",
               "bank_code":"604",
               "ispb":"31895683"
            },
            {
               "bank_name":"BCO VR S.A.",
               "bank_code":"610",
               "ispb":"78626983"
            },
            {
               "bank_name":"BCO PAULISTA S.A.",
               "bank_code":"611",
               "ispb":"61820817"
            },
            {
               "bank_name":"BCO GUANABARA S.A.",
               "bank_code":"612",
               "ispb":"31880826"
            },
            {
               "bank_name":"OMNI BANCO S.A.",
               "bank_code":"613",
               "ispb":"60850229"
            },
            {
               "bank_name":"BANCO PAN",
               "bank_code":"623",
               "ispb":"59285411"
            },
            {
               "bank_name":"BCO C6 CONSIG",
               "bank_code":"626",
               "ispb":"61348538"
            },
            {
               "bank_name":"BCO LETSBANK S.A.",
               "bank_code":"630",
               "ispb":"58497702"
            },
            {
               "bank_name":"BCO RENDIMENTO S.A.",
               "bank_code":"633",
               "ispb":"68900810"
            },
            {
               "bank_name":"BCO TRIANGULO S.A.",
               "bank_code":"634",
               "ispb":"17351180"
            },
            {
               "bank_name":"BCO SOFISA S.A.",
               "bank_code":"637",
               "ispb":"60889128"
            },
            {
               "bank_name":"BCO PINE S.A.",
               "bank_code":"643",
               "ispb":"62144175"
            },
            {
               "bank_name":"ITAÚ UNIBANCO HOLDING S.A.",
               "bank_code":"652",
               "ispb":"60872504"
            },
            {
               "bank_name":"BANCO VOITER",
               "bank_code":"653",
               "ispb":"61024352"
            },
            {
               "bank_name":"BCO DIGIMAIS S.A.",
               "bank_code":"654",
               "ispb":"92874270"
            },
            {
               "bank_name":"BCO VOTORANTIM S.A.",
               "bank_code":"655",
               "ispb":"59588111"
            },
            {
               "bank_name":"BCO DAYCOVAL S.A",
               "bank_code":"707",
               "ispb":"62232889"
            },
            {
               "bank_name":"BCO OURINVEST S.A.",
               "bank_code":"712",
               "ispb":"78632767"
            },
            {
               "bank_name":"BCO RNX S.A.",
               "bank_code":"720",
               "ispb":"80271455"
            },
            {
               "bank_name":"BCO CETELEM S.A.",
               "bank_code":"739",
               "ispb":"558456"
            },
            {
               "bank_name":"BCO RIBEIRAO PRETO S.A.",
               "bank_code":"741",
               "ispb":"517645"
            },
            {
               "bank_name":"BANCO SEMEAR",
               "bank_code":"743",
               "ispb":"795423"
            },
            {
               "bank_name":"BCO CITIBANK S.A.",
               "bank_code":"745",
               "ispb":"33479023"
            },
            {
               "bank_name":"BCO MODAL S.A.",
               "bank_code":"746",
               "ispb":"30723886"
            },
            {
               "bank_name":"BCO RABOBANK INTL BRASIL S.A.",
               "bank_code":"747",
               "ispb":"1023570"
            },
            {
               "bank_name":"BCO COOPERATIVO SICREDI S.A.",
               "bank_code":"748",
               "ispb":"1181521"
            },
            {
               "bank_name":"SCOTIABANK BRASIL",
               "bank_code":"751",
               "ispb":"29030467"
            },
            {
               "bank_name":"BCO BNP PARIBAS BRASIL S A",
               "bank_code":"752",
               "ispb":"1522368"
            },
            {
               "bank_name":"NOVO BCO CONTINENTAL S.A. - BM",
               "bank_code":"753",
               "ispb":"74828799"
            },
            {
               "bank_name":"BANCO SISTEMA",
               "bank_code":"754",
               "ispb":"76543115"
            },
            {
               "bank_name":"BOFA MERRILL LYNCH BM S.A.",
               "bank_code":"755",
               "ispb":"62073200"
            },
            {
               "bank_name":"BANCO SICOOB S.A.",
               "bank_code":"756",
               "ispb":"2038232"
            },
            {
               "bank_name":"BCO KEB HANA DO BRASIL S.A.",
               "bank_code":"757",
               "ispb":"2318507"
            }
         ]';

        $array = json_decode($json,true);

        foreach($array as $bk){

            if($bk['bank_code'] == $bank_code){
                return ['ispb' => $bk['ispb']];
            }

        }

        return ['ispb' => "not found"];

    }
}
