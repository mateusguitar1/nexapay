@extends('layouts.appdash')

@section('css')
<style type="text/css">
.wrappers{
    position: fixed;
    top: 0;
    left: 0;
    z-index: 9999;
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
.condition_btc{
    display:none;
    width:100%;
    padding:10px 0;
}
.condition_usdt{
    display:none;
    width:100%;
    padding:10px 0;
}
.condition_bank_info{
    display:block;
    width:100%;
    padding:10px 0;
}
.condition_pix{
    display:none;
    width:100%;
    padding:10px 8px;
}
</style>
@endsection

@section('content')
<div id="load"class="wrappers text-center"  style="display:none">
  <div class="spinner-border spin" role="status">
      <span class="sr-only">Loading...</span>
  </div>
</div>

@php
$client = \App\Models\Clients::where("id",auth()->user()->client_id)->first();
@endphp
<div class="col-md-12" style="padding: 0px;">
  <div class="card">
    <div class="card-header">
      <div class="row">
        <div class="col-sm-9"><h3>Withdrawal</h3></div>
        <div class="col-sm-3 text-right">
          {{-- @if($client->withdraw_permition === true)
            <a href="#!" data-toggle="modal" data-target="#add_batch_withdraw" class="btn btn-warning"></i>PIX Batch</a> &nbsp;&nbsp;
          @endif --}}
          {{-- <a href="#!" data-toggle="modal" data-target="#add_manual_withdraw" class="btn btn-primary"></i>New Withdraw</a> --}}
        </div>
      </div>
    </div>
    <div class="card-body" style="padding: 20px !important;">
      <form action="{{switchUrl('solicitation-withdrawal/search')}}" method="post">
      {{ csrf_field() }}
        <div class="row">
          <div class="form-group col-sm-3">
              <label for="month">DATE</label>
              <button type="button" class="btn btn-date" name="new_date" id="daterangepicker2" style="width:100%;">
                <i class="fa fa-calendar"></i>
                <span>{{date('F j, Y')}} - {{date('F j, Y')}}</span> <b class="caret"></b>
              </button>
              <input type="hidden" class="form-control" name="minall" placeholder="Start" id="minall"  autocomplete="off" />
              <input type="hidden" class="form-control" name="maxall" placeholder="End" id="maxall"  autocomplete="off" />
          </div>
          <div class="col-sm-2">
            <label for="search">SEARCH</label>
            <input type="text" name="search" id="search" class="form-control">
          </div>
          <div class="col-sm-1  offset-sm-6">
              <button style=" width: 100%; margin-top: 26px;" class='btn btn-success' type="submit"><i class="fa fa-search" aria-hidden="true"></i></button>
          </div>
        </div>
      </form>

      <br/>

      @php
        $countButton = 0;
      @endphp

      @if($data['transactions'])
        @foreach($data['transactions'] as $transaction)
          @if($transaction->method_transaction == "pix" && $transaction->status == "pending")
            @php $countButton++; @endphp
          @endif
        @endforeach
      @endif


      {{-- @if($countButton > 0)
        <button class="btn btn-success approvebatch">PAID WITHDRAW PIX</button>
        <br/><br/>
      @endif --}}

      <table class='table table-striped table-hover' style="font-size: 12px !important;">
      <thead>
        <tr>
          <th>SOLICITATION</th>
          <th>PAID</th>
          <th align="center" class="text-center">ID</th>
          <th align="center" class="text-center">USER ID</th>
          <th align="center" class="text-center">METHOD</th>
          <th align="right" class="text-right">AMOUNT</th>
          <th align="right" class="text-right">AMOUNT PAID</th>
          <th align="center" class="text-center">STATUS</th>
        </tr>
      </thead>
          <tbody>
            @if($data['transactions'])
              @foreach($data['transactions'] as $transaction)
              @php
                // if($transaction->client->currency == 'brl'){

                // }else{
                //   $currency = 'U$';
                // }

                $currency = 'R$';

                $reeceipt = $transaction->receipt;
                $button = "";
                // Button
                if($reeceipt != ''){
                    $button .= "<a href='".Storage::disk('s3')->url('upcomprovante/'.$transaction->receipt)."' target='_blank'>";
                }elseif($reeceipt == "" && $transaction->status == "confirmed"){
                    $button .= "<a href='https://admin.fastpayments.com.br/comprovantePix/".$transaction->id."' class='comprovante' data-transaction='".$transaction->id."' target='_blank'>";
                }

                if($transaction->status == 'pending'){
                  $button .= "<button class='btn btn-secondary btn-sm' type='button'>PENDIG</button>";
                  $paid_date = "-";
                }elseif($transaction->status == 'canceled'){
                  $button .= "<button class='btn btn-warning btn-sm' type='button' data-toggle='tooltip' data-placement='top' title='".$transaction->reason_status."'>CANCELED</button>";
                  $paid_date = "-";
                }elseif($transaction->status == 'confirmed'){
                  $button .= "<button class='btn btn-success btn-sm' type='button'>CONFIRMED</button>";
                  // $button = "<a class='btn btn-success btn-sm' href='/solicitation-withdrawal/".$transaction->id."/getWithdraw' target='blank'>CONFIRMED</a>";
                  $paid_date = date('d/m/Y H:i',strtotime($transaction->paid_date));
                }

                if($transaction->receipt != ''){
                  $button .= "</a>";
                }

              @endphp
              <tr>
                <td>{{date('d/m/Y H:i:s',strtotime($transaction->solicitation_date))}}</td>
                <td><?=$paid_date?></td>
                <td align="center" class="text-center">{{$transaction->order_id}}</td>
                <td align="center" class="text-center">{{$transaction->user_id}}</td>
                <td align="center" class="text-center">{{ strtoupper($transaction->method_transaction) }}</td>
                <td align="right" class="text-right">{{$currency}} {{number_format($transaction->amount_solicitation,2,',','.')}}</td>
                <td align="right" class="text-right">{{$currency}} {{number_format($transaction->amount_confirmed,2,',','.')}}</td>
                <td align="center" class="text-center"><?=$button?></td>
              </tr>
              @endforeach
            @endif
          </tbody>
      </table>
    </div>
  </div>
</div>


<!-- Modal -->
<div class="modal fade" id="add_manual_withdraw" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel">Create Manual Withdrawal</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                      <span aria-hidden="true">×</span>
                  </button>
              </div>

              <form id="formCreateWithdraw" method="post">
                  <div  class="modal-body">
                      <div class="row">
                        <div class="col-md-4 margin15">
                            <div>Merchant</div>
                            <input type="text" class="form-control" readonly value="{{ Auth()->User()->client->name }}" />
                            <input type="hidden" class="form-control client_id_withdrawal" value="{{Auth()->User()->client->id}}" name="client_id_withdrawal" />
                          </div>
                          <div class="col-md-4 margin15">
                              Requested Amount<br>
                              <div class="input-group">
                                  <div class="input-group-prepend">
                                      <div class="input-group-text">BRL</div>
                                  </div>
                                  <input required name="amount_solicitation" type="text" value="" required="" class="form-control money1 width100 text-left amount_solicitation_withdrawal" style="text-align:right;" placeholder="Amount BRL" maxlength="22">
                              </div>
                          </div>
                          <div class="col-md-4 margin15">
                              <div>Method</div>
                              <select required class="form-control method" name="method" id="method" style="width:100%;">
                                  <option value="bank_info" selected>BANK ACCOUNT</option>
                                  <option value="crypto">CRYPTO (BTC)</option>
                                  <option value="usdt">USDT-ERC20</option>
                                  <option value="pix">PIX KEY</option>
                              </select>
                          </div>

                          <div class="condition_pix">
                              <div class="row">
                                  <div class="col-md-4 margin15">
                                      PIX TYPE<br/>
                                      <select required class="form-control type_pixkey" name="type_pixkey" id="type_pixkey" style="width:100%;">
                                          <option value="cpf" selected>CPF</option>
                                          <option value="cnpj">CNPJ</option>
                                          <option value="email">E-MAIL</option>
                                          <option value="telefone">PHONE</option>
                                          <option value="aleatoria">RANDOM</option>
                                      </select>
                                  </div>
                                  <div class="col-md-8 margin15">
                                      PIX KEY<br/>
                                      <input type="text" id="pix_key" name="pix_key" class="form-control pix_key" style="width:100%;">
                                  </div>
                              </div>
                          </div>
                          <div class="condition_btc">
                              <div class="col-md-12 margin15">
                                  BTC<br/>
                                  <input type="text" id="hash_btc" name="hash_btc" class="form-control hash_btc" style="width:100%;">
                              </div>
                          </div>
                          <div class="condition_usdt">
                            <div class="col-md-12 margin15">
                                USDT-ERC20<br/>
                                <input type="text" id="hash_usdt" name="hash_usdt" class="form-control hash_usdt" style="width:100%;">
                            </div>
                        </div>
                          <div class="condition_bank_info">
                              <div class="col-md-12">
                                  <div class="row">
                                      <div class="col-md-6 margin15">
                                          Full name<br/>
                                          <input type="text" id="name" name="name" class="form-control name">
                                      </div>
                                      <div class="col-md-3 margin15">
                                          USER ID<br/>
                                          <input type="text" id="user_id" name="user_id" class="form-control user_id">
                                      </div>
                                      <div class="col-md-3 margin15">
                                          CPF / CNPJ <small>( Only Numbers )</small><br/>
                                          <input type="text" id="cpf" name="document" class="form-control document cpfcnpj">
                                      </div>
                                      <div class="col-md-3 margin15">
                                          Bank<br/>
                                          <input type="text" id="name_bank" name="name_bank" class="form-control name_bank">
                                      </div>
                                      <div class="col-md-3 margin15">
                                          Agency<br/>
                                          <input type="text" id="agency" name="agency" class="form-control agency">
                                      </div>
                                      <div class="col-md-3 margin15">
                                          Checking Account<br/>
                                          <input type="text" id="bank_account" name="bank_account" class="form-control bank_account">
                                      </div>
                                      <div class="col-md-3 margin15">
                                          Account Type<br/>
                                          <select class="form-control type_operation" name="type_operation" id="type_operation">
                                              <option value="corrente">CORRENTE</option>
                                              <option value="poupanca">POUPANÇA</option>
                                          </select>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>
                  <div class="modal-footer">
                      <button type="button" class="btn btn-warning pull-left" data-dismiss="modal" style="position: absolute;left:15px;color:#fff;">CANCEL</button>
                      <button type="submit" class="btn btn-primary pull-right">RECORD</button>
                  </div>
              </form>
          </div>
      </div>
  </div>
  </div>
<!-- Modal -->
{{-- <div class="modal fade" id="add_manual_withdraw" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">REQUEST WITHDRAWAL</h5>
      </div>
      <form id="send_form" method="post" onsubmit="return this.sendButton.disabled=true">
        <div style="display:table;width:100%;" class="modal-body">
          <div class="row">
            <div class="col-md-6 margin15">
              <div>Merchant</div>
              <input type="text" class="form-control" readonly value="{{ Auth()->User()->client->name }}" />
              <input type="hidden" class="form-control" value="{{Auth()->User()->id}}" name="client_id" />
            </div>

            <div class="col-md-6 margin15">
              Solicitation Amount<br/>
              <div class="input-group">
                <div class="input-group-prepend">
                  <div class="input-group-text">BRL</div>
                </div>
                <input name="amount_solicitation" type="text" value="" required class="form-control money width100 text-left" id="money" style="text-align:right;" placeholder="Valor BRL" />
              </div>
            </div>

            <div class="col-md-4 margin15">
              Solicitation Date<br/>
              <input type="text" id="solicitation_date" data-mask="00/00/0000 00:00:00" readonly value="<?=date("d/m/Y H:i:s");?>" name="solicitation_date" class="form-control" />
            </div>

            <div class="col-md-4 margin15">
              Order ID<br/>
              <input type="text" id="order_id" name="order_id" class="form-control">
            </div>

            <div class="col-md-4 margin15">
              User ID<br/>
            <input type="text" id="user_id"  name="user_id" class="form-control">
            </div>

            <div class="col-md-6 margin15">
              Full name<br/>
              <input type="text" id="name" name="name" class="form-control">
            </div>

            <div class="col-md-6 margin15">
              Document(CPF/CNPJ)<br/>
              <input type="text" name="document" class="form-control cpfcnpj">
            </div>

            <div class="col-md-3 margin15">
              Banks<br/>
              <select class="form-control selectpicker" data-live-search="true" name="name_bank" id="name_bank">
              <!-- <input type="text" id="name_bank" name="name_bank" class="form-control"> -->
              @foreach($data['banks_user'] as $bank)
                <option value="{{$bank['code']}} - {{$bank['name']}}">{{$bank['code']}} - {{$bank['name']}}</option>
              @endforeach
                </select>
            </div>

            <div class="col-md-3 margin15">
              Agency<br/>
              <input type="text" id="agency" name="agency" class="form-control">
            </div>

            <div class="col-md-3 margin15">
              Account<br/>
              <input type="text" id="bank_account" name="bank_account" class="form-control">
            </div>

            <div class="col-md-3 margin15">
              Type Account<br/>
              <select class="form-control" name="type_operation" id="type_operation">
                <option value="corrente">CORRENTE</option>
                <option value="poupanca">POUPANÇA</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger pull-left" style="position:absolute;left:10px;" data-dismiss="modal">CLOSE</button>
          <button type="submit" class="btn btn-primary pull-right" id="sendButton" name="sendButton">REQUEST</button>
        </div>
      </form>
    </div>
  </div>
</div> --}}

<!-- Modal -->
<div class="modal fade" id="add_batch_withdraw" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">BATCH WITHDRAWAL</h5>
      </div>
      <form id="send_batch" method="POST">
        <div style="display:table;width:100%;" class="modal-body">
          <div class="row">

            <div class="col-md-9 margin15">
              User ID<br/>
              <input type="text" id="user_id" name="user_id" class="form-control user_id" />
              <input type="hidden" class="client_id" name="client_id" value="{{ auth()->user()->client_id }}" />
            </div>

            <div class="col-md-3 margin15">
              <br/>
              <button type="button" class="btn btn-success searchbutton" style="width:100%;"><i class="fa fa-search"></i></button>
            </div>

            <div class="col-md-6 margin15">
              Order ID<br/>
              <input type="text" id="order_id" name="order_id" class="form-control" />
            </div>

            <div class="col-md-6 margin15">
              Amount Solicitation<br/>
              <div class="input-group">
                <div class="input-group-prepend">
                  <div class="input-group-text">BRL</div>
                </div>
                <input name="amount_solicitation" type="text" value="" required class="form-control money width100 text-left" id="money" style="text-align:right;" placeholder="Valor BRL" />
              </div>
            </div>

            <div class="col-md-6 margin15">
              Full name<br/>
              <input type="text" id="name" name="name" class="form-control name">
            </div>

            <div class="col-md-6 margin15">
              Document(CPF/CNPJ)<br/>
              <input type="text" name="document" class="form-control">
            </div>

          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger pull-left" style="position:absolute;left:10px;" data-dismiss="modal">CLOSE</button>
          <button type="submit" class="btn btn-primary pull-right sendButton" id="sendButton" disabled="true" name="sendButton">REQUEST</button>
        </div>
      </form>
    </div>
  </div>
</div>


@endsection
@section('js')

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.js"></script>

<script>

  $( document ).ready(function() {
    $('.money').mask("#.##0,00", {reverse: true});
    $('.money1').mask("#.##0,00", {reverse: true});
    $('.money2').mask("###0.00", {reverse: true});

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

    $(".method").change(function(e){

        var atual = $(this).val();

        if(atual == "bank_info"){

            $(".condition_bank_info").css("display","block");
            $(".condition_btc").css("display","none");
            $(".condition_usdt").css("display","none");
            $(".condition_pix").css("display","none");

        }else if(atual == "crypto"){

            $(".condition_bank_info").css("display","none");
            $(".condition_btc").css("display","block");
            $(".condition_usdt").css("display","none");
            $(".condition_pix").css("display","none");

        }else if(atual == "pix"){

            $(".condition_bank_info").css("display","none");
            $(".condition_btc").css("display","none");
            $(".condition_usdt").css("display","none");
            $(".condition_pix").css("display","block");

        }else if(atual == "usdt"){

            $(".condition_bank_info").css("display","none");
            $(".condition_btc").css("display","none");
            $(".condition_usdt").css("display","block");
            $(".condition_pix").css("display","none");

        }

    });

    $('#formCreateWithdraw').submit(function(e){
        e.preventDefault();
        // var data = $(this).serialize();

        var method = $(".method").val();

        var name = $(".name").val();
        var user_id = $(".user_id").val();
        var document = $(".document").val();
        var name_bank = $(".name_bank").val();
        var agency = $(".agency").val();
        var bank_account = $(".bank_account").val();
        var type_operation = $(".type_operation").val();

        var client_id = $(".client_id_withdrawal").val();
        var amount_solicitation = $(".amount_solicitation_withdrawal").val();

        var hash_btc = $(".hash_btc").val();
        var hash_usdt = $(".hash_usdt").val();
        var pix_key = $(".pix_key").val();
        var type_pixkey = $(".type_pixkey").val();

        if(method == "bank_info"){
            var data = {
                name : name,
                user_id : user_id,
                document : document,
                name_bank : name_bank,
                agency : agency,
                bank_account : bank_account,
                type_operation : type_operation,
                method : method,
                client_id : client_id,
                amount_solicitation : amount_solicitation,
            };
        }else if(method == "crypto"){
            var data = {
                hash_btc : hash_btc,
                method : method,
                client_id : client_id,
                amount_solicitation : amount_solicitation,
            };
        }else if(method == "usdt"){
            var data = {
                hash_usdt : hash_usdt,
                method : method,
                client_id : client_id,
                amount_solicitation : amount_solicitation,
            };
        }else if(method == "pix"){
            var data = {
                pix_key : pix_key,
                type_pixkey : type_pixkey,
                method : method,
                client_id : client_id,
                amount_solicitation : amount_solicitation,
            };
        }

        console.log(data);

        Swal.fire({
            title: 'Are you sure?',
            text: "Do you really want to create this withdraw?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, create it!'
        }).then((result) => {
            if (result.value) {

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                $.ajax({
                    url:"{{switchUrl('withdrawal/newWithdraw')}}",
                    method:"POST",
                    dataType:"json",
                    data:data,
                    success:function(response){
                        // console.log(response);
                        Swal.fire(
                            response.status.toUpperCase()+'!',
                            response.message,
                            response.status
                            )
                        $('#add_manual_withdraw').modal('hide');
                        $('.modal-backdrop').hide();
                        if(response.status == 'success'){
                            setTimeout(function(){ location.reload(); }, 1000);
                        }
                    },
                    error:function(err){
                        console.log(err);
                    }
                });

            }
        })
    });

    $(".searchbutton").click(function(){
      $('#load').show();

      var user_id = $(".user_id").val();
      var client_id = $(".client_id").val();

      if(user_id != ""){

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.ajax({
            url:"{{switchUrl('approveTed/search')}}",
            method:"POST",
            dataType:"json",
            data:{user_id,client_id},
            success:function(response){
                $('#load').hide();
                $(".name").val(response.name);
                $(".cpfcnpj").val(response.document);
                $(".sendButton").prop("disabled", false);
            },
            error:function(err){
                console.log(err);
            }
        });

      }

    });

    $(".cpfcnpj").keydown(function(){
        try {
            $(".cpfcnpj").unmask();
        } catch (e) {}

        var tamanho = $(".cpfcnpj").val().length;

        if(tamanho < 11){
            $(".cpfcnpj").mask("999.999.999-99");
        } else {
            $(".cpfcnpj").mask("99.999.999/9999-99");
        }

        // ajustando foco
        var elem = this;
        setTimeout(function(){
            // mudo a posição do seletor
            elem.selectionStart = elem.selectionEnd = 10000;
        }, 0);
        // reaplico o valor para mudar o foco
        var currentValue = $(this).val();
        $(this).val('');
        $(this).val(currentValue);
    });

    $('#send_form').submit(function(e){
      e.preventDefault();

    //   $('#add_manual_withdraw').modal('hide');

      $('#load').show();

      data = $(this).serialize();

      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
      });

      $.ajax({
          url:"{{switchUrl('withdrawal/newWithdraw')}}",
          method:"POST",
          dataType:"json",
          data:data,
          success:function(response){
                //console.log(response);
                $('#load').hide();
                Swal.fire(
                    response.status+'!',
                    response.message,
                    response.status
                )
                $('#add_manual_withdraw').modal('hide');
                location.reload();
          },
          error:function(err){
              console.log(err);
          }
      });
    })

    $('#send_batch').submit(function(e){
      e.preventDefault();
      $('#load').show();

      data = $(this).serialize()
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
      });

      $.ajax({
          url:"{{switchUrl('clients/newWithdrawBatch')}}",
          method:"POST",
          dataType:"json",
          data:data,
          success:function(response){
              $('#load').hide();
              if(response.status == "success"){

                Swal.fire(
                    response.status+'!',
                    response.message,
                    response.status
                )
                $('#add_batch_withdraw').modal('hide');
                location.reload();
              }else if(response.status == "error"){
                Swal.fire(
                    response.status+'!',
                    response.message,
                    response.status
                )
              }

          },
          error:function(err){
              console.log(err);
          }
      });

    });

    $(".approvebatch").click(function(){

      Swal.fire({
          title: 'Are you sure?',
          text: "Do you really want to approve all PIX withdrawals?",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Yes, approve it!'
      }).then((result) => {
          if (result.value) {

              $('#load').show();

              $.ajaxSetup({
                  headers: {
                      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                  }
              });

              $.ajax({
                  url:"{{switchUrl('approveWithdrawBatch')}}",
                  method:"POST",
                  dataType:"json",
                  data:{},
                  success:function(response){
                      console.log(response);

                      $('#load').hide();

                      Swal.fire(
                          response.status+'!',
                          response.message,
                          response.status
                      )

                    //   location.reload();

                  },
                  error:function(err){
                      console.log(err);
                  }

              });

          }
      });

    });

  $('#daterangepicker2').daterangepicker({
    ranges: {
      'Today': [moment(), moment()],
      'Yesterday': [moment().subtract('days', 1), moment().subtract('days', 1)],
      'Yesterday + Today': [moment().subtract('days', 1), moment()],
      'Last 7 Days': [moment().subtract('days', 6), moment()],
      'Last 30 Days': [moment().subtract('days', 29), moment()],
      'This Month': [moment().startOf('month'), moment().endOf('month')],
      'Last Month': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
    },
    opens: 'left',
    startDate: moment(),//.subtract('days', 29),
    endDate: moment()
    },
    function(start, end){
      $('#daterangepicker2 span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
      $("#minall").val(start.format('DD-MM-YYYY'));
      $("#maxall").val(end.format('DD-MM-YYYY'));
    });

  });

</script>
@endsection
