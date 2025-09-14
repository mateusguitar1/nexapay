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
table.dataTable.fixedHeader-floating, table.dataTable.fixedHeader-locked{
    background-color: black !important;
    color: #fff !important;
}
div.dataTables_wrapper div.dataTables_processing {
    position: absolute !important;
    top: 50% !important;
    left: 50% !important;
    width: 200px !important;
    margin-left: -100px !important;
    margin-top: 60px !important;
    margin-bottom: 60px !important;
    text-align: center !important;
}
.dt-buttons{
    position: absolute !important;
    right: 16px !important;
}
h3{
    font-size: 1.3rem !important;
}
</style>
@endsection

@section('content')

    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="@if(Auth::user()->level == 'master') col-md-12 @else col-md-5 @endif col-sm-12">
            <h1 class="m-0">Dashboard</h1>
            <br/>
          </div><!-- /.col -->
          <div class="@if(Auth::user()->level == 'master') col-md-12 @else col-md-7 @endif col-sm-12">
                <div class="row">
                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 mb-4">
                        <div class="card">
                            <div class="card-header pb-0">
                                <div class="row">
                                    <div class="col-lg-12 col-12">
                                        <h6>GERAR PIX QRCODE</h6>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body px-0 pb-2">
                                <form id="formcreatePixAccount" method="post">
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6 margin15 mb-4">
                                                <h6>Valor Depósito (BRL)</h6>
                                                <div class="input-group">
                                                    <input required="" name="amount_solicitation" type="text" value="" class="form-control money_pix width100 text-right amount_solicitation_deposit_pix" style="text-align:right;" placeholder="0.00" maxlength="22" onfocus="focused(this)" onfocusout="defocused(this)">
                                                </div>
                                            </div>

                                            <div class="col-md-6 margin15">
                                                <h6>Descrição</h6>
                                                <div class="input-group">
                                                    <input required="" name="description" type="text" value="" class="form-control width100 text-right" style="text-align:right;" placeholder="Description">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer hidden_after_account">
                                        <button type="submit" class="btn btn-success pull-right" style="width:100%;" data-toggle="modal" data-target="#showPix">GENERATE <i class="fa fa-qrcode" aria-hidden="true"></i></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 mb-4">
                        <div class="card">
                            <div class="card-header pb-0">
                                <div class="row">
                                    <div class="col-lg-12 col-12">
                                        <h6>TRANSFERENCIA POR PIX</h6>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body px-0 pb-2">
                                <form id="formSendPixAccount" method="post">
                                    <div class="modal-body">
                                        <h6>PIX POR CHAVE</h6>

                                        <div class="row pb-2">
                                            <div class="col-md-4 mb-3">
                                                <select name="type_key" id="type_key" class="form-control type_key">
                                                    <option value="CPF">CPF</option>
                                                    <option value="CNPJ">CNPJ</option>
                                                    <option value="PHONE">TELEFONE</option>
                                                    <option value="EMAIL">EMAIL</option>
                                                    <option value="EVP">ALEATÓRIA</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <input type="text" id="pix_key" placeholder="CHAVE PIX" name="pix_key" class="form-control pix_key" style="width:100%;" onfocus="focused(this)" onfocusout="defocused(this)">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <div class="input-group">
                                                    <input name="amount_solicitation_send_pix" type="text" placeholder="Amount BRL" class="form-control money_pix width100 text-left amount_solicitation_send_pix" style="text-align:right;" maxlength="22" onfocus="focused(this)" onfocusout="defocused(this)">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-success pull-right" style="width:100%;">ENVIAR PIX <i class="fa fa-dollar-sign"></i></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{switchUrl('/dashboard')}}" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    <div class="row">
                        <div class="@if(Auth::user()->level == 'master') col-md-2 @else col-md-4 @endif col-sm-12">
                            <label>Client</label>
                            <select name="client_id" id="client" class='form-control'>
                                @if(auth()->user()->level == "master") <option value="all">All</option> @endif
                                @foreach($data['clients'] as $client)
                                    <option value="{{ $client->id }}" @if(isset($data['request']['client_id'])) @if($data['request']['client_id'] == $client->id) selected @endif @endif >{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="@if(Auth::user()->level == 'master') col-md-2 @else col-md-2 @endif col-sm-12">
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
                        <div class="@if(Auth::user()->level == 'master') col-md-2 @else col-md-2 @endif col-sm-12">
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
                        @if(Auth::user()->level == 'master')
                            <div class="col-md-2 col-sm-12">
                                <label>Bank</label>
                                <div class="input-group">
                                    <select title='BANKS' name="bank_id" id="bank_id" class='form-control'>
                                        <option value="all">All</option>
                                        @foreach($data['banks'] as $bank)
                                            <option @if(isset($data['request']['bank_id'])) @if($data['request']['bank_id'] == $bank->id) selected @endif @endif value="{{$bank->id}}">{{$bank->name}} - {{$bank->agency}} - {{$bank->account}} - {{$bank->holder}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif
                        <div class="col-md-2 col-sm-12">
                            <label>CPF/NAME</label>
                            <div class="input-group">
                                <input type="text" class="form-control search" name="search" autocomplete="off" @if(isset($data['request'])) value="{{ $data['request']['search'] }}" @endif/>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-12">
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
          <!-- Small boxes (Stat box) -->
          <div class="row">
            <div class="col-md-4 col-sm-12">
              <!-- small box -->
              <div class="small-box bg-success">
                <div class="inner">
                  <h3>R$ {{ number_format($data['amount_cashin'],"2",",",".") }} <sup style="font-size: 12px"><small>QTD {{ $data['quantity_cashin'] }}</small></sup></h3>
                  <div class="text-white" style="margin-top:-5px;font-size: 12px;">Fee: R$ {{ number_format($data['cashout_fee_deposit'],"2",",",".") }}</div>
                  <p class="text-white" style="margin-bottom:4px;">CASH-IN</p>
                </div>
                <div class="icon">
                  <i class="ion ion-stats-bars"></i>
                </div>
              </div>
            </div>
            <!-- ./col -->
            <div class="col-md-4 col-sm-12">
              <!-- small box -->
              <div class="small-box bg-orange">
                <div class="inner">
                  <h3 class="text-white">R$ {{ number_format($data['amount_cashout'],"2",",",".") }} <sup style="font-size: 12px"><small>QTD {{ $data['quantity_cashout'] }}</small></sup></h3>
                  {{-- @if(Auth::user()->level == 'master') --}}
                  <div class="text-white" style="margin-top:-5px;font-size: 12px;">Fee: R$ {{ number_format($data['cashout_fee_withdraw'],"2",",",".") }}</div>
                  <p class="text-white" style="margin-bottom:4px;">CASH-OUT</p>
                  {{-- @else
                    <p class="text-white">CASH-OUT</p>
                  @endif --}}
                </div>
                <div class="icon">
                  <i class="ion ion-stats-bars"></i>
                </div>
              </div>
            </div>
            <!-- ./col -->
            {{-- <div class="col-md-4 col-sm-12">
              <!-- small box -->
              <div class="small-box bg-info">
                <div class="inner">
                  <h3>R$ {{ $data['amount_fee'] }}</h3>

                  <p>FEES</p>
                </div>
                <div class="icon">
                  <i class="ion ion-stats-bars"></i>
                </div>
              </div>
            </div> --}}

            <div class="col-md-4 col-sm-12">
                <!-- small box -->
                <div class="small-box bg-gray">
                  <div class="inner">
                    <h3>BALANCE: R$ {{ number_format(($data['balance']),"2",",",".") }}</h3>

                    <div style="font-size:13px;">Available for Withdraw: {{ number_format($data['av_today'],"2",",",".") }}</div>
                    <div style="font-size:13px;">To be Released: {{ number_format($data['tobe_released'],"2",",",".") }}</div>
                  </div>
                  <div class="icon">
                    <i class="ion ion-stats-bars"></i>
                  </div>
                </div>
              </div>
          </div>
          <!-- /.row -->

          <div class="card">
            <div class="card-header">
              <h3 class="card-title">EXTRACT</h3>
            </div>
            <!-- /.card-header -->
            {{-- <div class="card-body">
              <div style="border:1px solid #00eb5e;padding:15px;background-color:#d7fce6;">
                <table>
                  <tr>
                    <td>
                      To be Released:<br/>
                      <b>R$ {{ $data['tobe_released'] }}</b>
                    </td>
                    <td width="20%">&nbsp;</td>
                    <td>
                      Available Today:<br/>
                      <b>R$ {{ $data['av_today'] }}</b>
                    </td>
                  </tr>
                </table>
              </div>
            </div> --}}

            <div class="card-body p-0">
              <table id="extract" class="table table-striped dt-responsive" style="width:100%;">
                <thead>
                  <tr>
                    <th>DATE</th>
                    <th class="text-center">NEXA ID</th>
                    <th class="text-center">CLIENT</th>
                    <th class="text-center">ORDER ID / USER NAME</th>
                    <th class="text-center">USER ID</th>
                    {{-- @if(Auth::user()->level == 'master')<th class="text-center">BANK</th>@endif --}}
                    <th class="text-center">DESCRIPTION</th>
                    <th class="text-right">AMOUNT</th>
                  </tr>
                </thead>
                <tbody>
                    {{-- @foreach($data['all_registers'] as $register)
                        <tr>
                            <td>{{ date("d/m/Y H:i:s",strtotime($register->created_at)) }}</td>
                            <td class="text-center">{{ $register->transaction->id }}</td>
                            <td class="text-center">{{ $register->client->name }}</td>
                            <td class="text-center">{{ $register->order_id }}@if($register->transaction["provider_reference"] != "") <br/> {{$register->transaction["provider_reference"]}} @endif</td>
                            <td class="text-center">{{ $register->user_id }}</td>
                            @if(Auth::user()->level == 'master')<td class="text-center">{{ $register->bank->name }}</td>@endif
                            <td class="text-center">{{ $register->description_text }}</td>
                            <td class="text-right @if($register->type_transaction_extract == "cash-out") text-danger @endif">{{ number_format($register->final_amount,"2",",",".") }}</td>
                        </tr>
                    @endforeach --}}
                </tbody>
              </table>
            </div>
            <!-- /.card-body -->
          </div>
        </div>
        <!-- /.card-body -->
      </div>
    </section>
    <!-- /.content -->

<!-- Modal -->
<div class="modal fade" id="showPix" tabindex="-1" role="dialog" aria-labelledby="showPixLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4>PAGAMENTO POR PIX</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="show_request_account"></div>
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
<script src="http://igorescobar.github.io/jQuery-Mask-Plugin/js/jquery.mask.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.4/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.4/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.jsdelivr.net/gh/bpampuch/pdfmake@0.1.24/build/pdfmake.min.js"></script>

<script type="text/javascript">
    function time()
    {
        today = new Date();
        day = today.getDay();
        mon = today.getMonth();
        yer = today.getFullYear();
        hou = today.getHours();
        min = today.getMinutes();
        sec = today.getSeconds();

        if(day < 10){
            day = "0"+day;
        }

        return day+"-"+mon+"-"+yer+" "+hou+"-"+min+"-"+sec;
    }

    $(document).ready(function(){
        $('#datepicker_start').datepicker({
            format: 'dd/mm/yyyy',
        });
        $('#datepicker_end').datepicker({
            format: 'dd/mm/yyyy',
        });

        $('.datepicker_start').mask('00/00/0000');
        $('.datepicker_end').mask('00/00/0000');

        $('#formcreatePixAccount').submit(function(e){
            e.preventDefault();

            $(".show_request_account").html("<br/><div class='text-center'><i class='material-icons fa-spin fa-3x'>refresh</i></div>");

            var amount_solicitation = $(".amount_solicitation_deposit_pix").val();

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url:"{{ switchUrl('merchants/charge') }}",
                method:"POST",
                data:{
                    amount : amount_solicitation
                },
                success:function(response){
                    console.log(response);

                    if(response.link_qr){
                        $(".hidden_after_account").css("display","none");

                        $(".show_request_account").html("");
                        $(".show_request_account").append("<br/>");
                        $(".show_request_account").append("<center><p>Após pagar o QrCode PIX, atulize a página para visualizar seu saldo atualizado!</p></center>");
                        $(".show_request_account").append("<center><img src='https://image-charts.com/chart?chs=250x250&cht=qr&chl="+response.content_qr+"' style='width:100%;' alt='QrCode' /></center>");
                        $(".show_request_account").append("<br/>");
                        $(".show_request_account").append("<div class='input-group has-validation'><input type='text' id='copypaste' value='"+response.content_qr+"' class='form-control'><div class='input-group-text' style='cursor: pointer;background: #42a7d2;padding: 6px 15px;color: #fff;' onclick='copyToClipboard()'>COPY</div></div>");
                        $(".show_request_account").append("<br/>");
                    }else{
                        $(".message_toast_error").html(response.content)
                        $(".dangerToast").toast('show');
                    }

                },
                error:function(err){
                    console.log(err);
                }
            });

        });

    })
</script>
@if(Auth::user()->level == 'master')
    <script type="text/javascript">
        $(document).ready(function(){

            var date_start = $(".datepicker_start").val();
            var date_end = $(".datepicker_end").val();
            var bank_id = $("#bank_id").val();
            var search = $(".search").val();
            var client_id = $("#client").val();

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            var table = $('#extract')
                .on('preXhr.dt', function () {
                    $('#extract tbody').empty();
                })
                .DataTable({
                searching: false,
                responsive: true,
                processing: true,
                language: {
                    processing: '<i class="fa fa-sync fa-spin fa-2x fa-fw"></i>'
                },
                serverSide: true,
                lengthChange: true,
                lengthMenu: [ [10, 25, 50, 100, 300, 500, -1], [10, 25, 50, 100, 300, 500, "All"] ],
                serverMethod: 'post',
                ajax: {
                    url:'{{switchUrl('dashboard/getDash')}}',
                    data: {
                        date_start,
                        date_end,
                        bank_id,
                        search,
                        client_id
                    }
                },
                columns: [
                    { data: 'date' },
                    { data: 'fast_id' },
                    { data: 'client' },
                    { data: 'order_id' },
                    { data: 'user_id' },
                    // { data: 'bank' },
                    { data: 'description' },
                    {
                        data: 'amount',
                        render: function(data,type,row){
                            var valor = parseFloat(data.replace(".","").replace(",","."));
                            if(valor <= 0){
                                return "<span style='color:#dc3545;'>"+data+"</span>"
                            }else{
                                return data;
                            }
                        }
                    }
                ],
                dom: 'Blfrtip',
                buttons: [
                {
                    extend: 'excelHtml5',
                    title: 'FastPayments Transactions '+time(),
                    exportOptions: {
                        orthogonal: 'export',
                        format: {
                            body: function(data, row, column, node) {
                                return column === 7 ? `R$ ${data.replace("<span style='color:#dc3545;'>","").replace("</span>","").toString()}` : data;
                            }

                        }
                    },
                }
            ]
            });

            new $.fn.dataTable.FixedHeader( table );
        });
    </script>
@else
    <script type="text/javascript">
        $(document).ready(function(){
            var date_start = $(".datepicker_start").val();
            var date_end = $(".datepicker_end").val();
            var bank_id = $("#bank_id").val();
            var client_id = $("#client").val();
            var search = $(".search").val();

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            var table = $('#extract')
                .on('preXhr.dt', function () {
                    $('#extract tbody').empty();
                })
                .DataTable({
                searching: false,
                responsive: true,
                processing: true,
                language: {
                    processing: '<i class="fa fa-sync fa-spin fa-2x fa-fw"></i>'
                },
                serverSide: true,
                lengthChange: true,
                lengthMenu: [ [10, 25, 50, 100, 300, 500, -1], [10, 25, 50, 100, 300, 500, "All"] ],
                serverMethod: 'post',
                ajax: {
                    url:'{{switchUrl('dashboard/getDash')}}',
                    data: {
                        date_start,
                        date_end,
                        bank_id,
                        client_id,
                        search
                    }
                },
                columns: [
                    { data: 'date' },
                    { data: 'fast_id' },
                    { data: 'client' },
                    { data: 'order_id' },
                    { data: 'user_id' },
                    { data: 'description' },
                    {
                        data: 'amount',
                        render: function(data,type,row){
                            var valor = parseFloat(data.replace(".","").replace(",","."));
                            if(valor <= 0){
                                return "<span style='color:#dc3545;'>"+data+"</span>"
                            }else{
                                return data;
                            }
                        }
                    },
                ],
                dom: 'Blfrtip',
                buttons: [
                {
                    extend: 'excelHtml5',
                    title: 'FastPayments Transactions '+time(),
                    exportOptions: {
                        orthogonal: 'export',
                        format: {
                            body: function(data, row, column, node) {
                                return column === 6 ? `R$ ${data.replace("<span style='color:#dc3545;'>","").replace("</span>","").toString()}` : data;
                            }
                        }
                    },
                }
            ]
            });

            new $.fn.dataTable.FixedHeader( table );
        });
    </script>
@endif

@endsection
