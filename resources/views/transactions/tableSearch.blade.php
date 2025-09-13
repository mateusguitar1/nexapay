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
                      $button_status .= '<button class="btn btn-sm buttonDash bg-refund"><i class="fa fa-exchange"></i> REFUND</button>';
                  }else{
                      $button_status .= '<button class="btn btn-sm buttonDash bg-refund"><i class="fa fa-exchange"></i> REFUND CV</button>';
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
                <td class="text-center"><?=$order?></td>
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
<div class="d-flex justify-content-center">
    <div class="toolbar">
        <div class="pager">
            <div class="pages ">
                <ul class="pagination">
                    <?php
                    $adiciona = "";
                    $totall_pag = "";
                    $qtd = 50;
                    if(!isset($data['page'])){
                        $p = 1;
                    }else{
                        $p = $data['page'];
                    }

                    $pags = ceil($data['all_transactions']/$qtd);

                    $max_links = 2;
                    // Exibe o primeiro link "primeira página", que não entra na contagem acima(3)
                    echo "<li><a href='javascript:void(0);' class='send-pagination' data-page='1'><i class='fa fa-angle-double-left' ></i></a></li> ";
                    $volta_uma = 1;
                    for($i = $p-$max_links; $i <= $p-1; $i++){

                    if($i <= 0) {

                        if($volta_uma == 1){
                            echo "<li><a href='javascript:void(0);' class='send-pagination' data-page='1'><i class='fa fa-chevron-left' ></i></a></li>";
                        }

                    }else{

                            if($volta_uma == 1){
                                $oe = ($i+1);
                                echo "<li><a href='javascript:void(0);' class='send-pagination' data-page='".$oe."'><i class='fa fa-chevron-left' ></i></a></li>";
                            }

                            echo "<li class='hidden-xs'> <a href='javascript:void(0);' class='send-pagination' data-page='".$i."'>".$i."</a> </li>";
                        }
                    $volta_uma++;
                    }
                    // Exibe a página atual, sem link, apenas o número
                    echo "<li class='active'><a href='javascript:void(0);'>".$p."</a></li>";
                    // Cria outro for(), desta vez para exibir 3 links após a página atual
                    $vai_uma = 1;

                    if($max_links <= $pags){
                        for($i = $p+1; $i <= $p+$max_links; $i++) {

                            if($vai_uma == 1){
                                if($i <= $pags) {
                                    $totall_pag = "<li><a href='javascript:void(0);' class='send-pagination' data-page='".$i."'><i class='fa fa-chevron-right' ></i></a></li> ";
                                }
                            }

                            if($i > $pags) {

                            }else{

                                // Se tiver tudo Ok gera os links.
                                echo "<li class='hidden-xs'><a href='javascript:void(0);' class='send-pagination' data-page='".$i."'>".$i."</a></li> ";

                            }

                        $vai_uma++;
                        }
                    }else{

                    }
                    //echo $totall_pag;
                    // Exibe o link "última página"
                    echo "<li><a href='javascript:void(0);' class='send-pagination' data-page='".$pags."'><i class='fa fa-angle-double-right' ></i></a></li> ";
                    ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<input type="hidden" name="parameters" id="parameters" value="<?php print_r($data['array_parameters']) ?>" >
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
                    $('.table-dash').html("<div class='row'><div class='col-sm-12 text-center'><div class='spinner-border loading-chat' role='status'><span class='sr-only'>Loading...</span></div></div></div>")
                },
                data:{page:page,parameters:parameters},
                success:function(response){
                    console.log(response);
                    //$('.table-dash').html(response);
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

    });
</script>
