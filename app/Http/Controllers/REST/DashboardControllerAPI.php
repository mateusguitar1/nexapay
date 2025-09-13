<?php

namespace App\Http\Controllers\REST;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use \App\Models\{Extract,Clients,Banks};

class DashboardControllerAPI extends Controller
{
    //
    public function index(Request $request){

        $permition = auth()->user()->especificPermition();

        if(auth()->user()->level == 'merchant'){
            if($permition['permition'] == "no"){
                return redirect("/transactions");
            }
        }

        $start = date("Y-m-d 00:00:00");
        $end = date("Y-m-d 23:59:59");

        if(auth()->user()->level == "master"){

            $cashin = Extract::where("type_transaction_extract","cash-in")
            ->whereBetween("created_at",[$start,$end])
            ->get();

            $amount_cashin = $cashin->sum("final_amount");
            $quantity_cashin = $cashin->count();

            $cashout = Extract::where("type_transaction_extract","cash-out")
            ->where("description_code","MS02")
            ->whereBetween("created_at",[$start,$end])
            ->get();

            $cashout_fee_deposit = Extract::where("type_transaction_extract","cash-out")
            ->whereIn("description_code",["CM03"])
            ->whereBetween("created_at",[$start,$end])
            ->sum("final_amount");

            $cashout_fee_withdraw = Extract::where("type_transaction_extract","cash-out")
            ->whereIn("description_code",["CM04"])
            ->whereBetween("created_at",[$start,$end])
            ->sum("final_amount");

            $amount_cashout = $cashout->sum("final_amount");
            $quantity_cashout = $cashout->count();

            $av_today = Extract::where("disponibilization_date","<=",$start)
            ->sum("final_amount");

            $tobe_released = Extract::where("disponibilization_date",">",$end)
            ->sum("final_amount");

            $balance = Extract::sum("final_amount");

            $get_registers = Extract::whereBetween("created_at",[$start,$end])->orderBy("created_at","DESC")->get();
            $total_registers = $get_registers->count();
            $all_registers = array();

            foreach($get_registers as $register){

                $all_registers[] = [
                    "id" => $register->id,
                    "transaction_id" => $register->transaction_id,
                    "order_id" => $register->order_id,
                    "client_id" => $register->client->name,
                    "user_id" => $register->user_id,
                    "type_transaction_extract" => $register->type_transaction_extract,
                    "description_code" => $register->description_code,
                    "description_text" => $register->description_text,
                    "cash_flow" => $register->cash_flow,
                    "final_amount" => $register->final_amount,
                    "quote" => $register->quote,
                    "quote_markup" => $register->quote_markup,
                    "receita" => $register->receita,
                    "disponibilization_date" => date("d/m/Y H:i:s",strtotime($register->disponibilization_date)),
                    "created_at" => date("d/m/Y H:i:s",strtotime($register->created_at)),
                    "updated_at" => date("d/m/Y H:i:s",strtotime($register->updated_at)),
                    "bank_name" => $register->bank->name
                ];

            }

            $clients = Clients::orderBy("name","ASC")->get();

        }elseif(auth()->user()->level == "merchant"){

            $cashin = Extract::where("type_transaction_extract","cash-in")
            ->where("client_id",auth()->user()->client_id)
            ->whereBetween("created_at",[$start,$end])
            ->get();

            $amount_cashin = $cashin->sum("final_amount");
            $quantity_cashin = $cashin->count();

            $cashout = Extract::where("type_transaction_extract","cash-out")
            ->where("client_id",auth()->user()->client_id)
            ->where("description_code","MS02")
            ->whereBetween("created_at",[$start,$end])
            ->get();

            $cashout_fee_deposit = Extract::where("type_transaction_extract","cash-out")
            ->where("client_id",auth()->user()->client_id)
            ->whereIn("description_code",["CM03"])
            ->whereBetween("created_at",[$start,$end])
            ->sum("final_amount");

            $cashout_fee_withdraw = Extract::where("type_transaction_extract","cash-out")
            ->where("client_id",auth()->user()->client_id)
            ->whereIn("description_code",["CM04"])
            ->whereBetween("created_at",[$start,$end])
            ->sum("final_amount");

            $amount_cashout = $cashout->sum("final_amount");
            $quantity_cashout = $cashout->count();

            $av_today = Extract::where("disponibilization_date","<=",$start)
            ->where("client_id",auth()->user()->client_id)
            ->sum("final_amount");

            $tobe_released = Extract::where("disponibilization_date",">",$end)
            ->where("client_id",auth()->user()->client_id)
            ->sum("final_amount");

            $balance = Extract::where("client_id",auth()->user()->client_id)->sum("final_amount");

            $get_registers = Extract::where("client_id",auth()->user()->client_id)->whereBetween("created_at",[$start,$end])->orderBy("created_at","DESC")->get();
            $total_registers = $get_registers->count();
            $all_registers = [];

            foreach($get_registers as $register){

                $all_registers[] = [
                    "id" => $register->id,
                    "transaction_id" => $register->transaction_id,
                    "order_id" => $register->order_id,
                    "client_id" => $register->client->name,
                    "user_id" => $register->user_id,
                    "type_transaction_extract" => $register->type_transaction_extract,
                    "description_code" => $register->description_code,
                    "description_text" => $register->description_text,
                    "cash_flow" => $register->cash_flow,
                    "final_amount" => $register->final_amount,
                    "quote" => $register->quote,
                    "quote_markup" => $register->quote_markup,
                    "receita" => $register->receita,
                    "disponibilization_date" => date("d/m/Y H:i:s",strtotime($register->disponibilization_date)),
                    "created_at" => date("d/m/Y H:i:s",strtotime($register->created_at)),
                    "updated_at" => date("d/m/Y H:i:s",strtotime($register->updated_at)),
                    "bank_name" => $register->bank->name
                ];

            }

            $clients = Clients::where("id",auth()->user()->client_id)->get();

        }elseif(auth()->user()->level == "crypto"){

            $cashin = Extract::where("type_transaction_extract","cash-in")
            ->where("client_id",auth()->user()->client_id)
            ->whereBetween("created_at",[$start,$end])
            ->get();

            $amount_cashin = $cashin->sum("final_amount");
            $quantity_cashin = $cashin->count();

            $cashout = Extract::where("type_transaction_extract","cash-out")
            ->where("client_id",auth()->user()->client_id)
            ->where("description_code","MS02")
            ->whereBetween("created_at",[$start,$end])
            ->get();

            $cashout_fee_deposit = Extract::where("type_transaction_extract","cash-out")
            ->where("client_id",auth()->user()->client_id)
            ->whereIn("description_code",["CM03"])
            ->whereBetween("created_at",[$start,$end])
            ->sum("final_amount");

            $cashout_fee_withdraw = Extract::where("type_transaction_extract","cash-out")
            ->where("client_id",auth()->user()->client_id)
            ->whereIn("description_code",["CM04"])
            ->whereBetween("created_at",[$start,$end])
            ->sum("final_amount");

            $amount_cashout = $cashout->sum("final_amount");
            $quantity_cashout = $cashout->count();

            $av_today = Extract::where("disponibilization_date","<=",$start)
            ->where("client_id",auth()->user()->client_id)
            ->sum("final_amount");

            $tobe_released = Extract::where("disponibilization_date",">",$end)
            ->where("client_id",auth()->user()->client_id)
            ->sum("final_amount");

            $balance = Extract::where("client_id",auth()->user()->client_id)->sum("final_amount");

            $get_registers = Extract::where("client_id",auth()->user()->client_id)->whereBetween("created_at",[$start,$end])->orderBy("created_at","DESC")->get();
            $total_registers = $get_registers->count();
            $all_registers = [];

            foreach($get_registers as $register){

                $all_registers[] = [
                    "id" => $register->id,
                    "transaction_id" => $register->transaction_id,
                    "order_id" => $register->order_id,
                    "client_id" => $register->client->name,
                    "user_id" => $register->user_id,
                    "type_transaction_extract" => $register->type_transaction_extract,
                    "description_code" => $register->description_code,
                    "description_text" => $register->description_text,
                    "cash_flow" => $register->cash_flow,
                    "final_amount" => $register->final_amount,
                    "quote" => $register->quote,
                    "quote_markup" => $register->quote_markup,
                    "receita" => $register->receita,
                    "disponibilization_date" => date("d/m/Y H:i:s",strtotime($register->disponibilization_date)),
                    "created_at" => date("d/m/Y H:i:s",strtotime($register->created_at)),
                    "updated_at" => date("d/m/Y H:i:s",strtotime($register->updated_at)),
                    "bank_name" => $register->bank->name
                ];

            }

            $clients = Clients::where("id",auth()->user()->client_id)->get();

        }

        $data = [
            "amount_cashin" => floatval($amount_cashin),
            "quantity_cashin" => floatval($quantity_cashin),
            "amount_cashout" => floatval($amount_cashout),
            "quantity_cashout" => floatval($quantity_cashout),
            "cashout_fee_deposit" => floatval($cashout_fee_deposit),
            "cashout_fee_withdraw" => floatval($cashout_fee_withdraw),
            "balance" => floatval($balance),
            "av_today" => floatval($av_today),
            "tobe_released" => floatval($tobe_released),
            "all_registers" => $all_registers,
            "total_registers" => $total_registers,
            "clients" => $clients,
            "banks" => Banks::orderBy("name","ASC")->get(),
        ];

        return response()->json($data);

    }

    public function search(Request $request){

        $permition = auth()->user()->especificPermition();

        if(auth()->user()->level == 'merchant'){
            if($permition['permition'] == "no"){
                return redirect("/transactions");
            }
        }

        $FunctionsController = new FunctionsController();

        $start = $FunctionsController->strtodate($request->date_start)." 00:00:00";
        $end = $FunctionsController->strtodate($request->date_end)." 23:59:59";

        if($start == "-- 00:00:00" ||  $end == "-- 23:59:59"){
            $start = date("Y-m-d")." 00:00:00";
            $end = date("Y-m-d")." 23:59:59";
        }

        if(isset($request->bank_id)){
            $bank_id = $request->bank_id;
        }else{
            $bank_id = "";
        }

        $today = date("Y-m-d 00:00:00");
        $client_id = $request->client_id;

        $cls = Clients::orderBy("name","ASC")->get();
        $clients = [];

        if($client_id == "all"){
            foreach($cls as $cl){
                array_push($clients,$cl->id);
            }
        }else{
            $clients = [$client_id];
        }

        if($bank_id != ""){
            if($bank_id == "all"){

                $cashin = Extract::whereIn("client_id",$clients)
                ->where("type_transaction_extract","cash-in")
                ->whereBetween("created_at",[$start,$end])
                ->get();

                $amount_cashin = $cashin->sum("final_amount");
                $quantity_cashin = $cashin->count();

                $cashout = Extract::whereIn("client_id",$clients)
                ->where("type_transaction_extract","cash-out")
                ->where("description_code","MS02")
                ->whereBetween("created_at",[$start,$end])
                ->get();

                $cashout_fee_deposit = Extract::whereIn("client_id",$clients)
                ->where("type_transaction_extract","cash-out")
                ->whereIn("description_code",["CM03"])
                ->whereBetween("created_at",[$start,$end])
                ->sum("final_amount");

                $cashout_fee_withdraw = Extract::whereIn("client_id",$clients)
                ->where("type_transaction_extract","cash-out")
                ->whereIn("description_code",["CM04"])
                ->whereBetween("created_at",[$start,$end])
                ->sum("final_amount");

                $amount_cashout = $cashout->sum("final_amount");
                $quantity_cashout = $cashout->count();

                $av_today = Extract::whereIn("client_id",$clients)
                ->where("disponibilization_date","<=",$today)
                ->sum("final_amount");

                $tobe_released = Extract::whereIn("client_id",$clients)
                ->where("disponibilization_date",">",$today)
                ->sum("final_amount");

                $balance = Extract::whereIn("client_id",$clients)->sum("final_amount");

                $get_registers = Extract::whereIn("client_id",$clients)->whereBetween("created_at",[$start,$end])->orderBy("created_at","DESC")->get();
                $total_registers = $get_registers->count();
                $all_registers = [];

                foreach($get_registers as $register){

                    $all_registers[] = [
                        "id" => $register->id,
                        "transaction_id" => $register->transaction_id,
                        "order_id" => $register->order_id,
                        "client_id" => $register->client->name,
                        "user_id" => $register->user_id,
                        "type_transaction_extract" => $register->type_transaction_extract,
                        "description_code" => $register->description_code,
                        "description_text" => $register->description_text,
                        "cash_flow" => $register->cash_flow,
                        "final_amount" => $register->final_amount,
                        "quote" => $register->quote,
                        "quote_markup" => $register->quote_markup,
                        "receita" => $register->receita,
                        "disponibilization_date" => date("d/m/Y H:i:s",strtotime($register->disponibilization_date)),
                        "created_at" => date("d/m/Y H:i:s",strtotime($register->created_at)),
                        "updated_at" => date("d/m/Y H:i:s",strtotime($register->updated_at)),
                        "bank_name" => $register->bank->name
                    ];

                }

                if(auth()->user()->level == "master"){
                    $clients = Clients::orderBy("name","ASC")->get();
                }elseif(auth()->user()->level == "merchant"){
                    $clients = Clients::where("id",auth()->user()->client->id)->get();
                }

            }else{

                $cashin = Extract::whereIn("client_id",$clients)
                ->where("type_transaction_extract","cash-in")
                ->where("bank_id",$bank_id)
                ->whereBetween("created_at",[$start,$end])
                ->get();

                $amount_cashin = $cashin->sum("final_amount");
                $quantity_cashin = $cashin->count();

                $cashout = Extract::whereIn("client_id",$clients)
                ->where("type_transaction_extract","cash-out")
                ->where("description_code","MS02")
                ->where("bank_id",$bank_id)
                ->whereBetween("created_at",[$start,$end])
                ->get();

                $cashout_fee_deposit = Extract::where("type_transaction_extract","cash-out")
                ->where("bank_id",$bank_id)
                ->whereIn("description_code",["CM03"])
                ->whereBetween("created_at",[$start,$end])
                ->sum("final_amount");

                $cashout_fee_withdraw = Extract::where("type_transaction_extract","cash-out")
                ->where("bank_id",$bank_id)
                ->whereIn("description_code",["CM04"])
                ->whereBetween("created_at",[$start,$end])
                ->sum("final_amount");

                $amount_cashout = $cashout->sum("final_amount");
                $quantity_cashout = $cashout->count();

                $av_today = Extract::whereIn("client_id",$clients)
                ->where("disponibilization_date","<=",$today)
                ->where("bank_id",$bank_id)
                ->sum("final_amount");

                $tobe_released = Extract::whereIn("client_id",$clients)
                ->where("disponibilization_date",">",$today)
                ->where("bank_id",$bank_id)
                ->sum("final_amount");

                $balance = Extract::whereIn("client_id",$clients)->where("bank_id",$bank_id)->sum("final_amount");

                $get_registers = Extract::whereIn("client_id",$clients)->whereBetween("created_at",[$start,$end])->where("bank_id",$bank_id)->orderBy("created_at","DESC")->get();
                $total_registers = $get_registers->count();
                $all_registers = [];

                foreach($get_registers as $register){

                    $all_registers[] = [
                        "id" => $register->id,
                        "transaction_id" => $register->transaction_id,
                        "order_id" => $register->order_id,
                        "client_id" => $register->client->name,
                        "user_id" => $register->user_id,
                        "type_transaction_extract" => $register->type_transaction_extract,
                        "description_code" => $register->description_code,
                        "description_text" => $register->description_text,
                        "cash_flow" => $register->cash_flow,
                        "final_amount" => $register->final_amount,
                        "quote" => $register->quote,
                        "quote_markup" => $register->quote_markup,
                        "receita" => $register->receita,
                        "disponibilization_date" => date("d/m/Y H:i:s",strtotime($register->disponibilization_date)),
                        "created_at" => date("d/m/Y H:i:s",strtotime($register->created_at)),
                        "updated_at" => date("d/m/Y H:i:s",strtotime($register->updated_at)),
                        "bank_name" => $register->bank->name
                    ];

                }

                if(auth()->user()->level == "master"){
                    $clients = Clients::orderBy("name","ASC")->get();
                }elseif(auth()->user()->level == "merchant"){
                    $clients = Clients::where("id",auth()->user()->client->id)->get();
                }

            }
        }else{
            $cashin = Extract::whereIn("client_id",$clients)
            ->where("type_transaction_extract","cash-in")
            ->whereBetween("created_at",[$start,$end])
            ->get();

            $amount_cashin = $cashin->sum("final_amount");
            $quantity_cashin = $cashin->count();

            $cashout = Extract::whereIn("client_id",$clients)
            ->where("type_transaction_extract","cash-out")
            ->where("description_code","MS02")
            ->whereBetween("created_at",[$start,$end])
            ->get();

            $cashout_fee_deposit = Extract::whereIn("client_id",$clients)
            ->where("type_transaction_extract","cash-out")
            ->whereIn("description_code",["CM03"])
            ->whereBetween("created_at",[$start,$end])
            ->sum("final_amount");

            $cashout_fee_withdraw = Extract::whereIn("client_id",$clients)
            ->where("type_transaction_extract","cash-out")
            ->whereIn("description_code",["CM04"])
            ->whereBetween("created_at",[$start,$end])
            ->sum("final_amount");

            $amount_cashout = $cashout->sum("final_amount");
            $quantity_cashout = $cashout->count();

            $av_today = Extract::whereIn("client_id",$clients)
            ->where("disponibilization_date","<",$today)
            ->sum("final_amount");

            $tobe_released = Extract::whereIn("client_id",$clients)
            ->where("disponibilization_date",">=",$today)
            ->sum("final_amount");

            $balance = Extract::whereIn("client_id",$clients)->sum("final_amount");

            $get_registers = Extract::whereIn("client_id",$clients)->whereBetween("created_at",[$start,$end])->orderBy("created_at","DESC")->get();
            $total_registers = $get_registers->count();
            $all_registers = [];

            foreach($get_registers as $register){

                $all_registers[] = [
                    "id" => $register->id,
                    "transaction_id" => $register->transaction_id,
                    "order_id" => $register->order_id,
                    "client_id" => $register->client->name,
                    "user_id" => $register->user_id,
                    "type_transaction_extract" => $register->type_transaction_extract,
                    "description_code" => $register->description_code,
                    "description_text" => $register->description_text,
                    "cash_flow" => $register->cash_flow,
                    "final_amount" => $register->final_amount,
                    "quote" => $register->quote,
                    "quote_markup" => $register->quote_markup,
                    "receita" => $register->receita,
                    "disponibilization_date" => date("d/m/Y H:i:s",strtotime($register->disponibilization_date)),
                    "created_at" => date("d/m/Y H:i:s",strtotime($register->created_at)),
                    "updated_at" => date("d/m/Y H:i:s",strtotime($register->updated_at)),
                    "bank_name" => $register->bank->name
                ];

            }

            if(auth()->user()->level == "master"){
                $clients = Clients::orderBy("name","ASC")->get();
            }elseif(auth()->user()->level == "merchant"){
                $clients = Clients::where("id",auth()->user()->client->id)->get();
            }
        }

        $data = [
            "amount_cashin" => floatval($amount_cashin),
            "quantity_cashin" => floatval($quantity_cashin),
            "amount_cashout" => floatval($amount_cashout),
            "quantity_cashout" => floatval($quantity_cashout),
            "cashout_fee_deposit" => floatval($cashout_fee_deposit),
            "cashout_fee_withdraw" => floatval($cashout_fee_withdraw),
            "balance" => floatval($balance),
            "av_today" => floatval($av_today),
            "tobe_released" => floatval($tobe_released),
            "all_registers" => $all_registers,
            "total_registers" => $total_registers,
            "clients" => $clients,
            "banks" => Banks::orderBy("name","ASC")->get(),
        ];

        return response()->json($data);

    }
}
