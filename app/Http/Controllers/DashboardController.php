<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use \App\Models\{Extract,Clients,Banks};
use App\Http\Controllers\FunctionsAPIController;

class DashboardController extends Controller
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
            ->whereIn("description_code",["MS01","MS02","MS03"])
            ->whereBetween("created_at",[$start,$end])
            ->get();

            $cashout_fee_deposit = Extract::where("type_transaction_extract","cash-out")
            ->whereIn("description_code",["CM03"])
            ->whereBetween("created_at",[$start,$end])
            ->sum("final_amount");

            $cashout_fee_withdraw = Extract::where("type_transaction_extract","cash-out")
            ->whereIn("description_code",["CM04","CM05"])
            ->whereBetween("created_at",[$start,$end])
            ->sum("final_amount");

            $amount_cashout = $cashout->sum("final_amount");
            $quantity_cashout = $cashout->count();

            $av_today = Extract::where("disponibilization_date","<=",$start)
            ->sum("final_amount");

            $tobe_released = Extract::where("disponibilization_date",">",$end)
            ->sum("final_amount");

            $balance = Extract::sum("final_amount");

            $all_registers = Extract::whereBetween("created_at",[$start,$end])->orderBy("created_at","DESC")->get();
            $total_registers = $all_registers->count();

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
            ->whereIn("description_code",["MS01","MS02","MS03"])
            ->whereBetween("created_at",[$start,$end])
            ->get();

            $cashout_fee_deposit = Extract::where("type_transaction_extract","cash-out")
            ->where("client_id",auth()->user()->client_id)
            ->whereIn("description_code",["CM03"])
            ->whereBetween("created_at",[$start,$end])
            ->sum("final_amount");

            $cashout_fee_withdraw = Extract::where("type_transaction_extract","cash-out")
            ->where("client_id",auth()->user()->client_id)
            ->whereIn("description_code",["CM04","CM05"])
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

            $all_registers = Extract::where("client_id",auth()->user()->client_id)->whereBetween("created_at",[$start,$end])->orderBy("created_at","DESC")->get();
            $total_registers = $all_registers->count();

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
            ->whereIn("description_code",["MS01","MS02","MS03"])
            ->whereBetween("created_at",[$start,$end])
            ->get();

            $cashout_fee_deposit = Extract::where("type_transaction_extract","cash-out")
            ->where("client_id",auth()->user()->client_id)
            ->whereIn("description_code",["CM03"])
            ->whereBetween("created_at",[$start,$end])
            ->sum("final_amount");

            $cashout_fee_withdraw = Extract::where("type_transaction_extract","cash-out")
            ->where("client_id",auth()->user()->client_id)
            ->whereIn("description_code",["CM04","CM05"])
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

            $all_registers = Extract::where("client_id",auth()->user()->client_id)->whereBetween("created_at",[$start,$end])->orderBy("created_at","DESC")->get();
            $total_registers = $all_registers->count();

            $clients = Clients::where("id",auth()->user()->client_id)->get();
        }

        $data = [
            "amount_cashin" => $amount_cashin,
            "quantity_cashin" => $quantity_cashin,
            "amount_cashout" => $amount_cashout,
            "quantity_cashout" => $quantity_cashout,
            "cashout_fee_deposit" => $cashout_fee_deposit,
            "cashout_fee_withdraw" => $cashout_fee_withdraw,
            "balance" => $balance,
            "av_today" => $av_today,
            "tobe_released" => $tobe_released,
            "all_registers" => $all_registers,
            "total_registers" => $total_registers,
            "clients" => $clients,
            "banks" => Banks::orderBy("name","ASC")->get(),
            "request" => null,
        ];

        return view('dashboard.index',compact('data'));

    }

    public function search(Request $request){

        $permition = auth()->user()->especificPermition();

        if(auth()->user()->level == 'merchant'){
            if($permition['permition'] == "no"){
                return redirect("/transactions");
            }
        }

        $FunctionsAPIController = new FunctionsAPIController();

        $start = $FunctionsAPIController->strtodate($request->date_start)." 00:00:00";
        $end = $FunctionsAPIController->strtodate($request->date_end)." 23:59:59";

        if($start == "-- 00:00:00" ||  $end == "-- 23:59:59"){
            $start = date("Y-m-d")." 00:00:00";
            $end = date("Y-m-d")." 23:59:59";
        }

        if(isset($request->bank_id)){
            $bank_id = $request->bank_id;
        }else{
            $bank_id = "";
        }

        if(isset($request->search)){
            $search = $request->search;
        }else{
            $search = "";
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
                ->where(function($query) use ($search,$FunctionsAPIController){
                    if($search != ''){
                        $query->join('transactions_detail', 'transactions_detail.user_document', '=', $FunctionsAPIController->clearCPF($search));
                    }
                })
                ->get();

                $amount_cashin = $cashin->sum("final_amount");
                $quantity_cashin = $cashin->count();

                $cashout = Extract::whereIn("client_id",$clients)
                ->where("type_transaction_extract","cash-out")
                ->whereIn("description_code",["MS01","MS02","MS03"])
                ->whereBetween("created_at",[$start,$end])
                ->where(function($query) use ($search,$FunctionsAPIController){
                    if($search != ''){
                        $query->join('transactions_detail', 'transactions_detail.user_document', '=', $FunctionsAPIController->clearCPF($search));
                    }
                })
                ->get();

                $cashout_fee_deposit = Extract::whereIn("client_id",$clients)
                ->where("type_transaction_extract","cash-out")
                ->whereIn("description_code",["CM03"])
                ->whereBetween("created_at",[$start,$end])
                ->where(function($query) use ($search,$FunctionsAPIController){
                    if($search != ''){
                        $query->join('transactions_detail', 'transactions_detail.user_document', '=', $FunctionsAPIController->clearCPF($search));
                    }
                })
                ->sum("final_amount");

                $cashout_fee_withdraw = Extract::whereIn("client_id",$clients)
                ->where("type_transaction_extract","cash-out")
                ->whereIn("description_code",["CM04","CM05"])
                ->whereBetween("created_at",[$start,$end])
                ->where(function($query) use ($search,$FunctionsAPIController){
                    if($search != ''){
                        $query->join('transactions_detail', 'transactions_detail.user_document', '=', $FunctionsAPIController->clearCPF($search));
                    }
                })
                ->sum("final_amount");

                $amount_cashout = $cashout->sum("final_amount");
                $quantity_cashout = $cashout->count();

                $av_today = Extract::whereIn("client_id",$clients)
                ->where("disponibilization_date","<=",$today)
                ->where(function($query) use ($search,$FunctionsAPIController){
                    if($search != ''){
                        $query->join('transactions_detail', 'transactions_detail.user_document', '=', $FunctionsAPIController->clearCPF($search));
                    }
                })
                ->sum("final_amount");

                $tobe_released = Extract::whereIn("client_id",$clients)
                ->where("disponibilization_date",">",$today)
                ->where(function($query) use ($search,$FunctionsAPIController){
                    if($search != ''){
                        $query->join('transactions_detail', 'transactions_detail.user_document', '=', $FunctionsAPIController->clearCPF($search));
                    }
                })
                ->sum("final_amount");

                $balance = Extract::whereIn("client_id",$clients)->sum("final_amount");

                // $all_registers = Extract::whereIn("client_id",$clients)->whereBetween("created_at",[$start,$end])->orderBy("created_at","DESC")->get();
                // $total_registers = $all_registers->count();

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
                ->where(function($query) use ($search,$FunctionsAPIController){
                    if($search != ''){
                        $query->join('transactions_detail', 'transactions_detail.user_document', '=', $FunctionsAPIController->clearCPF($search));
                    }
                })
                ->get();

                $amount_cashin = $cashin->sum("final_amount");
                $quantity_cashin = $cashin->count();

                $cashout = Extract::whereIn("client_id",$clients)
                ->where("type_transaction_extract","cash-out")
                ->whereIn("description_code",["MS01","MS02","MS03"])
                ->where("bank_id",$bank_id)
                ->whereBetween("created_at",[$start,$end])
                ->where(function($query) use ($search,$FunctionsAPIController){
                    if($search != ''){
                        $query->join('transactions_detail', 'transactions_detail.user_document', '=', $FunctionsAPIController->clearCPF($search));
                    }
                })
                ->get();

                $cashout_fee_deposit = Extract::where("type_transaction_extract","cash-out")
                ->where("bank_id",$bank_id)
                ->whereIn("description_code",["CM03"])
                ->whereBetween("created_at",[$start,$end])
                ->where(function($query) use ($search,$FunctionsAPIController){
                    if($search != ''){
                        $query->join('transactions_detail', 'transactions_detail.user_document', '=', $FunctionsAPIController->clearCPF($search));
                    }
                })
                ->sum("final_amount");

                $cashout_fee_withdraw = Extract::where("type_transaction_extract","cash-out")
                ->where("bank_id",$bank_id)
                ->whereIn("description_code",["CM04","CM05"])
                ->whereBetween("created_at",[$start,$end])
                ->where(function($query) use ($search,$FunctionsAPIController){
                    if($search != ''){
                        $query->join('transactions_detail', 'transactions_detail.user_document', '=', $FunctionsAPIController->clearCPF($search));
                    }
                })
                ->sum("final_amount");

                $amount_cashout = $cashout->sum("final_amount");
                $quantity_cashout = $cashout->count();

                $av_today = Extract::whereIn("client_id",$clients)
                ->where("disponibilization_date","<=",$today)
                ->where("bank_id",$bank_id)
                ->where(function($query) use ($search,$FunctionsAPIController){
                    if($search != ''){
                        $query->join('transactions_detail', 'transactions_detail.user_document', '=', $FunctionsAPIController->clearCPF($search));
                    }
                })
                ->sum("final_amount");

                $tobe_released = Extract::whereIn("client_id",$clients)
                ->where("disponibilization_date",">",$today)
                ->where("bank_id",$bank_id)
                ->where(function($query) use ($search,$FunctionsAPIController){
                    if($search != ''){
                        $query->join('transactions_detail', 'transactions_detail.user_document', '=', $FunctionsAPIController->clearCPF($search));
                    }
                })
                ->sum("final_amount");

                $balance = Extract::whereIn("client_id",$clients)->where("bank_id",$bank_id)->sum("final_amount");

                // $all_registers = Extract::whereIn("client_id",$clients)->whereBetween("created_at",[$start,$end])->where("bank_id",$bank_id)->orderBy("created_at","DESC")->get();
                // $total_registers = $all_registers->count();

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
            ->where(function($query) use ($search,$FunctionsAPIController){
                if($search != ''){
                    $query->join('transactions_detail', 'transactions_detail.user_document', '=', $FunctionsAPIController->clearCPF($search));
                }
            })
            ->get();

            $amount_cashin = $cashin->sum("final_amount");
            $quantity_cashin = $cashin->count();

            $cashout = Extract::whereIn("client_id",$clients)
            ->where("type_transaction_extract","cash-out")
            ->whereIn("description_code",["MS01","MS02","MS03"])
            ->whereBetween("created_at",[$start,$end])
            ->where(function($query) use ($search,$FunctionsAPIController){
                if($search != ''){
                    $query->join('transactions_detail', 'transactions_detail.user_document', '=', $FunctionsAPIController->clearCPF($search));
                }
            })
            ->get();

            $cashout_fee_deposit = Extract::whereIn("client_id",$clients)
            ->where("type_transaction_extract","cash-out")
            ->whereIn("description_code",["CM03"])
            ->whereBetween("created_at",[$start,$end])
            ->where(function($query) use ($search,$FunctionsAPIController){
                if($search != ''){
                    $query->join('transactions_detail', 'transactions_detail.user_document', '=', $FunctionsAPIController->clearCPF($search));
                }
            })
            ->sum("final_amount");

            $cashout_fee_withdraw = Extract::whereIn("client_id",$clients)
            ->where("type_transaction_extract","cash-out")
            ->whereIn("description_code",["CM04","CM05"])
            ->whereBetween("created_at",[$start,$end])
            ->where(function($query) use ($search,$FunctionsAPIController){
                if($search != ''){
                    $query->join('transactions_detail', 'transactions_detail.user_document', '=', $FunctionsAPIController->clearCPF($search));
                }
            })
            ->sum("final_amount");

            $amount_cashout = $cashout->sum("final_amount");
            $quantity_cashout = $cashout->count();

            $av_today = Extract::whereIn("client_id",$clients)
            ->where("disponibilization_date","<",$today)
            ->where(function($query) use ($search,$FunctionsAPIController){
                if($search != ''){
                    $query->join('transactions_detail', 'transactions_detail.user_document', '=', $FunctionsAPIController->clearCPF($search));
                }
            })
            ->sum("final_amount");

            $tobe_released = Extract::whereIn("client_id",$clients)
            ->where("disponibilization_date",">=",$today)
            ->where(function($query) use ($search,$FunctionsAPIController){
                if($search != ''){
                    $query->join('transactions_detail', 'transactions_detail.user_document', '=', $FunctionsAPIController->clearCPF($search));
                }
            })
            ->sum("final_amount");

            $balance = Extract::whereIn("client_id",$clients)->sum("final_amount");

            // $all_registers = Extract::whereIn("client_id",$clients)->whereBetween("created_at",[$start,$end])->orderBy("created_at","DESC")->get();
            // $total_registers = $all_registers->count();

            if(auth()->user()->level == "master"){
                $clients = Clients::orderBy("name","ASC")->get();
            }elseif(auth()->user()->level == "merchant"){
                $clients = Clients::where("id",auth()->user()->client->id)->get();
            }
        }

        $data = [
            "amount_cashin" => $amount_cashin,
            "quantity_cashin" => $quantity_cashin,
            "amount_cashout" => $amount_cashout,
            "quantity_cashout" => $quantity_cashout,
            "cashout_fee_deposit" => $cashout_fee_deposit,
            "cashout_fee_withdraw" => $cashout_fee_withdraw,
            "balance" => $balance,
            "av_today" => $av_today,
            "tobe_released" => $tobe_released,
            // "all_registers" => $all_registers,
            // "total_registers" => $total_registers,
            "clients" => $clients,
            "banks" => Banks::orderBy("name","ASC")->get(),
            "request" => [
                "date_start" => $request->date_start,
                "date_end" => $request->date_end,
                "client_id" => $request->client_id,
                "bank_id" => $request->bank_id,
                "search" => $request->search
            ]
        ];

        return view('dashboard.index',compact('data'));

    }

    public function getDash(Request $request){

        $FunctionsController = new FunctionsController();

        $draw = $request->draw;
        $offset = $request->start;
        $limit = $request->length;

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

        if(isset($request->search)){
            $search = $request->search;
        }else{
            $search = "";
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

        // $offset = ($draw*$limit) - $limit;

        if($bank_id != ""){
            if($bank_id == "all"){

                $all_registers = Extract::with('transaction')
                ->whereHas('transaction', function ($query) use ($search,$FunctionsController) {
                    if($search != ''){
                        $query->where('user_document',$FunctionsController->clearCPF($search))
                        ->orWhere('user_name','ilike','%'.$search.'%');
                    }
                })
                ->whereIn("client_id",$clients)->whereBetween("created_at",[$start,$end])
                ->orderBy("created_at","DESC")->offset($offset)->limit($limit)->get();

                $total_registers = Extract::with('transaction')
                ->whereHas('transaction', function ($query) use ($search,$FunctionsController) {
                    if($search != ''){
                        $query->where('user_document',$FunctionsController->clearCPF($search))
                        ->orWhere('user_name','ilike','%'.$search.'%');
                    }
                })
                ->whereIn("client_id",$clients)->whereBetween("created_at",[$start,$end])
                ->orderBy("created_at","DESC")->count();

                // return response()->json($all_registers_sql);

            }else{

                $all_registers = Extract::with('transaction')
                ->whereHas('transaction', function ($query) use ($search,$FunctionsController) {
                    if($search != ''){
                        $query->where('user_document',$FunctionsController->clearCPF($search))
                        ->orWhere('user_name','ilike','%'.$search.'%');
                    }
                })
                ->whereIn("client_id",$clients)->whereBetween("created_at",[$start,$end])->where("bank_id",$bank_id)
                ->orderBy("created_at","DESC")->offset($offset)->limit($limit)->get();

                $total_registers = Extract::with('transaction')
                ->whereHas('transaction', function ($query) use ($search,$FunctionsController) {
                    if($search != ''){
                        $query->where('user_document',$FunctionsController->clearCPF($search))
                        ->orWhere('user_name','ilike','%'.$search.'%');
                    }
                })
                ->whereIn("client_id",$clients)->whereBetween("created_at",[$start,$end])->where("bank_id",$bank_id)
                ->orderBy("created_at","DESC")->count();

            }
        }else{

            $all_registers = Extract::with('transaction')
            ->whereHas('transaction', function ($query) use ($search,$FunctionsController) {
                if($search != ''){
                    $query->where('user_document',$FunctionsController->clearCPF($search))
                    ->orWhere('user_name','ilike','%'.$search.'%');
                }
            })
            ->whereIn("client_id",$clients)->whereBetween("created_at",[$start,$end])
            ->orderBy("created_at","DESC")->offset($offset)->limit($limit)->get();

            $total_registers = Extract::with('transaction')
            ->whereHas('transaction', function ($query) use ($search,$FunctionsController) {
                if($search != ''){
                    $query->where('user_document',$FunctionsController->clearCPF($search))
                    ->orWhere('user_name','ilike','%'.$search.'%');
                }
            })
            ->whereIn("client_id",$clients)->whereBetween("created_at",[$start,$end])
            ->orderBy("created_at","DESC")->count();

        }

        $data = [];

        if(auth()->user()->level == "master"){
            foreach($all_registers as $register){
                $data[] = array(
                    "date" => date("d/m/Y H:i:s",strtotime($register->created_at)),
                    "fast_id" => $register->transaction_id,
                    "client" => $register->client->name,
                    "order_id" => $register->order_id." / ".strtoupper($register->transaction->user_name),
                    "user_id" => $register->user_id,
                    "bank" => $register->bank->name,
                    "description" => $register->description_text,
                    "amount" => number_format($register->final_amount,"2",",",".")
                );
            }
        }else{
            foreach($all_registers as $register){
                $data[] = array(
                    "date" => date("d/m/Y H:i:s",strtotime($register->created_at)),
                    "fast_id" => $register->transaction->id,
                    "client" => $register->client->name,
                    "order_id" => $register->order_id." / ".strtoupper($register->transaction->user_name),
                    "user_id" => $register->user_id,
                    "description" => $register->description_text,
                    "amount" => number_format($register->final_amount,"2",",",".")
                );
            }
        }

        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $total_registers,
            "iTotalDisplayRecords" => $total_registers,
            "aaData" => $data
        );

        return response()->json($response);

    }

}
