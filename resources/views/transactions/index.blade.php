@extends('layouts.appdash')

@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" />

<style type="text/css">
.iwxmpB .min-height-box > * {
    flex: 1 1 0%;
}
.hgjCcM .list {
    padding: 0px 0px 0px 30px;
    margin: 0px;
    list-style: none;
    color: var(--gray-700);
}
.zJaJi:first-child {
    position: relative;
    padding-top: 10px;
}
.zJaJi:first-child::before {
    content: "";
    display: block;
    width: 2px;
    background-color: white;
    height: 20px;
    left: 0px;
    margin-left: 0px;
    position: absolute;
    z-index: 1;
}
.zJaJi .item {
    padding-top: 0px;
    border-left: 2px solid var(--gray-400);
    padding-left: 12px;
    display: flex;
    align-items: flex-start;
    -webkit-box-pack: start;
    justify-content: flex-start;
    width: 100%;
}
.jluaTU {
    color: var(--gray-600);
    font-size: 10px;
    border-left: 2px solid rgb(245, 246, 250);
    padding-left: 12px;
    padding-bottom: 8px;
    font-weight: 300;
}
.zJaJi .item {
    padding-top: 0px;
    border-left: 2px solid var(--gray-400);
    padding-left: 12px;
    display: flex;
    align-items: flex-start;
    -webkit-box-pack: start;
    justify-content: flex-start;
    width: 100%;
}
.cvhvqL {
    width: 100%;
    border: none;
    overflow: hidden;
    border-radius: 4px;
    margin-bottom: 8px;
    text-align: left;
    background: linear-gradient(270deg, rgb(245, 246, 250) 50%, rgba(245, 246, 250, 0) 100%);
    padding: 4px 32px 4px 0px;
}
.align-items-center {
    -ms-flex-align: center!important;
    align-items: center!important;
}
.justify-content-between {
    -ms-flex-pack: justify!important;
    justify-content: space-between!important;
}
.fepefJ.debito {
    fill: var(--red-500);
    color: var(--red-500);
}
.iXGMzg:not(.hasClass) .flex .currency {
    color: var(--gray);
    font-size: 14px;
    margin-top: 2px;
    font-weight: 300;
}
.iXGMzg:not(.hasClass) .flex .currency strong {
    font-weight: 500;
    margin-left: 2px;
}

.input-group-append {
  cursor: pointer;
}

/* .datepicker-days{
    padding: 15px !important;
    text-align: center;
}
.day{
    padding: 5px !important;
    cursor:pointer;
}
.day:hover{
    background-color: #17A2B8;
    color:#FFF;
    border-radius: 3px;
} */
h3{
    font-size: 1.3rem !important;
}
.card-body>.table>thead>tr>td, .card-body>.table>thead>tr>th {
    font-size: 12px;
}
.table-bordered td, .table-bordered th {
    font-size: 12px !important;
}
.buttonDash{
    font-size: 12px !important;
}
.wrapper2{
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1040;
    width: 100vw;
    height: 100vh;
    background-color: #000;
    opacity: 0.8;
}
.spin{
    width: 150px;
    height: 150px;
    color: #fff;
    margin-top: 30vh;
}

.files input {
    outline: 2px dashed #92b0b3;
    outline-offset: -10px;
    -webkit-transition: outline-offset .15s ease-in-out, background-color .15s linear;
    transition: outline-offset .15s ease-in-out, background-color .15s linear;
    padding: 70px;
    text-align: center !important;
    margin: 0;
    width: 100% !important;
}
.files input:focus{     outline: 2px dashed #92b0b3;  outline-offset: -10px;
    -webkit-transition: outline-offset .15s ease-in-out, background-color .15s linear;
    transition: outline-offset .15s ease-in-out, background-color .15s linear; border:1px solid #92b0b3;
 }
.files{ position:relative}
.files:after {  pointer-events: none;
    position: absolute;
    top: 60px;
    left: 0;
    width: 40px;
    right: 0;
    height: 56px;
    content: "";
    background-image: url(https://image.flaticon.com/icons/png/128/109/109612.png);
    display: block;
    margin: 0 auto;
    background-size: 100%;
    background-repeat: no-repeat;
}
.color input{ background-color:#f1f1f1;}
.files:before {
    position: absolute;
    bottom: 10px;
    left: 0;  pointer-events: none;
    width: 100%;
    right: 0;
    height: 57px;
    content: " or drag it here. ";
    display: block;
    margin: 0 auto;
    color: #2ea591;
    font-weight: 600;
    text-transform: capitalize;
    text-align: center;
}
.pager {
    padding-left: 0;
    margin: 20px 0;
    list-style: none;
    text-align: center;
}
.pagination {
    padding-left: 0;
    margin: 10px 0;
    border-radius: 2px;
}
.pager li {
}

.pagination>li>a, .pagination>li>span {
    position: relative;
    float: left;
    padding: 5px 10px;
    line-height: 1.54;
    text-decoration: none;
    color: #03a9f4;
    background-color: #fff;
    border: 1px solid #e0e0e0;
    margin-left: -1px;
}

.pager li>a, .pager li>span {
    padding: 5px 14px;
    background-color: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 2px;
}

.pager li>a, .pager li span {
    padding: 6px 15px;
}

.pagination>li:first-child>a, .pagination>li:first-child>span {
    margin-left: 0;
    border-bottom-left-radius: 2px;
    border-top-left-radius: 2px;
}

.pager li>a, .pager li>span {
    padding: 5px 14px;
    background-color: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 2px;
}

.pagination>.active>a, .pagination>.active>span, .pagination>.active>a:hover, .pagination>.active>span:hover, .pagination>.active>a:focus, .pagination>.active>span:focus {
    z-index: 2;
    color: #fff;
    background-color: #03a9f4;
    border-color: #03a9f4;
    cursor: default;
}

tr.watching{
    background-color: rgba(150,206,181,0.3) !important;
}
.table {
    font-size:9px;
}
.av_red{
    color: rgb(230 119 119) !important;
}

.paginate svg {
    width: 30px;
    height: 30px;
}

spam .shadow-sm {
    box-shadow: none !important;
}
</style>
@endsection

@php
    if(Auth::user()->haspermitions){
        $afpermitions = Auth::user()->haspermitions;
        $permitions = [];
        foreach($afpermitions as $row){
            array_push($permitions,$row->permition_id);
        }
    }else{
        $permitions = [];
    }
@endphp

@section('content')
<div id="load" class="wrapper2 text-center"  style="display:none">
    <div class="spinner-border spin" role="status">
        <span class="sr-only">Loading...</span>
    </div>
</div>
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-4">
            <h1 class="m-0">Transactions</h1>
          </div><!-- /.col -->
          <div class="col-sm-8">
            <form method="GET" action="{{switchUrl('/transactions')}}" enctype="multipart/form-data">
                {{ csrf_field() }}
                <div class="row">
                    <div class="col-md-3 col-sm-12">
                        <label>Client</label>
                        <select name="client_id" id="client" class='form-control'>
                            @if(auth()->user()->level == "master") <option value="all">All</option> @endif
                            @foreach($data['clients'] as $client)
                                <option value="{{ $client->id }}" @if(isset($data['request']['client_id'])) @if($data['request']['client_id'] == $client->id) selected @endif @endif >{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-12">
                        <label>Type</label>
                        <select name="type_transaction" id="type_transaction" class='form-control'>
                            <option value="all" @if(isset($data['request']['type_transaction'])) @if($data['request']['type_transaction'] == "all")selected @endif @endif>ALL</option>
                            <option value="deposit" @if(isset($data['request']['type_transaction'])) @if($data['request']['type_transaction'] == "deposit")selected @endif @endif>DEPOSIT</option>
                            <option value="withdraw" @if(isset($data['request']['type_transaction'])) @if($data['request']['type_transaction'] == "withdraw")selected @endif @endif>WITHDRAW</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-12">
                        <label>Start</label>
                        <div class="input-group date" id="datepicker_start">
                            <input type="text" class="form-control datepicker_start" id="date1" maxlength="11" name="date_start" autocomplete="off" @if(isset($data['request'])) value="{{ $data['request']['date_start'] }}" @endif />
                            <span class="input-group-append">
                                <span class="input-group-text bg-light d-block">
                                    <i class="fa fa-calendar"></i>
                                </span>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-12">
                        <label>End</label>
                        <div class="input-group date" id="datepicker_end">
                            <input type="text" class="form-control datepicker_end" id="date2" maxlength="11" name="date_end" autocomplete="off" @if(isset($data['request'])) value="{{ $data['request']['date_end'] }}" @endif/>
                            <span class="input-group-append">
                                <span class="input-group-text bg-light d-block">
                                    <i class="fa fa-calendar"></i>
                                </span>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-12">
                        <label>ID</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" autocomplete="off" @if(isset($data['request'])) value="{{ $data['request']['search'] }}" @endif/>
                        </div>
                    </div>
                    <div class="col-md-1 col-sm-12">
                        <button class="btn btn-success" type="submit" style="width:100%;margin-top:31px;"><i class="fa fa-search"></i></button>
                    </div>
                </div>
            </form>
        </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <div class="content">
        <div class="container-fluid">

          <div class="card">
            <div class="card-header">
              <h3 class="card-title">TRANSACTIONS</h3>
            </div>

            <div class="card-body p-0">
                <section class="section-table">
                    <table class='table table-striped table-bordered thead-light table-dash' id='search-table'>
                    <thead>
                      <tr>
                        @if(in_array(15,$permitions))
                            <th width="15%">DATE</th>
                        @endif
                        @if(in_array(16,$permitions))
                            <th class='text-center' width="5%">ORDER ID</th>
                        @endif
                        @if(in_array(18,$permitions))
                            <th class='text-center' width="">METHOD</th>
                        @endif
                        @if(Auth::user()->level == 'master')
                            @if(in_array(19,$permitions))
                                <th class='text-center' width="">BANK</th>
                            @endif
                        @endif
                        @if(in_array(20,$permitions))
                            <th class='text-center'>USER ID</th>
                        @endif
                        @if(in_array(21,$permitions))
                            <th width="">TYPE</th>
                        @endif
                        @if(in_array(22,$permitions))
                            <th width="" class='text-right'>AMOUNT</th>
                        @endif
                        @if(in_array(24,$permitions))
                            <th width="" class='text-right'>FEES</th>
                        @endif
                        @if(in_array(26,$permitions))
                            <th class='text-center' width="12%">STATUS</th>
                        @endif
                      </tr>
                    </thead>
                    <tbody>
                        @php
                        function doubletostr($var) {
                            $var = number_format($var, 2, ',', '.');
                            return $var;
                        }
                        $client = $data['client'];
                        $currency = "BRL";

                        $banks_account = $data['banks'];
                        $all_users_blocked = $data['all_users_blocked'];
                        @endphp
                        @foreach($data['transactions'] as $transaction)

                        @php

                          $date = "";
                          $order = "";
                          $code_transaction = "";
                          $icon = "";
                          $bank_data = "";
                          $user = "";
                          $type_upper = "";
                          $solicitation = "";
                          $confirmed = "";
                          $fee = "";
                          $b1_icon = "";
                          $button_status = "";
                          $class = "";
                          $blocked = "";

                          $order = "<span class='text-nowrap'>".$transaction->order_id."</span>";

                          $button_status = "";
                          // Button
                          if($transaction->receipt != ''){
                                $link_aws = Storage::disk('s3')->url('upcomprovante/'.$transaction->receipt);
                                $button_status .= "<a href='".$link_aws."' target='_blank'>";
                          }else{
                            if($transaction->status == "confirmed"){
                                $button_status .= "<a href='https://admin.fastpayments.com.br/comprovantePix/".$transaction->id."' class='comprovante' data-transaction='".$transaction->id."' target='_blank'>";
                            }
                          }

                          if($transaction->type_transaction != ""){
                              if($transaction->status == 'confirmed'){
                                  if($transaction->method_transaction == 'invoice'){
                                      if($transaction->confirmed_bank == '1'){
                                        $button_status .= '<button class="btn btn-sm buttonDash btn-success"><i class="fa fa-check"></i> CONFIRMED</button>';
                                      }else{
                                        $button_status .= '<button class="btn btn-sm buttonDash" style="background-color:#292927;color:#FFF;"><i class="fa fa-hourglass-half"></i> PROCESSING</button>';
                                      }
                                  }else{
                                    if($transaction->amount_confirmed >=  5){
                                      $button_status .= '<button class="btn btn-sm buttonDash btn-success"><i class="fa fa-check"></i> CONFIRMED</button>';
                                    }else{
                                        if($transaction->method_transaction == 'credit_card'){
                                            $button_status .= '<button class="btn btn-sm buttonDash btn-secondary"><i class="fa fa-check"></i> CONFIRMED CV</button>';
                                        }else{
                                            $button_status .= '<button class="btn btn-sm buttonDash btn-success"><i class="fa fa-check"></i> CONFIRMED</button>';
                                        }
                                    }
                                  }

                              }else if($transaction->status == 'canceled'){
                                  if($transaction->method_transaction == 'credit_card'){
                                      $error = DB::table('api_logs')->where('order_id', '=', $transaction->order_id)->first();
                                      if(!empty($error[0])){
                                          $message = json_decode($error->response_body,true);
                                          $button_status .= '<button class="btn btn-sm buttonDash btn-secondary"  data-toggle="tooltip" data-placement="top" title="'."message: ".$message['message']." code: ".$message['code'].'"><i class="fa fa-times"></i> CANCELEdD</button>';
                                      }else{
                                          $button_status .= '<button class="btn btn-sm buttonDash btn-secondary bg-canceled" data-toggle="tooltip" data-placement="top" title="'.$transaction->reason_status.'"><i class="fa fa-times"></i> CANCELED</button>';
                                      }

                                  }else{
                                      $button_status .= '<button class="btn btn-sm buttonDash btn-secondary bg-canceled"><i class="fa fa-times"></i> CANCELED</button>';
                                  }
                              }else if($transaction->status == 'pending'){
                                  $button_status .= '<button class="btn btn-sm buttonDash btn-info"><i class="fa fa-clock-o"></i> PENDING</button>';
                              }else if($transaction->status == 'refund'){
                                  if($transaction->amount_confirmed > 5){
                                      $button_status .= '<button class="btn btn-sm buttonDash bg-warning" style="color:#FFF !important;"><i class="fas fa-exchange-alt"></i> REFUND</button>';
                                  }else{
                                      $button_status .= '<button class="btn btn-sm buttonDash bg-warning" style="color:#FFF !important;"><i class="fas fa-exchange-alt"></i> REFUND CV</button>';
                                  }
                              }else if($transaction->status == 'freeze'){
                                  $button_status .= '<button class="btn btn-sm buttonDash bg-freeze"><i class="fa fa-snowflake-o"></i> FREEZE</button>';
                              }else{
                                  $button_status .= '<button class="btn btn-sm buttonDash bg-chargeback"><i class="fa fa-user-times"></i> CHARGEBACK</button>';
                              }
                          }else{
                              if($transaction->status == 'confirmed'){
                                  $button_status .= '<button class="btn btn-sm buttonDash btn-success"><i class="fa fa-check"></i> CONFIRMED</button>';
                              }else if($transaction->status == 'canceled'){
                                  $button_status .= '<button class="btn btn-sm buttonDash btn-secondary bg-canceled"><i class="fa fa-times"></i> CANCELED</button>';
                              }else if($transaction->status == 'pending'){
                                  $button_status .= '<button class="btn btn-sm buttonDash btn-info"><i class="fa fa-clock-o"></i> PENDING</button>';
                              }
                          }

                          if($transaction->receipt != ''){
                              $button_status .= "</a>";
                          }

                          //Button Transaction Canceled
                          if($transaction->status == 'canceled'){
                            if($transaction->method_transaction == 'TEF' && $transaction->type_transaction == 'withdraw'){
                                $button_status = "<a data-toggle='modal' href='#transactionInfo'  class='transaction-canceled' data-transaction-id=".$transaction->id." data-receipt=".$transaction->receipt." ><button class='btn btn-sm buttonDash btn-secondary bg-canceled'><i class='fa fa-times'></i> CANCELED</button></a>";
                            }
                          }

                          //transaction
                          if($transaction->method_transaction == 'credit_card'){
                              $method = 'Credit Card';
                          }else if($transaction->method_transaction == 'automatic_checking'){
                              $method = 'Shop';
                          }else if($transaction->method_transaction == 'invoice'){
                              $method = 'Invoice';
                          }else if($transaction->method_transaction == 'pix'){
                              $method = 'PIX';
                          }else{
                              $method = 'Bank Transfer';
                          }

                          //Confirmation icon
                          if($transaction->method_transaction == 'invoice'){
                                //empty icon
                                $b1_icon = '<image src="'.asset('images/empty.png').'" width="20"/>';
                          }else{
                              //empty icon
                              $b1_icon = '<image src="'.asset('images/empty.png').'" width="20"/>';
                          }

                          //icon
                          $bank = $banks_account->where('id', '=', $transaction->id_bank)->first();


                            if($transaction->method_transaction == 'automatic_checking'){

                                switch($bank->code){
                                    case"033": $icon = "<image src='".asset("img/santander.png")."' width='30'/>"; break;
                                    case"341": $icon = "<image src='".asset("img/itau.png")."' width='30'/>"; break;
                                    case"001": $icon = "<image src='".asset("img/banco-do-brasil.png")."' width='30'/>"; break;
                                    case"237": $icon = "<image src='".asset("img/bradesco.png")."' width='30'/>"; break;
                                    case"212": $icon = "<image src='".asset("img/original.png")."' width='30'/>"; break;
                                    case"999": $icon = "<image src='".asset("img/banco-intermediario-zp-new.png")."' width='30'/>"; break;
                                    case"998": $icon = "<image src='".asset("img/caixa-brl-new.png")."' width='30'/>"; break;
                                    case"104": $icon = "<image src='".asset("img/caixa.png")."' width='30'/>"; break;
                                    case"145": $icon = "<image src='".asset("img/ame.png")."' width='30'/>"; break;
                                    case"118": $icon = "<image src='".asset("img/mercado-pago.png")."' width='30'/>"; break;
                                    case"218": $icon = "<image src='".asset("img/bs2.png")."' width='30'/>"; break;
                                    case"221": $icon = "<image src='".asset("img/openpix.png")."' width='30'/>"; break;
                                }

                            }else if($transaction->method_transaction == 'invoice'){


                                $icon = "<image src='".asset("img/boleto.png")."' width='30'/>";


                            }else if($transaction->method_transaction == 'credit_card'){

                                if($transaction->brand != ''){
                                    switch($transaction->brand){
                                        case"master": $icon = "<image src='".asset("img/mastercard.png")."' width='30'/>"; break;
                                        case"mastercard": $icon = "<image src='".asset("img/mastercard.png")."' width='30'/>"; break;
                                        case"visa": $icon = "<image src='".asset("img/visa.png")."' width='30'/>"; break;
                                        case"elo": $icon = "<image src='".asset("img/elo.png")."' width='30'/>"; break;
                                        case"amex": $icon = "<image src='".asset("img/amex.png")."' width='30'/>"; break;
                                        case"hipercard": $icon = "<image src='".asset("img/hipercard.png")."' width='30'/>"; break;
                                        case"discover": $icon = "<image src='".asset("img/discover.png")."' width='30'/>"; break;
                                        case"jcb": $icon = "<image src='".asset("img/jcb.png")."' width='30'/>"; break;
                                        case"aura": $icon = "<image src='".asset("img/aura.png")."' width='30'/>"; break;
                                    }

                                    if($transaction->number_card != ''){
                                        $icon .= "<br/><br/>".$transaction->number_card;
                                    }

                                }else{

                                    if($transaction->bank->code == "763"){
                                        $icon = "<image src='".asset("img/pagseguro.png")."' width='30'/>";
                                    }else{
                                        $icon = "<image src='".asset("img/credit-card.png")."' width='30'/>";
                                    }

                                    if($transaction->number_card != ''){
                                    $icon .= "<br/><br/>".$transaction->number_card;
                                }

                                }

                            }else if($transaction->method_transaction == 'pix'){

                                $icon = "<image src='".asset("img/pix.png")."' width='30'/>";


                            }else if($transaction->method_transaction == 'TEF' && $transaction->id_bank != null){

                                switch($bank->code){
                                    case"033": $icon = "<image src='".asset("img/santander.png")."' width='30'/>"; break;
                                    case"341": $icon = "<image src='".asset("img/itau.png")."' width='30'/>"; break;
                                    case"001": $icon = "<image src='".asset("img/banco-do-brasil.png")."' width='30'/>"; break;
                                    case"237": $icon = "<image src='".asset("img/bradesco.png")."' width='30'/>"; break;
                                    case"212": $icon = "<image src='".asset("img/original.png")."' width='30'/>"; break;
                                    case"999": $icon = "<image src='".asset("img/banco-intermediario-zp-new.png")."' width='30'/>"; break;
                                    case"998": $icon = "<image src='".asset("img/caixa-brl-new.png")."' width='30'/>"; break;
                                    case"104": $icon = "<image src='".asset("img/caixa.png")."' width='30'/>"; break;
                                    case"145": $icon = "<image src='".asset("img/ame.png")."' width='30'/>"; break;
                                    case"118": $icon = "<image src='".asset("img/mercado-pago.png")."' width='30'/>"; break;
                                    case"218": $icon = "<image src='".asset("img/bs2.png")."' width='30'/>"; break;
                                    case"221": $icon = "<image src='".asset("img/openpix.png")."' width='30'/>"; break;
                                }

                            }else if($transaction->method_transaction == 'ame_digital'){

                            switch($bank->code){
                                case"033": $icon = "<image src='".asset("img/santander.png")."' width='30'/>"; break;
                                case"341": $icon = "<image src='".asset("img/itau.png")."' width='30'/>"; break;
                                case"001": $icon = "<image src='".asset("img/banco-do-brasil.png")."' width='30'/>"; break;
                                case"237": $icon = "<image src='".asset("img/bradesco.png")."' width='30'/>"; break;
                                case"212": $icon = "<image src='".asset("img/original.png")."' width='30'/>"; break;
                                case"999": $icon = "<image src='".asset("img/banco-intermediario-zp-new.png")."' width='30'/>"; break;
                                case"998": $icon = "<image src='".asset("img/caixa-brl-new.png")."' width='30'/>"; break;
                                case"104": $icon = "<image src='".asset("img/caixa.png")."' width='30'/>"; break;
                                case"145": $icon = "<image src='".asset("img/ame.png")."' width='30'/>"; break;
                                case"118": $icon = "<image src='".asset("img/mercado-pago.png")."' width='30'/>"; break;
                                case"218": $icon = "<image src='".asset("img/bs2.png")."' width='30'/>"; break;
                                case"221": $icon = "<image src='".asset("img/openpix.png")."' width='30'/>"; break;
                            }
                            if($transaction->number_card != ''){
                                $icon .= "<br/><br/>".$transaction->number_card;
                            }

                            if($transaction->number_card != ''){
                                $icon .= "<br/><br/>".$transaction->number_card;
                            }

                            }else if($transaction->method_transaction == 'TEF' && $transaction->type_transaction == 'withdraw'){
                                $icon = "<image src='".asset("img/banco-intermediario-zp-new.png")."' width='30'/>";
                            }else if($transaction->method_transaction == 'bank_transfer' && $transaction->type_transaction == 'withdraw'){
                              $icon = "<image src='".asset("img/banco-intermediario-zp-new.png")."' width='30'/>";
                            }else{
                                $icon = '';
                            }

                            $set = "";

                          if(auth()->user()->level == 'master'){
                                if(isset($bank->name)){
                                    $set .= $bank->name;
                                }

                                if(isset($bank->holder)){
                                    $set .= " - ".explode(" ",$bank->holder)[0];
                                }
                            }

                            if($set != ""){
                                $icon = $set."<br/>".$icon;
                            }

                          if($transaction->type_transaction == 'deposit'){
                              if($transaction->user_id != null){

                                  $account_number = '';
                                  $user_name = '';
                                  $user_document = '';
                                  $bank_name = '';
                                  $agency = '';
                                  //user
                                  if($transaction->user_account_data != ''){
                                      $array_user = json_decode(base64_decode($transaction->user_account_data),true);
                                      if(isset($array_user['name'])){$user_name = $array_user['name'];}
                                      if(isset($array_user['document'])){$user_document = $array_user['document'];}
                                      if(isset($array_user['bank_name'])){$bank_name = $array_user['bank_name'];}
                                      if(isset($array_user['agency'])){$agency = $array_user['agency'];}
                                      if(isset($array_user['account_number'])){$account_number = $array_user['account_number'];}
                                  }

                                  $user_blocked = $all_users_blocked->where('user_id', '=', $transaction->user_id)->where('client_id', '=', $transaction->client_id)->first();

                                  $blocked = "";

                                  if(!empty($user_blocked)){
                                      if($user_blocked->blocked == '1'){
                                          $blocked = "blocked";
                                      }else{
                                          $blocked = "";
                                      }

                                      if($user_blocked->highlight == '1'){
                                          $class = "watching";
                                      }else{
                                          $class = "";
                                      }

                                      $user = "<div class='text-center ".$blocked."'><span>$transaction->user_id</span></div>";

                                  }else{
                                      $user = "<div class='text-center'><span>$transaction->user_id</span></div>";
                                  }

                                //   if(auth()->user()->level == 'master'){
                                //       $user.="<div class='col-sm-3' style='padding: 0px 8px;' >";
                                //   }else{
                                //       if(in_array(14,$permitions)){
                                //           $user.="<div class='col-sm-3'  >";
                                //       }else{
                                //           $user.="<div class='col-sm-3' >";
                                //       }
                                //   }
                                //   $user.="
                                //       <a data-toggle='modal' href='#infoUser' class='view_info_user' data-user-id='$transaction->user_id' data-client-id='$transaction->client_id' data-transaction-id='$transaction->id' data-user-name='$user_name'
                                //       data-user-document='$user_document' data-bank-name='$bank_name' data-agency='$agency' data-account-number='$account_number'
                                //       data-type='$account_number' style='float:left;border: 2px solid #27a844; color: #FFF; background-color: #27a844; border-radius: 50px; font-size: 8px;padding: 2px;'>
                                //       INFOS
                                //       </a>
                                //   </div> ";

                                //   if(auth()->user()->level == 'merchant'){
                                //       if(in_array(30,$permitions)){
                                //           $user.="
                                //           <div class='col-sm-3' >
                                //               <a data-toggle='modal' href='#modalAsk' class='modal-ask' data-client='".$transaction->client_id."' data-order='$transaction->order_id'
                                //                   style='float:left;border: 2px solid #9d4b2f; color: #FFF; background-color: #9d4b2f; border-radius: 50px; font-size: 8px;padding: 2px;'>
                                //               ASK
                                //               </a>
                                //           </div>";
                                //       }
                                //   }
                                  $user.="</div>";

                              }else{
                                  $user = "<span>---</span>";
                              }
                          }else{
                            if(auth()->user()->level == 'master'){
                                    $user="
                                    <div class='col-sm-3' style='padding: 0px 8px;'>
                                        <a href='javascript:void(0);' class='change_status' data-transaction='$transaction->id'
                                        data-type='$transaction->type_transaction' data-method='$transaction->method_transaction'
                                        data-status='$transaction->status' data-order='$transaction->order_id' style='float:left;margin-left:20px;border: 2px solid #9fa827; color: #FFF; background-color: #9fa827; border-radius: 50px; font-size: 8px;padding: 2px;'>
                                        ACTION
                                        </a>
                                    </div>";
                                }else{
                                    $user = "<span>---</span>";
                                }
                          }

                          if($transaction->id_bank == null){
                              $bank_data = '--';
                          }else{
                              if(Auth::user()->level == 'merchant' && $transaction->method_transaction == 'credit_card'){
                                $bank_data = strtoupper($transaction->brand);
                              }else{
                                if(isset($bank->name)){ $bank_data .= $bank->name.'<br/>'; }
                                if(isset($bank->agency)){ $bank_data .= $bank->agency.'<br/>'; }
                                if(isset($bank->account)){ $bank_data .= $bank->account; }
                              }
                          }

                          $date = "<div class='text-left'><span>IN: ".date('d/m/Y H:i',strtotime($transaction->solicitation_date))."</span><br/>";

                          if($transaction->final_date != ''){
                              $date .= "<div class='text-left'><span>STATUS: ".date('d/m/Y H:i',strtotime($transaction->final_date))."</span><br/>";
                          }else{
                              $date .= "<div class='text-left'><span>STATUS: -- </span><br/>";
                          }

                          if($transaction->disponibilization_date != ''){
                              $date .= "<div class='text-left'><span>RELEASED: ".date('d/m/Y H:i',strtotime($transaction->disponibilization_date))."</span><br/>";
                          }else{
                              $date .= "<div class='text-left'><span>RELEASED: -- </span><br/>";
                          }

                          $date .= "</div>";

                          //amount
                          if($transaction->type_transaction == 'withdraw' && Auth::user()->level == 'merchant'){
                            $solicitation = "---";
                          }else {
                            $solicitation =  "<span style='text-align: right;' >R$ ".doubletostr($transaction->amount_solicitation).'</span>';
                          }

                          if($transaction->status == 'pending'){
                            $confirmed =     "<span style='text-align: right;' >".$currency.doubletostr(0).'</span>';
                          }else{
                            $confirmed =     "<span style='text-align: right;' >".$currency.doubletostr($transaction->final_amount).'</span>';
                          }

                          //fee
                          if($transaction->comission != ''){
                              $fee = "R$ ".doubletostr($transaction->comission);
                          }else{
                              $fee = "R$ ".doubletostr(0);
                          }
                          $type_upper = strtoupper($transaction->type_transaction);

                          switch($transaction->method_transaction){
                              case"pix": $code_transaction = $transaction->code; break;
                              case"invoice": if($bank->code == "218"){ $code_transaction = $transaction->data_bank; }else{ $code_transaction = $transaction->code; } break;
                              case"automatic_checking": $code_transaction = $transaction->code; break;
                              case"ame_digital": $code_transaction = $transaction->data_bank; break;
                              case"credit_card": $code_transaction = $transaction->payment_id; break;
                              case"debit_card": $code_transaction = $transaction->payment_id; break;
                              default: $code_transaction = $transaction->code;
                          }

                          if($code_transaction == ""){ $code_transaction = "---"; }

                        @endphp
                        <tr class="{{$class}}">
                            @if(in_array(15,$permitions))
                                <td><?=$date?></td>
                            @endif
                            @if(in_array(16,$permitions))
                                <td class="text-center">{{ $transaction->client->name }}<br/><?=$order?></td>
                            @endif
                            @if(in_array(18,$permitions))
                                <td><center><?=$icon?></center></td>
                            @endif
                            @if(Auth::user()->level == 'master')
                                @if(in_array(19,$permitions))
                                    <td class="text-center"><?=$bank_data?></td>
                                @endif
                            @endif
                            @if(in_array(20,$permitions))
                                <td class="text-center {{$blocked}}">
                                    @if(isset($transaction->user_id)) ID: {{ $transaction->user_id }}<br/> @endif
                                    @if(isset(json_decode(base64_decode($transaction->user_account_data),true)['name'])) Name: {{ strtoupper(json_decode(base64_decode($transaction->user_account_data),true)['name']) }}<br/> @endif
                                    @if(isset(json_decode(base64_decode($transaction->user_account_data),true)['document'])) CPF: {{ formatCnpjCpf(json_decode(base64_decode($transaction->user_account_data),true)['document']) }} @endif
                                </td>
                            @endif
                            @if(in_array(21,$permitions))
                                <td class="text-center"><?=$type_upper?></td>
                            @endif
                            @if(in_array(22,$permitions))
                                <td class="text-right"><?=$solicitation?></td>
                            @endif
                            @if(in_array(24,$permitions))
                                <td class="text-right"><?=$fee?></td>
                            @endif
                            @if(in_array(26,$permitions))
                                <td class='text-center' >
                                    <?=$button_status?>
                                    @if($transaction->status == "confirmed")
                                        &nbsp;&nbsp;
                                        <a class="size_icon resendCallback" href="#" data-id="{{ $transaction->id }}"><button class="btn btn-sm btn-primary text-white" style="font-size:12px;"><i class="fa fa-paper-plane"></i></button></a>
                                    @endif
                                </td>
                            @endif
                        </tr>

                        @endforeach
                    </tbody>
                    </table>

                    <div class="col-lg-12 mt-3 paginate">
                        {{ $data['transactions']->appends([
                            "client_id" => request()->get("client_id",""),
                            "type_transaction" => request()->get("type_transaction",""),
                            "date_start" => request()->get("date_start",""),
                            "date_end" => request()->get("date_end",""),
                            "search" => request()->get("search",""),
                        ])->links() }}
                    </div>

                    <input type="hidden" name="parameters" id="parameters" value="<?php print_r($data['array_parameters']) ?>" >
                </section>
            </div>
            <!-- /.card-body -->
          </div>
        </div>
        <!-- /.card-body -->
      </div>
    </section>
    <!-- /.content -->

    <!-- Modal -->
    <div class="modal fade" id="userInfo" tabindex="-1" role="dialog" aria-labelledby="userInfoLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
    <div class="modal-content">
    <div class="modal-header">
    <h5 class="modal-title" id="userInfoLabel">User Information</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
    </div>
    <div class="modal-body">

    <div id="carrega_informacao_usuario" class="carrega_informacao_usuario"></div>

    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary " data-dismiss="modal">Close</button>
    </div>
    </div>
    </div>
    </div>
@endsection

@section('js')
<script src="http://igorescobar.github.io/jQuery-Mask-Plugin/js/jquery.mask.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

<script type="text/javascript">
    $(document).ready(function(){

        $('.send-pagination').click(function(){
            var page = $(this).data('page');
            var parameters = $('#parameters').val()

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url:"{{switchUrl('transactions/searchfind')}}",
                method:"POST",
                beforeSend:function(x){
                    $('.section-table').html("<div class='row'><div class='col-sm-12 text-center'><div class='spinner-border loading-chat' role='status'><span class='sr-only'>Loading...</span></div></div></div>")
                },
                data:{page:page,parameters:parameters},
                success:function(response){
                    //console.log(response);
                    $('.section-table').html(response);
                },
                error:function(err){
                    console.log(err);
                }

            });
        })

        $('.comprovante').click(function(){

            let transaction = $(this).data('transaction')

            // $("form#"+transaction).submit();

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url:"{{switchUrl('comprovantePix')}}",
                method:"POST",
                data: {transaction: transaction},
                success:function(response){
                    console.log(response);

                },
                error:function(err){
                    console.log(err);
                }

            });
        });

        $('.openModalUser').click(function() {
            var button = $(this);
            var order = button.data('order')
            var client = button.data('client')

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url:"{{switchUrl('approveDeposit/user')}}",
                method:"POST",
                dataType:"json",
                data:{'order_id':order,'client_id':client},
                success:function(response){
                    console.log(response);
                    $("#carrega_informacao_usuario").html(response.table)
                },
                error:function(err){
                    console.log(err);
                }

            });

        })

        $(".resendCallback").click(function(){

            $('#load').show();
            var idtransaction = $(this).data('id');

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url:"{{switchUrl('resendCallback')}}",
                method:"POST",
                dataType:"json",
                data:{id_transaction:idtransaction},
                success:function(response){
                    console.log(response);
                    $('#load').hide();

                    Swal.fire(
                        response.status+'!',
                        response.message,
                        response.status
                        )

                    location.reload();

                },
                error:function(err){
                    console.log(err);
                }

            });

        });

        $('#datepicker_start').datepicker({
            format: 'dd/mm/yyyy',
        });
        $('#datepicker_end').datepicker({
            format: 'dd/mm/yyyy',
        });

        $('.datepicker_start').mask('00/00/0000');
        $('.datepicker_end').mask('00/00/0000');

        var table = $('#extract').DataTable( {
            responsive: true,
            dom: 'ftp',
        } );

        new $.fn.dataTable.FixedHeader( table );

    })
</script>
@endsection
