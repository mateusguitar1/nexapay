@extends('layouts.appdash')
@section('css')
<style>

    .usd{
    background: rgba(139, 195, 74, 0.25);
    border-bottom: 2px solid #FFF;
}
</style>
@endsection
@section('content')

    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-11">
                <h1 class="m-0">Merchants</h1>
            </div><!-- /.col -->
            <div class="col-sm-1">
                <a class='btn btn-primary' href="{{switchUrl('merchants/create')}}" style="padding:7px 10px;width:100%;">
                    <i class="fa fa-users"></i> New Client
                </a>
            </div><!-- /.col -->
        </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <div class="content">
        <div class="container-fluid">

            <div class="card">

                <div class="card-body">
                    <form id='search' action="{{switchUrl('merchants/search')}}" method="post">
                    {{ csrf_field() }}
                        <div class="row">
                            <div class="form-group col-sm-1">
                                <label for="month">Month</label>
                                <select style="" name="date" class="form-control" id="month">
                                    <option {{$data['date'] && $data['date'] == date('Y-m') ? 'selected' : '' }} value="{{date('Y-m')}}" >{{date('Y-m')}}</option>
                                    <option {{$data['date'] && $data['date'] == date('Y-m',strtotime('-1 month')) ? 'selected' : '' }} value="{{date('Y-m',strtotime('-1 month'))}}" >{{date('Y-m',strtotime('-1 month'))}}</option>
                                    <option {{$data['date'] && $data['date'] == date('Y-m',strtotime('-2 month')) ? 'selected' : '' }} value="{{date('Y-m',strtotime('-2 month'))}}" >{{date('Y-m',strtotime('-2 month'))}}</option>
                                    <option {{$data['date'] && $data['date'] == date('Y-m',strtotime('-3 month')) ? 'selected' : '' }} value="{{date('Y-m',strtotime('-3 month'))}}" >{{date('Y-m',strtotime('-3 month'))}}</option>
                                    <option {{$data['date'] && $data['date'] == date('Y-m',strtotime('-4 month')) ? 'selected' : '' }} value="{{date('Y-m',strtotime('-4 month'))}}" >{{date('Y-m',strtotime('-4 month'))}}</option>
                                </select>
                            </div>
                        </div>
                    </form>
                    <br>
                    @php $total_fee = 0; @endphp
                    <table class='table'>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Fees *</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['merchants'] as $merchant)
                            <?php

                                $abbreviation = 'R$ ';
                                $first_date = date('Y-m-01 00:00:00', strtotime($data['first_date']));
                                $last_date = date('Y-m-d 23:59:59', strtotime($data['last_date']));

                                $fee = \App\Models\Extract::where("client_id",$merchant->id)
                                    ->where("type_transaction_extract","cash-out")
                                    ->whereBetween("created_at",[$first_date,$last_date])
                                    ->whereIn("description_code",["CM01","CM02","CM03","CM04"])
                                    ->sum("final_amount");

                                if($merchant->contract != ""){
                                    $link_contract = Storage::disk('s3')->url('contract/'.$merchant->contract);
                                }else{
                                    $link_contract = "";
                                }

                                $total_fee += $fee;


                            ?>
                            <tr class="{{$merchant->currency == 'brl' ? '' : 'usd' }}">
                                <td><a href="#!" data-id="{{$merchant->id}}" data-toggle="modal" data-target="#getClient" >{{$merchant->name}}</a></td>
                                <td>{{$abbreviation}} {{ number_format(($fee * (-1)),2,',','.') }}</td>
                                <td class="text-right">
                                    <a class="btn  btn-primary" href='{{ switchUrl("merchants/$merchant->id/edit") }}'>
                                        Edit
                                    </a>
                                    <a class="btn  {{$merchant->contract != '' ? 'btn-info' : 'btn-secondary' }}" href=" {{$merchant->contract != '' ? $link_contract : '#!' }}" {{$merchant->contract != '' ? 'target="_blank"' : ''}} >
                                        Contract
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                            <tr class="brl">
                                <td colspan="3" style="font-size:16px;"><b>TOTAL R$ {{ number_format(($total_fee * (-1)),2,',','.') }}</b></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>




<!-- Modal -->
<div class="modal fade" id="getClient" tabindex="-1" role="dialog" aria-labelledby="getClientLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document" style="max-width: 1024px;">
    <div class="modal-content">
      <div class="modal-header">

        <h5 class="modal-title" id="getClientLabel" style="color: #7f7f7f;" >
            <span id="span_client_name"></span>
            <span style="text-transform: uppercase;" id="span_client_currency"></span>
            <span style="margin-left:500px;"id="span_client_contact"></span>
            <span id="span_client_email"></span>
        </h5>

        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
            <div class="col-sm-1 text-center">
                <i class="fa fa-2x fa-money blue"></i>
            </div>
            <div class="col-sm-11">
                <div class="row">
                    <div class="col-sm-3">
                        <h5>INVOICE</h5>
                        <table>
                            <tr>
                                <td>Maximum = BRL <span id="span_max_boleto"></span></td>
                            </tr>
                            <tr>
                                <td>Minimum = BRL <span id="span_min_boleto"></span></td>
                            </tr>
                            <tr>
                                <td>Fee Paid = BRL <span id="span_boleto_absolute"></span> + <span id="span_boleto_percent"></span> %</td>
                            </tr>
                            <tr>
                                <td>Fee Cancel = BRL <span id="span_boleto_cancel"></span></td>
                            </tr>
                            <tr>
                                <td>Due in: <span id="span_client_safe_boleto"></span> days</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-sm-3">
                        <h5>PIX</h5>
                        <table>
                            <tr>
                                <td>Maximum = BRL <span id="span_max_pix"></span></td>
                            </tr>
                            <tr>
                                <td>Minimum = BRL <span id="span_min_pix"></span></td>
                            </tr>
                            <tr>
                                <td>Fee = BRL <span id="span_pix_absolute"></span> + <span id="span_pix_percent"></span> %</td>
                            </tr>
                            <tr>
                                <td>&nbsp</td>
                            </tr>
                            <tr>
                                <td>Due in: <span id="span_client_safe_pix"></span> days</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-sm-3">
                        <h5>WITHDRAWALS</h5>
                        <table>
                            <tr>
                                <td>Maximum = BRL <span id="span_max_withdraw"></span></td>
                            </tr>
                            <tr>
                                <td>Minimum = BRL <span id="span_min_withdraw"></span></td>
                            </tr>
                            <tr>
                                <td>Fee = BRL <span id="span_withdraw_absolute"></span> + <span id="span_withdraw_percent"></span> %</td>
                            </tr>

                        </table>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-11 m-2" style="border:1px solid #dedede"></div>
                </div>

            </div>
        </div>

        <div class="m-4"></div>

        <div class="row">
            <div class="col-sm-1 text-center">
                <a href="{{switchUrl('banks')}}"><i class="fa fa-2x fa-university blue"></i></a>
            </div>
            <div class="col-sm-11">
                <table style='width:100%'>
                    <tr>
                        <th>TYPE</th>
                        <th>BANK</th>
                        <th>AGENCY</th>
                        <th>ACCOUNT</th>
                        <th class="text-center">STATUS</th>
                    </tr>
                    <tr>
                        <td>Invoice</td>
                        <td><span id="span_bank_invoice"></span></td>
                        <td><span id="span_bank_invoice_agency"></span></td>
                        <td><span id="span_bank_invoice_account"></span></td>
                        <td class="text-center"><span id="span_bank_invoice_icon"></span></td>
                    </tr>
                    <tr>
                        <td>PIX</td>
                        <td><span id="span_bank_pix"></span></td>
                        <td><span id="span_bank_pix_agency"></span></td>
                        <td><span id="span_bank_pix_account"></span></td>
                        <td class="text-center"><span id="span_bank_pix_icon"></span></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="m-4"></div>


        <div class="row">
            <div class="col-sm-1 text-center">
                <a href="" target="_blank" id="link_file" ><i class="fa fa-2x fa-file-text blue"></i></a>
            </div>
            <div class="col-sm-11">
                <h5><b>Contract</b></h5>
            </div>
        </div>



      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@endsection
@section('js')
<script>

$( document ).ready(function() {

    $("#chkToggle2").change(function(){
        var check = $(this).prop('checked');

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        if(check === false){
            $.ajax({
                url:"{{switchUrl('clients/config')}}",
                method:"POST",
                dataType:"json",
                data:{status:"maintenance"},
                success:function(response){
                    Swal.fire(
                        response.title+'!',
                        response.message,
                        response.status
                    );
                },
                error:function(err){
                    console.log(err);
                }
            });
        }else{
            $.ajax({
                url:"{{switchUrl('clients/config')}}",
                method:"POST",
                dataType:"json",
                data:{status:"active"},
                success:function(response){
                    Swal.fire(
                        response.title+'!',
                        response.message,
                        response.status
                    );
                },
                error:function(err){
                    console.log(err);
                }
            });
        }
    });

    $('#month').change(function(){
        $('#search').submit();
    })

    $('#getClient').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');

        $.ajax({
            url:"{{switchUrl('merchants')}}"+'/'+id,
            method:"GET",
            dataType:"json",
            success:function(response){
                $('#span_client_name').text(response.client.name)
                $('#span_client_contact').text(response.client.contact)
                $('#span_client_email').text(response.client.email)
                $('#span_client_currency').text(response.client.currency)
                $('.span_client_currency').text(response.client.currency)
                $('#span_client_safe_boleto').text(response.client.days_safe_boleto)
                $('#span_client_safe_pix').text(response.client.days_safe_pix)

                $('#span_bank_invoice').text(response.bank_invoice.name)
                $('#span_bank_pix').text(response.bank_pix.name)

                var span_boleto_absolute = parseFloat(response.taxes.boleto_absolute).toLocaleString('pt-br', {minimumFractionDigits: 2});
                var span_boleto_cancel = parseFloat(response.taxes.boleto_cancel).toLocaleString('pt-br', {minimumFractionDigits: 2});
                var span_pix_absolute = parseFloat(response.taxes.pix_absolute).toLocaleString('pt-br', {minimumFractionDigits: 2});

                $('#span_boleto_absolute').text(span_boleto_absolute);
                $('#span_boleto_cancel').text(span_boleto_cancel);
                $('#span_boleto_percent').text(response.taxes.boleto_percent);
                $('#span_pix_absolute').text(span_pix_absolute);
                $('#span_pix_percent').text(response.taxes.pix_percent);
                $('#span_withdraw_absolute').text(response.taxes.withdraw_absolute);
                $('#span_withdraw_percent').text(response.taxes.withdraw_percent);

                var span_min_boleto = parseFloat(response.taxes.min_boleto).toLocaleString('pt-br', {minimumFractionDigits: 2});
                var span_min_pix = parseFloat(response.taxes.min_pix).toLocaleString('pt-br', {minimumFractionDigits: 2});
                var span_min_withdraw = parseFloat(response.taxes.min_withdraw).toLocaleString('pt-br', {minimumFractionDigits: 2});
                var span_max_boleto = parseFloat(response.taxes.max_boleto).toLocaleString('pt-br', {minimumFractionDigits: 2});
                var span_max_pix = parseFloat(response.taxes.max_pix).toLocaleString('pt-br', {minimumFractionDigits: 2});
                var span_max_withdraw = parseFloat(response.taxes.max_withdraw).toLocaleString('pt-br', {minimumFractionDigits: 2});


                $('#span_min_boleto').text(span_min_boleto);
                $('#span_max_boleto').text(span_max_boleto);

                $('#span_min_pix').text(span_min_pix);
                $('#span_max_pix').text(span_max_pix);

                $('#span_min_withdraw').text(span_min_withdraw);
                $('#span_max_withdraw').text(span_max_withdraw);

                $('#span_min_volume_pix_invoice').text(response.client.min_volume_pix_invoice)
                $('#span_min_fee_pix_invoice').text(response.client.min_fee_pix_invoice)

                var span_spread_deposit = parseFloat(response.taxes.spread_deposit).toLocaleString('pt-br', {minimumFractionDigits: 2});
                var span_spread_withdraw = parseFloat(response.taxes.spread_withdraw).toLocaleString('pt-br', {minimumFractionDigits: 2});
                var span_spread_usd_eur = parseFloat(response.taxes.usd_eur_convertion).toLocaleString('pt-br', {minimumFractionDigits: 2});

                $('#span_spread_deposit').text(span_spread_deposit);
                $('#span_spread_withdraw').text(span_spread_withdraw);
                $('#span_spread_usd_eur').text(span_spread_usd_eur);

                var span_b1_min = parseFloat(response.keys.minamount_boletofirst).toLocaleString('pt-br', {minimumFractionDigits: 2});

                $('#span_b1_min').text(span_b1_min);

                var span_credit_card_rule = parseFloat(response.taxes.base_fee_card).toLocaleString('pt-br', {minimumFractionDigits: 2});
                var span_credit_card_fee_aply = parseFloat(response.taxes.base_fee_card_apply).toLocaleString('pt-br', {minimumFractionDigits: 2});
                var span_other_methods_rule = parseFloat(response.taxes.base_fee_others).toLocaleString('pt-br', {minimumFractionDigits: 2});
                var span_other_methods_fee_aply = parseFloat(response.taxes.base_fee_others).toLocaleString('pt-br', {minimumFractionDigits: 2});

                $('#span_credit_card_rule').text(span_credit_card_rule);
                $('#span_credit_card_fee_aply').text(span_credit_card_fee_aply);
                $('#span_other_methods_rule').text(span_other_methods_rule);
                $('#span_other_methods_fee_aply').text(span_other_methods_fee_aply);

                // // // // // // // // // //

                var url = 'https://xdash.FastPayments.com/clients/';
                var asset = 'https://files.fastpayments.com.br/contract/';

                $("#link_edit").attr('href',url+'/'+response.client.id+'/edit')

                if(response.client.contract == null){
                    $("#link_file").attr('href','/#!')
                }else{
                    $("#link_file").attr('href',asset+response.client.contract)
                }

                if(response.client.bank_name != null){
                    $('#bank_name').text(response.client.bank_name)
                }

                if(response.client.document_holder != null){
                    $('#account_holder').text(response.client.document_holder)
                }

                if(response.client.address != null){
                    $('#bank_address').text(response.client.address)

                }

                if(response.client.bank_name != null){
                    $('#account_number').text(response.client.agency+' - '+response.client.number_account)
                }


                //data banks
                if(response.bank_invoice.agency != null){
                    $('#span_bank_invoice_agency').text(response.bank_invoice.agency)
                }else{
                    $('#span_bank_invoice_agency').text('---')
                }
                if(response.bank_invoice.account != null){
                    $('#span_bank_invoice_account').text(response.bank_invoice.account)
                }else{
                    $('#span_bank_invoice_account').text('---')
                }

                //
                if(response.bank_pix.agency != null){
                    $('#span_bank_pix_agency').text(response.bank_pix.agency)
                }else{
                    $('#span_bank_pix_agency').text('---')
                }
                if(response.bank_pix.account != null){
                    $('#span_bank_pix_account').text(response.bank_pix.account)
                }else{
                    $('#span_bank_pix_account').text('---')
                }


                //
                if(response.bank_invoice.status == null || response.bank_invoice.status == 'ativo'){
                    $('#span_bank_invoice_icon').html('<i class="fa fa-check text-success"></i>')
                }else{
                    $('#span_bank_invoice_icon').html('freeze')
                }

                //
                if(response.bank_pix.status == null || response.bank_pix.status == 'ativo'){
                    $('#span_bank_pix_icon').html('<i class="fa fa-check text-success"></i>')
                }else{
                    $('#span_bank_pix_icon').html('freeze')
                }

            },
            error:function(err){
                console.log(err);
            }

        });




    })
});

</script>
@endsection
