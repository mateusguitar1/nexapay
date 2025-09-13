@extends('layouts.appdash')

@section('css')
<style>
    h1,h2,h3,h4,h5{
        margin-bottom: 0px;
    }
    .margin15{
        margin-bottom: 15px;
    }
    .horizontal-center{
        vertical-align: middle;align-self: center;
    }
    .fa-chevron-down{
        cursor:pointer;
    }
    .condition_btc{
        display:none;
        width:100%;
        padding:0;
    }
    .condition_usdt{
        display:none;
        width:100%;
        padding:0;
    }
    .condition_bank_info{
        display:block;
        width:100%;
        padding:0;
    }
    .condition_pix{
        display:none;
        width:100%;
        padding:0 8px;
    }
</style>
@endsection
@section('js')
<script src="http://igorescobar.github.io/jQuery-Mask-Plugin/js/jquery.mask.min.js"></script>
<script type="text/javascript">
    function checkInfoClient(client_id,type){
        $.ajax({
            url: "{{switchUrl('transactions/getInfosClient')}}",
            method:"POST",
            dataType:"json",
            data:{client_id,type},
            success:function(response){
                // console.log(response);

                if(typeof response.currency !== "undefined"){
                    $(".currency_client").html(response.currency);
                }
                if(typeof response.quotes !== "undefined"){
                    $("#quote_markup").val(response.quotes.markup);

                    var final_amount = $("#final_amount").val();
                    var quote = response.quotes.markup;
                    $(".quote_markup").val(response.quotes.markup);
                    $(".quote").val(response.quotes.quote);
                    $(".percent_markup").val(response.quotes.spread);
                    calcAmountSolicitation(final_amount,quote);
                }

            },
            error:function(err){
                console.log(err);
            }
        });
    }

    function calcAmountSolicitation(final_amount,quote){
        var count = "";

        count = parseFloat(final_amount/quote).toFixed(2).toLocaleString('pt-br',{minimumFractionDigits: 2 , style: 'currency', currency: 'BRL'});

       //$("#amount_solicitation").val(count);
    }

    $( document ).ready(function() {

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

        $('.money1').mask('#.##0,00', {reverse: true, maxlength: false});

        $(".createTransaction").click(function(){

            $('.money2').mask("###0.00", {reverse: true});
            // Add the following code if you want the name of the file appear on select
            $(".custom-file-input").on("change", function() {
                var fileName = $(this).val().split("\\").pop();
                $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
            });

            // Request Per Month B1
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            var client_id = $("#client_bank_transfer").val();
            var type = $("#type_transaction").val();

            checkInfoClient(client_id,type);

            $("#client_bank_transfer").change(function(){
                var client = $(this).val();
                var type = $("#type_transaction").val();

                checkInfoClient(client,type);
            });

            $("#type_transaction").change(function(){
                var type = $(this).val();
                var client = $("#client_bank_transfer").val();

                checkInfoClient(client,type);
            });

            $('#final_amount').on("input", function() {
                var final_amount = this.value;
                var quote = $("#quote_markup").val();

                calcAmountSolicitation(final_amount,quote);
            });

        });

        $('.addVoucher').click(function(){
            var order_id = $(this).data('order')
            var client_id = $(this).data('client')
            $("input[name='order_id']").val(order_id)
            $("input[name='client_id']").val(client_id)
        })

        $('.reportError').click(function(){
            var order_id = $(this).data('order')
            var client_id = $(this).data('client')
            var description = $(this).data('description')
            $("input[name='order_id']").val(order_id)
            $("input[name='client_id']").val(client_id)
            $("input[name='description_error']").val(description)
        })

        $('.updateComission').click(function(){
            var order_id = $(this).data('order')
            var client_id = $(this).data('client')
            $("input[name='order_id']").val(order_id)
            $("input[name='client_id']").val(client_id)
        })

        $('.approveWithdraw').click(function(){
            var amount_solicitation = $(this).data('amount-solicitation')
            var amount_confirmed = $(this).closest("div").parent("div").children('div .col-sm-4').children('input').val()

            $('.amount_before').val(amount_solicitation)
            $('.amount_after').val(amount_confirmed)

            var order_id = $(this).data('order')
            var client_id = $(this).data('client')
            $("input[name='order_id']").val(order_id)
            $("input[name='client_id']").val(client_id)
            $("input[name='amount_confirmed']").val(amount_confirmed)

        })

        $('#formError').submit(function(e){
            e.preventDefault();
            data = $(this).serialize();

            Swal.fire({
                title: 'Are you sure?',
                text: "Do you really want to cancel this withdraw?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, cancel it!'
            }).then((result) => {
                if (result.value) {

                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });

                    $.ajax({
                        url:"{{switchUrl('withdrawal/reportError')}}",
                        method:"POST",
                        dataType:"json",
                        data:data,
                        success:function(response){
                            console.log(response);
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

                }
            })

        })

        $('#formcreateTransaction').submit(function(e){
            e.preventDefault();
            var data = $(this).serialize();

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
                            console.log(response);
                            Swal.fire(
                                response.status+'!',
                                response.message,
                                response.status
                                )
                        },
                        error:function(err){
                            console.log(err);
                        }

                    });

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

            }else if(atual == "usdt"){

                $(".condition_bank_info").css("display","none");
                $(".condition_usdt").css("display","block");
                $(".condition_btc").css("display","none");
                $(".condition_pix").css("display","none");

            }else if(atual == "pix"){

                $(".condition_bank_info").css("display","none");
                $(".condition_btc").css("display","none");
                $(".condition_usdt").css("display","none");
                $(".condition_pix").css("display","block");

            }

        });

    });
</script>

@endsection
@section('content')

<?php
$transactionsAll = ( !empty($data['transactionsAll']) ? $data['transactionsAll'] : 0 );
$totalPage = ( !empty($data['totalPage']) ? $data['totalPage'] : 50 );

$total = $transactionsAll / $totalPage;
$total = number_format($total,2);
if( !empty($total) ){
    if( explode('.',$total)[1] > 0 ){
        $total = (int)$total++;
    }
}
$total = (int)$total;

?>

<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
    <div class="row mb-2">
        <div class="col-sm-10">
            <h1 class="m-0">Withdraw</h1>
        </div><!-- /.col -->
        @if(Auth::user()->level != 'payment')
            <div class="col-sm-2">
                <a href="#!" class='btn btn-primary text-white' style="width:100%;" data-toggle="modal" data-target="#createWithdraw">Create Withdraw</a>
            </div>
            {{-- <div class="col-sm-2">
                <a href="#!" class="btn btn-warning approvePix" style="width:100%;" ></i>Approve Pix</a>
            </div>
            <div class="col-sm-2">
                <a href="#!" class="btn btn-success sendPixOP" style="width:100%;" ></i>Send Pix OP</a>
            </div> --}}
        @endif
    </div><!-- /.row -->
    </div><!-- /.container-fluid -->
</div>
<!-- /.content-header -->

<div class="content">
    <div class="container-fluid">

        <div class="card">

            <div class="col-lg-12" style="margin-bottom: 15px;">

                <form  method="POST" action="{{switchUrl('/withdrawal/search')}}">
                    {{ csrf_field() }}
                    <div class="row" >
                        <div class="col-md-2">
                            <label for="client">CLIENT</label>
                            <select name="client_id" id="client" class='form-control'>
                                <option value="all">All</option>
                                @foreach($data['clients'] as $client)
                                <option value="{{ $client->id }}" {!! @(int)$data['current_search']['client_id'] === (int)$client->id ? 'selected' : Null !!}>{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="client">STATUS</label>
                            <select name="status[]" id="status[]" class='form-control selectpicker' multiple="multiple" size="2" required>
                                <option {!! !empty($data['current_search']) ? ( in_array('canceled', $data['current_search']['status']) ? 'selected' : Null ) : Null !!} value="canceled">Canceled</option>
                                <option {!! !empty($data['current_search']) ? ( in_array('confirmed', $data['current_search']['status']) ? 'selected' : Null ) : Null !!} value="confirmed">Confirmed</option>
                                <option {!! !empty($data['current_search']) ? ( in_array('pending', $data['current_search']['status']) ? 'selected' : Null ) : 'selected' !!} value="pending">Pending</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="search">SEARCH</label>
                            <input type="text" name="search" id="search" class='form-control' value="{!! !empty($data['current_search']['search']) ? $data['current_search']['search'] : Null !!}">
                        </div>
                        <div class="col-md-2">
                            <label for="client">DATE</label>
                            <select name="date" id="date" class='form-control'>
                                @for($i = 0;$i <= 11;$i++)
                                    <option value="{{ date("Y-m",strtotime("-".$i." month")) }}" @if(isset($data['request_date'])) @if($data['request_date'] == date("Y-m",strtotime("-".$i." month"))) selected="selected" @endif @endif>{{ date("Y-m",strtotime("-".$i." month")) }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-md-1 offset-md-1 mt1">
                            <button class="btn btn-success" type="submit" style="width:100%;margin-top:31px;"><i class="fa fa-search"></i></button>
                        </div>
                    </form>
                        @if ($data['transactions'])
                            <div class="col-md-1 ">
                                <form action="{{switchUrl('withdrawal/pdf')}}" method="POST" target="_blank">
                                    {{ csrf_field() }}
                                    <input type="hidden" name="transactions_pdf[]" value="{{ json_encode($data['transactions'] )}}">
                                    <button class="btn btn-danger " type="submit"  style="margin-top:32px;width:100%;">
                                        <i class="fas fa-file-pdf"></i>
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-1 ">
                                <button class="btn btn-warning pay_withdraw" type="button"  style="margin-top:32px;width:100%;color:#fff;">
                                    <i class="far fa-money-bill-alt"></i>
                                </button>
                            </div>
                        @endif
                    </div>


                <div class="card mt-4">
                    <div class="card-body" style="padding: 0px !important;margin: 0px !important;">
                        <div class="row" style="margin: 0;">
                            <div class="col-sm-12 bg-info text-white" style="text-align: right !important; padding-right: 15px !important">
                                Found <strong>{!! ( !empty($data['transactionsAll']) ? $data['transactionsAll'] : 0 ) !!}</strong> records = R${{ number_format($data['transactionAmount'], 2, ',', '.') }}
                            </div>
                            <div class="col-sm-12" style="padding:0 !important;">
                                <div id="accordion">
                                    <?php $i = 0; ?>
                                    @foreach($data['transactions'] as $key => $transaction)
                                        @php
                                            $icon='';
                                            if($transaction->bank){
                                                $bank_name = $transaction->bank->name;
                                            }else{
                                                $bank_name = '';
                                            }

                                            if($transaction->user_account_data){
                                                $array_user = json_decode(base64_decode($transaction->user_account_data),true);

                                                $account_number = '';
                                                $user_name = '';
                                                $user_document = '';
                                                $bank_name = '';
                                                $agency = '';
                                                $hash_btc = '';
                                                $hash_usdt = '';
                                                $pix_key = '';
                                                $type_pixkey = '';

                                                if(!isset($array_user['name'])){

                                                    if(!empty($array_user['hash_usdt'])){
                                                        $hash_usdt = $array_user['hash_usdt'];
                                                    }elseif(!empty($array_user['hash_btc'])){
                                                        $hash_btc = $array_user['hash_btc'];
                                                    }elseif(!empty($array_user['pix_key']) && !empty($array_user['type_pixkey'])){
                                                        $pix_key = $array_user['pix_key'];
                                                        $type_pixkey = $array_user['type_pixkey'];
                                                    }else{
                                                        $account_number = '';
                                                        $user_name = '';
                                                        $user_document = '';
                                                        $bank_name = '';
                                                        $agency = '';
                                                    }

                                                }else{

                                                    if(!empty($array_user['hash_usdt'])){
                                                        $hash_usdt = $array_user['hash_usdt'];
                                                    }elseif(isset($array_user['pix_key'],$array_user['type_pixkey'])){
                                                        if($array_user['pix_key'] != "" && $array_user['type_pixkey'] != ""){
                                                            $pix_key = $array_user['pix_key'];
                                                            $type_pixkey = $array_user['type_pixkey'];
                                                        }else{
                                                            $user_name = $array_user['name'];
                                                            $user_document = $array_user['document'];
                                                            $bank_name = $array_user['bank_name'];
                                                            $agency = $array_user['agency'];
                                                            $account_number = $array_user['account_number'];
                                                        }
                                                    }else{
                                                        $user_name = $array_user['name'];
                                                        $user_document = $array_user['document'];
                                                        $bank_name = $array_user['bank_name'];
                                                        $agency = $array_user['agency'];
                                                        $account_number = $array_user['account_number'];
                                                    }
                                                }

                                            }else{

                                                $account_number = '';
                                                $user_name = '';
                                                $user_document = '';
                                                $bank_name = '';
                                                $agency = '';

                                            }

                                            $orderIdSearch = $transaction->order_id;

                                            $log = \App\Models\Logs::where("client_id",$transaction->client_id)
                                                ->where("type","add")
                                                ->where("action","LIKE","%".$orderIdSearch."%")
                                                ->first();

                                            if(isset($log)){
                                                $user_system = $log->user->name;
                                            }else{
                                                $user_system = "API";
                                            }

                                        @endphp
                                        <div class="card" style="border: none !important;border-radius:0 !important;">
                                            <div class="card-header" id="heading{{$i}}" style="padding: 0 9px;">
                                                <div class="row">
                                                    <div class="col-sm-12" style="padding:0 !important;">
                                                        <table class="table pdf" style="margin-bottom:0 !important;">
                                                            @if( $key === 0 )
                                                                <thead>
                                                                    <tr>
                                                                        <td style="border:none !important;background-color:#000;color:#fff;width: 2% !important; text-align: left !important;"><input type="checkbox" class="select-all" data-check="unchecked" style="cursor:pointer;" /> #</td>
                                                                        <td style="border:none !important;background-color:#000;color:#fff;width: 8% !important; text-align: left !important;">DATE</td>
                                                                        <td style="border:none !important;background-color:#000;color:#fff;width: 6% !important; text-align: left !important;">CLIENT</td>
                                                                        <td style="border:none !important;background-color:#000;color:#fff;width: 8% !important; text-align: left !important;">NEXA ID</td>
                                                                        <td style="border:none !important;background-color:#000;color:#fff;width: 6.5% !important; text-align: left !important;">USER</td>
                                                                        @if(auth()->user()->id == "1" || auth()->user()->id == "3" || auth()->user()->id == "4")
                                                                            <td style="border:none !important;background-color:#000;color:#fff;width: 6.5% !important; text-align: left !important;">OWNER</td>
                                                                        @endif
                                                                        <td style="border:none !important;background-color:#000;color:#fff;width: 12% !important; text-align: left !important;">DATA TRANSACTION</td>
                                                                        <td style="border:none !important;background-color:#000;color:#fff;width: 6% !important; text-align: left !important;">AMOUNT</td>
                                                                        <td style="border:none !important;background-color:#000;color:#fff;width: 9% !important; text-align: center !important;">ACTION</td>
                                                                    </tr>
                                                                </thead>
                                                            @endif
                                                            <tbody id="pdf">
                                                                <tr>
                                                                    <td style="border:none !important;vertical-align:middle;width: 2% !important; text-align: left !important; overflow-wrap: anywhere !important;"><input type="checkbox" value="{{ $transaction->id }}" name="withdraw_select" style="cursor:pointer;" /> {!! $key+1+(( !empty($data['currentPage']) ? $data['currentPage'] : 0 )*$totalPage) !!}</td>
                                                                    <td style="border:none !important;vertical-align:middle;width: 8% !important; text-align: left !important; overflow-wrap: anywhere !important;">{!! date('d/m/Y H:i',strtotime($transaction->solicitation_date)) !!}</td>
                                                                    <td style="border:none !important;vertical-align:middle;width: 6% !important; text-align: left !important; overflow-wrap: anywhere !important;">{!! $transaction->client->name !!}</td>
                                                                    <td style="border:none !important;vertical-align:middle;width: 8% !important; text-align: left !important; overflow-wrap: anywhere !important;">{!! $transaction->id !!}<br/>ORDER ID: {!! $transaction->order_id  !!}</td>
                                                                    <td style="border:none !important;vertical-align:middle;width: 6.5% !important; text-align: left !important; overflow-wrap: anywhere !important;">{!! $transaction->user_id !!}</td>
                                                                    @if(auth()->user()->id == "1" || auth()->user()->id == "3" || auth()->user()->id == "4")
                                                                        <td style="border:none !important;vertical-align:middle;width: 6.5% !important; text-align: left !important; overflow-wrap: anywhere !important;">{!! $user_system !!}</td>
                                                                    @endif
                                                                    <td style="border:none !important;vertical-align:middle;width: 12% !important; text-align: left !important; overflow-wrap: anywhere !important;">
                                                                        @if(($user_name != ""))
                                                                            Name: {!! $user_name !!}<br/>
                                                                            Bank: {!! $bank_name !!} | Ag: '{!! $agency !!}<br/>
                                                                            AC: {!! formatNumberAccount($account_number) !!} | CPF: {!! formatCPF($user_document) !!}
                                                                        @elseif($hash_btc != "")
                                                                            ADDRESS: {!! $hash_btc !!}
                                                                        @elseif($hash_usdt != "")
                                                                            USDT-ERC20: {!! $hash_usdt !!}
                                                                        @elseif($pix_key != "" && $type_pixkey != "")
                                                                            PIX Key:<br/>{!! $pix_key !!}<br/>
                                                                            PIX Type:<br/>{{ strtoupper($type_pixkey) }}
                                                                        @endif
                                                                    </td>
                                                                    <td style="border:none !important;vertical-align:middle;width: 6% !important; text-align: left !important; overflow-wrap: anywhere !important;">{!! number_format($transaction->amount_solicitation,2,',','.') !!}</td>
                                                                    <td style="border:none !important;vertical-align:middle;width: 9% !important; text-align: left !important; overflow-wrap: anywhere !important;">
                                                                        <center>
                                                                            @if($transaction->status == "confirmed")
                                                                            <a class="size_icon resendCallback" href="#" data-id="{{ $transaction->id }}"><i class="fa fa-paper-plane text-info"></i></a>
                                                                            &nbsp;&nbsp;
                                                                            @endif
                                                                            <a data-toggle="modal" href="#updateComission" data-receipt="{{ $transaction->receipt }}" data-order="{{$transaction->order_id}}" data-client="{{$transaction->client_id}}" data-id="{{$transaction->id}}" data-status="{{ $transaction->status }}" data-solicitation="{{ $transaction->amount_solicitation }}"  data-final="{{ $transaction->final_amount }}" data-percent="{{ $transaction->percent_fee }}" data-fixed="{{ $transaction->fixed_fee }}"  class="col-lg-12 col-xs-12 updateComission size_icon" data-codigosaque="{!! $transaction->id !!}" style="cursor: pointer;"><i class="fas fa-pencil-alt text-success"></i></a>
                                                                            <a data-toggle="modal" href="#reportError" data-order="{{$transaction->id}}" data-client="{{$transaction->client_id}}" class="col-lg-12 col-xs-12 reportError size_icon" data-codigosaque="{!! $transaction->id !!}" style="cursor: pointer;"><i class="fa fa-times text-warning"></i></a>

                                                                            <a  data-order="{{$transaction->id}}" data-client="{{$transaction->client_id}}" class="col-lg-12 col-xs-12 delete size_icon"  style="cursor: pointer;"><i class="fa fa-trash text-danger"></i></a>
                                                                            @if($transaction->amount_solicitation  > '49999.99')
                                                                                <a data-toggle="modal" href="#split" data-order="{{$transaction->id}}" data-amount="{{$transaction->amount_solicitation}}" data-fee="{{ $transaction->comission }}"   class="col-lg-12 col-xs-12 split  size_icon" style="cursor: pointer;"><i class="fa fa-bars text-primary"></i></a>
                                                                            @endif
                                                                        </center>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>

                                            <div style="width: 100%;" id="collapse{{$i}}" class="collapse" aria-labelledby="heading{{$i}}" data-parent="#accordion">
                                                <div class="card-body">
                                                    <div class="row" style="margin: 0px !important;">

                                                        <div class="col-lg-4 col-xs-12" style="padding-top:15px;">
                                                            <div style="padding-left:15px;">
                                                                <div class="pull-left">{!! !empty($icon) ? $icon : Null !!}</div>
                                                                <div class="pull-left" style="font-size:22px;font-weight:600;margin: -2px 5px 5px 5px;">{{$bank_name}}</div>
                                                                <div style="clear:both;"><br></div>
                                                                <table width="100%" cellpadding="0" cellspacing="0" class="table" style="margin-bottom:0px;background-color: transparent;">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td style="border:1px solid #dedede;">Name :</td>
                                                                            <td style="border:1px solid #dedede;">{{$user_name}}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td style="border:1px solid #dedede;">Agency:</td>
                                                                            <td style="border:1px solid #dedede;">{{$agency}}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td style="border:1px solid #dedede;">Account:</td>
                                                                            <td style="border:1px solid #dedede;">{{$account_number}}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td style="border:1px solid #dedede;">CPF/CNPJ:</td>
                                                                            <td style="border:1px solid #dedede;">{{$user_document}}</td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>

                                                        <div class="col-lg-8 col-xs-12" style="margin-top: 55px;">
                                                            <table width="100%" cellspacing="0" cellpadding="0">
                                                                <tbody>
                                                                    <tr>
                                                                        <td width="50%" valign="top" style="padding:15px;">
                                                                            <form method="POST" action="{{switchUrl('/withdrawal/approve')}}" enctype="multipart/form-data">
                                                                                {{ csrf_field() }}
                                                                                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="table-striped table-bordered">
                                                                                    <tbody>
                                                                                        <tr>
                                                                                            <td colspan="3" style="border:1px solid #dedede;padding:5px;background-color:#efa11a;color:#FFF;text-align:center;">CONFIRM DATA</td>
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="table-striped table-bordered">
                                                                                    <tbody>
                                                                                        <tr>
                                                                                            <td width="33.3%" style="padding:15px;background-color:#fff;">
                                                                                                <input type="file" name="arquivo" class="form-control">
                                                                                            </td>
                                                                                            <td width="33.3%" style="padding:15px;background-color:#fff;">
                                                                                                <button type="button" data-toggle="modal" href="#reportError" data-order="{{$transaction->order_id}}" data-client="{{$transaction->client_id}}" class="col-lg-12 col-xs-12 btn btn-orange botao_erro reportError" data-codigosaque="145"><i class="fa fa-warning"></i> Report error</button>
                                                                                            </td>
                                                                                            <td width="33.3%" style="padding:15px;background-color:#fff;">
                                                                                                <button type="submit"  class="col-lg-12 col-xs-12 btn btn-success btnaprovarwithdraw"><i class="fa fa-check"></i> Approve</button>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                                <input type="text" name="codigo_withdraw" value="{{$transaction->order_id}}">
                                                                                <input type="text" name="codigo_cliente" value="{{$transaction->client_id}}">
                                                                            </form>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php $i++; ?>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-center">
    <div class="toolbar">
        <div class="pager">
            <div class="pages ">
                <ul class="pagination">
                    @for($i = 0; $i <= $total; $i++)
                    @if( !empty($data['current_search']) )
                    <form id="formPagination" name="formPagination" action="{{switchUrl('/withdrawal/search')}}" method="post">
                        {{ csrf_field() }}
                        @foreach( $data['current_search'] as $key => $search )
                        @if( !is_array($search) )
                        <input type="hidden" name="{!! $key !!}" value="{!! $search !!}">
                        @else
                        @foreach( $search as $key1 => $search1 )
                        <input type="hidden" name="{!! $key !!}[]" value="{!! $search1 !!}">
                        @endforeach
                        @endif
                        @endforeach
                        <input type="hidden" name="page" value="{!! $i !!}">
                        <button type="submit" class="btn btn-{!! (int)$data['currentPage']+1 === $i+1 ? 'info' : 'default' !!}" style="border: 1px solid #cecece !important; margin: 0px 3px !important; border-radius: 0px !important;">{!! $i+1 !!}</button>
                    </form>
                    @endif
                    @endfor
                </ul>
            </div>
        </div>
    </div>
</div>

<input type="hidden" name="withdrawal_list" class="withdrawal_list" value="{{ json_encode($data['withdrawal_list']) }}">



<!-- modal de envio de comprovante -->
<div class="modal fade" id="addVoucherModal" tabindex="-1" role="dialog" aria-labelledby="addVoucherLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addVoucherLabel">Add Voucher</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="row">
                <div class="col-md-12" style="padding: 15px;">
                    <form method="POST" action="{{switchUrl('/withdrawal/approve')}}" enctype="multipart/form-data">
                        {{ csrf_field() }}
                        <table width="100%" cellpadding="0" cellspacing="0" border="0" class="table-striped table-bordered">
                            <tr>
                                <td>
                                    <input type="file" name="arquivo" class="form-control">
                                </td>
                            </tr>
                            <tr><td>&nbsp;</td></tr>
                            <tr>
                                <td>
                                    <button type="submit" class="col-lg-12 col-xs-12 btn btn-success btnaprovarwithdraw"><i class="fa fa-check"></i> Submit and approve</button>
                                </td>
                            </tr>
                        </table>
                        {{-- <input class="transaction_id_return" id="transaction_id_return" type="hidden" name="transaction_id_return" > --}}
                        <input class="order_id" id="order_id" type="hidden" name="order_id" >
                        <input class="client_id" id="client_id" type="hidden" name="client_id" >
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- modal para dividir transações withdraw acima de 50.000,00 -->
<div class="modal fade" id="split" tabindex="-1" role="dialog" aria-labelledby="split" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addVoucherLabel">Split Transaction</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="splitTransaction"action="{{switchUrl('/withdrawal/split')}}" method="post">
                {{ csrf_field() }}
                <div class="row">
                    <input type="hidden" name="transaction" class="form-control ">
                <div class="col-md-6" style="padding: 15px;">
                    <label for="">Amount Solicitation</label>
                    <input type="text" name="amount" class="form-control money" readonly>
                </div>
                <div class="col-md-6" style="padding: 15px;">
                    <label for="">Fee</label>
                    <input type="text" name="fee" class="form-control " readonly>
                </div>
            </div>

            <div style="width: 90%; margin: 1%">
                <div class="row">
                    <div class="col-md-6" >
                        <label for="">Quantas vezes deseja dividir?</label>
                        <select name="divide" id="divide" class='form-control'>
                            @for($i = 1;$i <= 11;$i++)
                                <option value="{{ $i }}" > {{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-6" >
                        <button type="button" class="btn btn-primary next" style="margin-top:25px">Next</button>
                    </div>
                </div>
                <hr>
                <div id="campos" style="width: 90%; margin: 20px 1% 0 1%; ">

                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary  btnSplitTransaction" disabled >Save changes</button>
            </form>
            </div>
        </div>
    </div>
</div>



<!-- modal de envio de comprovante -->
<div class="modal fade" id="reportError" tabindex="-1" role="dialog" aria-labelledby="reportErrorLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportErrorLabel">Error Notification</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="row">
                <div class="col-md-12" style="padding: 15px;">
                    <form method="POST" action="{{switchUrl('withdrawal/reportError')}}" enctype="multipart/form-data">
                        {{ csrf_field() }}
                        <table width="100%" cellpadding="0" cellspacing="0" border="0" class="table-striped table-bordered">
                            <tr>
                                <td>
                                    <label for="optionsRadios1">
                                        <input type="radio" name="type_error" id="optionsRadios1" value="incorrect_account_data" checked="">
                                        Incorrect account data
                                    </label>
                                    <br>
                                    <label for="optionsRadios6">
                                        <input type="radio" name="type_error" id="optionsRadios6" value="other_error">
                                        Other
                                    </label>
                                </td>
                            </tr>
                            <tr><td>&nbsp;</td></tr><tr>
                                <td>
                                    <textarea name="description_error" class="form-control" style="height:100px;" placeholder="Description erro her ..."></textarea>
                                </td>
                            </tr>
                            <tr><td>&nbsp;</td></tr>
                            <tr>
                                <td>
                                    <button type="submit" class="col-lg-12 col-xs-12 btn btn-success btnaprovarwithdraw"><i class="fa fa-check"></i> Submit error</button>
                                </td>
                            </tr>
                        </table>
                        <input class="reportError" type="hidden" name="order_id" >
                        <input class="reportError" type="hidden" name="client_id" >
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>




<!-- modal de atualização de comissão -->
<div class="modal fade" id="updateComission" tabindex="-1" role="dialog" aria-labelledby="updateComissionLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateComissionLabel">Approve Withdrawal</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="row">
                <div class="col-md-12" style="padding: 15px;">
                    <form method="POST" action="{{switchUrl('withdrawal/updateComission')}}" enctype="multipart/form-data">
                        {{ csrf_field() }}
                        <div class="row">
                            <div class="col-md-4">
                                <label for="">Percent Fee</label>
                                <input type="text" name="percent_fee" value="" class="form-control percent_fee" >
                                <input type="hidden" name="id" value="" class="form-control id" >
                            </div>
                            <div class="col-md-4">
                                <label for="">Fixed Fee</label>
                                <input type="text" value="" name="fixed_fee" class="form-control fixed_fee" >
                            </div>
                            <div class="col-md-4">
                                <label for="">Comission</label>
                                <input type="text" name="comission" readonly="" class="form-control comission" >
                            </div>
                        </div><br>
                        <div class="row">

                            <div class="col-md-6">
                                <label for="">Amount Solicitation</label>
                                <input type="text" name="amount_solicitation" class="form-control amount_solicitation" >
                            </div>
                            <div class="col-md-6">
                                @php
                                    $status = array( 'pending', 'confirmed', 'canceled');
                                @endphp
                                <label for="">Status</label>
                                <select name="status" id="" class="form-control">
                                    @foreach ($status as $item)
                                        <option value="{{ $item }}">{{ $item }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div><br>
                        <div class="row">
                            <div class="col-md-12">
                                <label for="">Receipt</label>
                                <input type="file" name="arquivo" class="form-control">
                                <input type="hidden" name="receipt"  class="form-control receipt" >
                            </div>

                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary btnaprovarwithdraw ">Save changes</button>
                </div>
            </form>
        </div>

    </div>
</div>



<!-- Modal -->
<div class="modal fade" id="createWithdraw" tabindex="-1" role="dialog" aria-labelledby="createWithdrawTitle" aria-hidden="true">
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
                            <div>Client</div>
                            <select required class="form-control client_id_withdrawal" name="client_id" id="client" style="width:100%;">
                                @foreach($data['clients'] as $client)
                                    <option value="{{$client->id}}">{{$client->name}}</option>
                                @endforeach
                            </select>
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
<div class="modal fade" id="createTransaction" tabindex="-1" role="dialog" aria-labelledby="createTransactionTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Create Manual Withdrawal</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            @include('transactions.formulario', ['modal' => 1,'returnTo'=>'/withdrawal'])
        </div>
    </div>
</div>
</div>

<!-- Modal -->
<div class="modal fade" id="approveWithdraws" tabindex="-1" role="dialog" aria-labelledby="approveWithdraws" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-content">
                <form method="POST" class="approveWithdrawSelect">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">APPROVE WITHDRAWALS</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="input-group has-validation">
                            <input type="text" class="form-control code" id="code" name="code" placeholder="2FA CODE" required maxlength="6">
                            <span class="input-group-text sendFormWithdraw" style="background-color:#28a745;color:#FFF;cursor:pointer;"><i class="far fa-check-circle"></i></span>
                        </div>
                    </div>
                    <input type="hidden" name="withdrawals_id" value="" class="withdrawals_id">
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script src="http://igorescobar.github.io/jQuery-Mask-Plugin/js/jquery.mask.min.js"></script>
<script type="text/javascript">


    $(document).ready(function() {
        $('.money').mask('#.##0,00', {reverse: true, maxlength: false});

        $(document).on("focus", ".money", function() {
            $(this).mask('#.##0,00', {reverse: true});
        });

        //função para deletar transação
        $('.approvePix').click(function(e){

            var withdrawal_list = $(".withdrawal_list").val();

            Swal.fire({
                title: 'Are you sure?',
                text: "Do you really want to approve all transactions pix?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, approve all!'
            }).then((result) => {
                if (result.value) {

                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });

                    $.ajax({
                        url:"{{switchUrl('withdrawal/approvePixBatch')}}",
                        method:"POST",
                        dataType:"json",
                        data:{withdrawal_list: withdrawal_list},
                        success:function(response){
                            console.log(response);

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

                }
            })
        });

        //função para deletar transação
        $('.sendPixOP').click(function(e){

            var withdrawal_list = $(".withdrawal_list").val();

            Swal.fire({
                title: 'Are you sure?',
                text: "Do you really want to send all transactions pix?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, send all!'
            }).then((result) => {
                if (result.value) {

                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });

                    $.ajax({
                        url:"{{switchUrl('withdrawal/sendPixBatch')}}",
                        method:"POST",
                        dataType:"json",
                        data:{withdrawal_list: withdrawal_list},
                        success:function(response){
                            console.log(response);

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

                }
            })
        });

        $('.select-all').click(function(){
            let select = $(this).attr('data-check');
            if(select == 'checked'){
                $(this).attr('data-check','unchecked');
                $("input[name='withdraw_select']").prop( "checked", false );
            }else if(select == 'unchecked'){
                $(this).attr('data-check','checked');
                $("input[name='withdraw_select']").prop( "checked", true );
            }

        });

        $('.next').on('click', function(){

            var vezes = $("select[name='divide']").val();

            var campos = '<div class="row"><div class="col-md-6"><label for="">Amount</label><input type="text" name="amount_new[]" class="form-control money"></div><div class="col-md-6"><label for="">Fee</label><input type="text" name="fee_new[]" class="form-control money"></div></div>';

            for(var i = 0; i < vezes; i++){
                $("#campos").append(campos);
            }

            $('.btnSplitTransaction').attr("disabled", false);

            return false;
        });

        $(".pay_withdraw").click(function(){
            var arr = [];
            $.each($("input[name='withdraw_select']:checked"), function(){
                arr.push($(this).val());
            });
            if(arr.length > 0){
            $("#approveWithdraws").modal("show");
            $(".withdrawals_id").val(arr.join(","));
            }else{
            Swal.fire(
            "Error !",
            "Select transactions to pay",
            "error"
            )
            }
        });

        $(".sendFormWithdraw").click(function(){
            $(".approveWithdrawSelect").submit();
        });

        $(".approveWithdrawSelect").submit(function(e){
            e.preventDefault();

            var code = $(".code").val();
            var withdrawals_id = $(".withdrawals_id").val();

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url:"{{switchUrl('check2fa')}}",
                method:"POST",
                dataType:"json",
                data:{withdrawals_id,code},
                success:function(response){

                    if(response.status == "error"){
                        Swal.fire(
                            response.status+'!',
                            response.message,
                            response.status
                        )
                    }else{
                        Swal.fire(
                            response.status+'!',
                            response.message,
                            response.status
                        )
                        setTimeout(function(){ location.reload(); }, 3000);

                    }

                },
                error:function(err){
                    console.log(err);
                }

            });

        });

        $(".resendCallback").click(function(){

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



        $('#addVoucherModal').on('show.bs.modal', function (event) {

            var button = $(event.relatedTarget);
            var client_id = button.data('clientid');
            var order_id = button.data('orderid');

            $(".client_id").val(client_id);
            $(".order_id").val(order_id);

        })

        $('.reportError').click(function(){
            var order_id = $(this).data('order')
            var client_id = $(this).data('client')
            var description = $(this).data('description')
            $("input[name='order_id']").val(order_id)
            $("input[name='client_id']").val(client_id)
            $("input[name='description_error']").val(description)
        })

        $('.split').click(function(){
            var id = $(this).data('order');
            var amount = $(this).data('amount');
            var fee = $(this).data('fee');

            amount = amount.toLocaleString('pt-br', {minimumFractionDigits: 2});

            $("input[name='transaction']").val(id);
            $("input[name='amount']").val(amount);
            $("input[name='fee']").val(fee);
        });

        $('.updateComission').click(function(){
            var fixed = $(this).data('fixed')
            var percent = $(this).data('percent')
            var status = $(this).data('status')
            var solicitation = $(this).data('solicitation')
            var final = $(this).data('final')
            var id = $(this).data('id')
            var receipt = $(this).data('receipt')

            $('.comission').val(fixed+percent)
            $('.percent_fee').val(percent)
            $('.fixed_fee').val(fixed)
            $('.status').val(status)
            $('.final_amount').val(solicitation)
            $('.amount_solicitation').val(solicitation)
            $('.id').val(id)
            $('.receipt').val(receipt)
        })

        $('.approveWithdraw').click(function(){
            var amount_solicitation = $(this).data('amount-solicitation')
            //var amount_confirmed = $(this).closest("div").parent("div").children('div .col-sm-4').children('input').val()
            var amount_confirmed = amount_solicitation

            $('.amount_before').val(amount_solicitation)
            $('.amount_after').val(amount_confirmed)

            var order_id = $(this).data('order')
            var client_id = $(this).data('client')
            $("input[name='order_id']").val(order_id)
            $("input[name='client_id']").val(client_id)
            $("input[name='amount_confirmed']").val(amount_confirmed)
        })

        $('#formError').submit(function(e){
            e.preventDefault();
            data = $(this).serialize();

            Swal.fire({
                title: 'Are you sure?',
                text: "Do you really want to cancel this withdraw?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, cancel it!'
            }).then((result) => {
                if (result.value) {

                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });

                    $.ajax({
                        url:"{{switchUrl('withdrawal/reportError')}}",
                        method:"POST",
                        dataType:"json",
                        data:data,
                        success:function(response){
                            console.log(response);
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

                }
            })
        })

        $(".splitTransaction").submit(function(e){
            e.preventDefault();
            var data = $(this).serialize();


            Swal.fire({
                title: 'Are you sure?',
                text: "Do you really want to split this transaction?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, split it!'
            }).then((result) => {
                if (result.value) {

                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });

                    $.ajax({
                        url:"{{switchUrl('withdrawal/split')}}",
                        method:"POST",
                        dataType:"json",
                        data:data,
                        success:function(response){
                            // console.log(response);
                           if(response.status == 'error'){
                            Swal.fire(
                                response.status+'!',
                                response.message,
                                response.status
                                )
                           }else{
                            Swal.fire(
                                response.status+'!',
                                response.message,
                                response.status
                                )
                            $('#split').modal('hide');
                            location.reload();
                           }
                        },
                        error:function(err){
                            console.log(err);
                        }
                    });

                }
            })
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
                                response.status+'!',
                                response.message,
                                response.status
                                )
                            $('#modalAsk').modal('hide');
                            $('.modal-backdrop').hide();
                            location.reload();
                        },
                        error:function(err){
                            console.log(err);
                        }
                    });

                }
            })
        });

        $('#formcreateTransaction').submit(function(e){
            e.preventDefault();
            var data = $(this).serialize();

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
                            console.log(response);
                            Swal.fire(
                                response.status+'!',
                                response.message,
                                response.status
                                )
                        },
                        error:function(err){
                            console.log(err);
                        }

                    });

                }
            })
        })

        //função para deletar transação
        $('.delete').click(function(e){
            var order_id = $(this).data('order')

            Swal.fire({
                title: 'Are you sure?',
                text: "Do you really want to delete this transaction?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.value) {

                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });

                    $.ajax({
                        url:"{{switchUrl('withdrawal/delete')}}",
                        method:"POST",
                        dataType:"json",
                        data:{order_id: order_id},
                        success:function(response){
                            console.log(response);
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

                }
            })
        })
    });
</script>




@endsection
