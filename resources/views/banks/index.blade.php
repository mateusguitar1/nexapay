@extends('layouts.appdash')

@section('style')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/8.11.8/sweetalert2.css">
<meta name="csrf-token" content="{{ csrf_token() }}" />

@endsection

<style>
    .iconSize{
        font-size: 16pt !important;
    }
</style>

@section('content')
<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">

        <div class="row mb-2">
            <div class="col-sm-10">
                <h1 class="m-0">Bank Accounts</h1>
            </div><!-- /.col -->
            <div class="col-sm-2">
                <a class='btn btn-primary' href="{{ switchUrl('banks/create') }}" style="padding:7px 10px;width:100%;">
                    <i class="fas fa-university"></i> New Bank
                </a>
            </div><!-- /.col -->
        </div><!-- /.row -->

        <div class="row " style='margin:0px'>
            <div class="col-md-12" style='padding:0px'>
                <div class="card">

                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="active-tab" data-toggle="tab" href="#active" role="tab" aria-controls="active" aria-selected="true">Active</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="inactive-tab" data-toggle="tab" href="#inactive" role="tab" aria-controls="inactive" aria-selected="false">Inactive</a>
                            </li>
                        </ul>
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="active" role="tabpanel" aria-labelledby="active-tab">

                                <table class="table table-striped table-bordered" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:-10px;font-size:14px;">
                                    <thead>
                                        <tr>
                                            <th width="9%">CODE</th>
                                            <th width="16.5%">BANK</th>
                                            <th width="25%">HOLDER</th>
                                            <th width="16%">CNPJ</th>
                                            <th width="6%">AG</th>
                                            <th width="9%">ACCOUNT</th>
                                            <th width="18.5%">ACTIONS</th>
                                        </tr>
                                    </thead>
                                </table>
                                <div class="row">
                                    <table class="table" width="100%" cellspacing="0" cellpadding="0" border="0">
                                        <tr>
                                            <td style="border:none;">
                                                <div class="row">
                                                    @foreach ($data['banks'] as $key => $bank)
                                                        @if($bank->status == "ativo")
                                                            <div class="col-md-12">
                                                                <div id="card-91547">
                                                                    <div class="card">
                                                                        <div class="card-header">
                                                                            <div class="row">
                                                                                <div class="col-md-1">{!! getflag($bank->code,$bank->name) !!} <br> {{ $bank->code }}</div>
                                                                                <div class="col-md-2">{{ $bank->name }}</div>
                                                                                <div class="col-md-3">{{ $bank->holder }}</div>
                                                                                <div class="col-md-2">{{ $bank->document }}</div>
                                                                                <div class="col-md-1">{{ $bank->agency }}</div>
                                                                                <div class="col-md-1">{{ $bank->account }}</div>
                                                                                <div class="col-md-2">
                                                                                    <div class="row">
                                                                                        <div class="col-lg-2" style="padding-bottom: 10px; text-align: center !important;">
                                                                                            <a class="card-link collapsed" data-toggle="collapse" data-parent="#card-91547" href="#card-element-{!! $key !!}"><i class="fa fa-info-circle iconSize"></i></a>
                                                                                        </div>
                                                                                        <div class="col-lg-2" style="padding-bottom: 10px; text-align: center !important;">
                                                                                            <a class="text-warning edit-clients" href="#" data-client_id="{{ $bank->id }}" data-toggle="modal" data-target="#modalEditClientsData" style="cursor:pointer;"><i class="fa fa-user iconSize"></i></a>
                                                                                        </div>
                                                                                        <div class="col-lg-2" style="padding-bottom: 10px; text-align: center !important;">
                                                                                            <?php /*
                                                                                            <a class="text-info edit-clients" data-bank="{{ $bank->id }}" data-toggle="modal" data-target="#modalEditClientsWithBank" style="cursor: pointer;"><i class="fa fa-users iconSize"></i><span class="badge badge-pill badge-primary bedge-abs">{!! $bank->total !!}</span></a>
                                                                                            */ ?>

                                                                                            <a class="<?php if($bank->total > 0){ ?>text-info<?php } ?> edit-clients" data-bank="{{ $bank->id }}" data-bank_info="{{ $bank->name }} AG: {{$bank->agency}} CC: {{$bank->account}} - {{substr($bank->holder,0,10)}}" data-toggle="modal" data-target="#modalEditClients" style="cursor:pointer;">
                                                                                                <i class="fa fa-users iconSize"></i>
                                                                                                <span class="badge badge-pill badge-primary bedge-abs">{!! $bank->total !!}</span>
                                                                                            </a>


                                                                                        </div>
                                                                                        <div class="col-lg-2" style="padding-bottom: 10px; text-align: center !important;">
                                                                                            <a class="{{ $bank->status == 'ativo' ? 'text-danger' : 'text-secondary' }} freeze" data-id="{{ $bank->id }}" data-status="{{ $bank->status == 'ativo' ? 'freeze' : 'unfreeze' }}" data-name="{{ $bank->name }}" style="cursor: pointer !important"><i class="fa fa-{{ $bank->status == 'ativo' ? 'user-times' : 'user-plus' }} iconSize"></i></a>
                                                                                        </div>
                                                                                        <div class="col-lg-2" style="padding-bottom: 10px; text-align: center !important;">
                                                                                            <a class="text-success" href="{{switchUrl('banks/exportPayin/'.$bank->id.'')}}" target="_blank"><i class="fa fa-download iconSize"></i></a>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div id="card-element-{!! $key !!}" class="collapse">
                                                                            <div class="card-body">
                                                                                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                                                                                    <thead>
                                                                                        <tr>
                                                                                            <td style="width: 25% !important">BOLETO</td>
                                                                                            <td style="width: 25% !important">PIX</td>
                                                                                        </tr>
                                                                                    </thead>
                                                                                    <tbody>
                                                                                        <tr>
                                                                                            <td>
                                                                                                @include('banks.layout_banks',['data' => $bank->clients_invoice])
                                                                                            </td>
                                                                                            <td>
                                                                                                @include('banks.layout_banks',['data' => $bank->clients_pix])
                                                                                            </td>
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </div>

                            </div>
                            <div class="tab-pane fade" id="inactive" role="tabpanel" aria-labelledby="inactive-tab">

                                <table class="table table-striped table-bordered" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:-10px;font-size:14px;">
                                    <thead>
                                        <tr>
                                            <th width="9%">CODE</th>
                                            <th width="16.5%">BANK</th>
                                            <th width="25%">HOLDER</th>
                                            <th width="16%">CNPJ</th>
                                            <th width="6%">AG</th>
                                            <th width="9%">ACCOUNT</th>
                                            <th width="18.5%">ACTIONS</th>
                                        </tr>
                                    </thead>
                                </table>
                                <div class="row">
                                    <table class="table" width="100%" cellspacing="0" cellpadding="0" border="0">
                                        <tr>
                                            <td style="border:none;">
                                                <div class="row">
                                                    @foreach ($data['banks'] as $key => $bank)
                                                        @if($bank->status == "inativo")
                                                            <div class="col-md-12">
                                                                <div id="card-91547">
                                                                    <div class="card">
                                                                        <div class="card-header">
                                                                            <div class="row">
                                                                                <div class="col-md-1">{!! getflag($bank->code) !!} <br> {{ $bank->code }}</div>
                                                                                <div class="col-md-2">{{ $bank->name }}</div>
                                                                                <div class="col-md-3">{{ $bank->holder }}</div>
                                                                                <div class="col-md-2">{{ $bank->document }}</div>
                                                                                <div class="col-md-1">{{ $bank->agency }}</div>
                                                                                <div class="col-md-1">{{ $bank->account }}</div>
                                                                                <div class="col-md-2">
                                                                                    <div class="row">
                                                                                        <div class="col-lg-2" style="padding-bottom: 10px; text-align: center !important;">
                                                                                            <a class="card-link collapsed" data-toggle="collapse" data-parent="#card-91547" href="#card-element-{!! $key !!}"><i class="fa fa-info-circle iconSize"></i></a>
                                                                                        </div>
                                                                                        <div class="col-lg-2" style="padding-bottom: 10px; text-align: center !important;">
                                                                                            <a class="text-warning edit-clients" href="#" data-client_id="{{ $bank->id }}" data-toggle="modal" data-target="#modalEditClientsData" style="cursor:pointer;"><i class="fa fa-user iconSize"></i></a>
                                                                                        </div>
                                                                                        <div class="col-lg-2" style="padding-bottom: 10px; text-align: center !important;">
                                                                                            <?php /*
                                                                                            <a class="text-info edit-clients" data-bank="{{ $bank->id }}" data-toggle="modal" data-target="#modalEditClientsWithBank" style="cursor: pointer;"><i class="fa fa-users iconSize"></i><span class="badge badge-pill badge-primary bedge-abs">{!! $bank->total !!}</span></a>
                                                                                            */ ?>

                                                                                            <a class="<?php if($bank->total > 0){ ?>text-info<?php } ?> edit-clients" data-bank="{{ $bank->id }}" data-bank_info="{{ $bank->name }} AG: {{$bank->agency}} CC: {{$bank->account}} - {{substr($bank->holder,0,10)}}" data-toggle="modal" data-target="#modalEditClients" style="cursor:pointer;">
                                                                                                <i class="fa fa-users iconSize"></i>
                                                                                                <span class="badge badge-pill badge-primary bedge-abs">{!! $bank->total !!}</span>
                                                                                            </a>


                                                                                        </div>
                                                                                        <div class="col-lg-2" style="padding-bottom: 10px; text-align: center !important;">
                                                                                            <a class="{{ $bank->status == 'ativo' ? 'text-danger' : 'text-secondary' }} freeze" data-id="{{ $bank->id }}" data-status="{{ $bank->status == 'ativo' ? 'freeze' : 'unfreeze' }}" data-name="{{ $bank->name }}" style="cursor: pointer !important"><i class="fa fa-{{ $bank->status == 'ativo' ? 'user-times' : 'user-plus' }} iconSize"></i></a>
                                                                                        </div>
                                                                                        <div class="col-lg-2" style="padding-bottom: 10px; text-align: center !important;">
                                                                                            <a class="text-success" href="{{switchUrl('banks/exportPayin/'.$bank->id.'')}}" target="_blank"><i class="fa fa-download iconSize"></i></a>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div id="card-element-{!! $key !!}" class="collapse">
                                                                            <div class="card-body">
                                                                                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                                                                                    <thead>
                                                                                        <tr>
                                                                                            <td style="width: 25% !important">BOLETO</td>
                                                                                            <td style="width: 25% !important">PIX</td>
                                                                                        </tr>
                                                                                    </thead>
                                                                                    <tbody>
                                                                                        <tr>
                                                                                            <td>
                                                                                                @include('banks.layout_banks',['data' => $bank->clients_invoice])
                                                                                            </td>
                                                                                            <td>
                                                                                                @include('banks.layout_banks',['data' => $bank->clients_pix])
                                                                                            </td>
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>

                <!-- Modal -->
                <div class="modal fade" id="modalEditClientsWithBank" tabindex="-1" role="dialog" aria-labelledby="modalLable"
                aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalLable">Customers using the bank <span>#bankname</span> currently</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="modalEditClientsSendWithBank">
                            <div class="modal-body">
                                <div class="row" style="line-height:1">
                                    <div class="col-sm-8">Clients</div>
                                    <div class="col-sm-4">Method</div>

                                    <div class="col-sm-12">&nbsp;</div>

                                    @foreach( $data['clients'] as $key => $clients )
                                    @php
                                    $cor = ( $key % 2 === 0 ? '#fff' : '#cecece' );
                                    @endphp
                                    <div class="col-sm-12" style="padding-top: 5px !important; padding-bottom: 5px !important">
                                        <div class="row" style="padding: 5px; background-color:{!! $cor !!} !important;">
                                            <div class="col-sm-8" style="padding-top: 10px !important">
                                                {!! $clients['name'] !!}
                                            </div>
                                            <div class="col-sm-4">
                                                <select name="method[]" id="select" class='form-control'>
                                                    <option value='none'>...</option>
                                                    {{-- <option value='shop_santander'>Shop Santander</option>
                                                    <option value='shop_bb'>Shop BB</option>
                                                    <option value='shop_itau'>Shop Itau</option>
                                                    <option value='shop_bradesco'>Shop Bradesco</option>
                                                    <option value='shop_caixa'>Shop Caixa Econômica</option> --}}
                                                    <option value='invoice'>Invoice</option>
                                                    {{-- <option value='credit_card'>Credit Card</option> --}}
                                                    <option value='bank_pix'>PIX</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" id='client_id[]' name="client_id[]" value="{!! $clients->id !!}">
                                    @endforeach
                                    <input type="hidden" class='bank_id' name="bank_id">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalEditClients" tabindex="-1" role="dialog" aria-labelledby="modalLable"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLable"><span class="data_bank"></span><br/>Use this bank in...</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editClients" style="margin:0;">
                    <div class="modal-body">
                        <div class="row">
                            <?php
                                $countt = 1;
                                foreach($data['clients'] as $client){
                            ?>
                            <div class="col-sm-6 mb-1">
                                <?php if($countt == 1){ ?><label for="select">Merchant</label><?php } ?>
                                <select name="clients[<?=$countt;?>]" id="select" class='form-control'>
                                    <option value='<?=$client->id;?>'><?=$client->name;?></option>
                                </select>
                            </div>
                            <div class="col-sm-6 mb-1">
                            <?php if($countt == 1){ ?><label for="select">Method</label><?php } ?>
                                <select name="method[<?=$countt;?>]" id="select" class='form-control'>
                                    <option value='none'>No Selected</option>
                                    {{-- <option value='shop_santander'>Shop Santander</option>
                                    <option value='shop_bb'>Shop BB</option>
                                    <option value='shop_itau'>Shop Itau</option>
                                    <option value='shop_bradesco'>Shop Bradesco</option>
                                    <option value='shop_caixa'>Shop Caixa Econômica</option> --}}
                                    <option value='invoice'>Invoice</option>
                                    {{-- <option value='credit_card'>Credit Card</option> --}}
                                    <option value='bank_pix'>PIX</option>
                                </select>
                            </div>
                            <?php
                                $countt++;}
                            ?>
                        </div>
                        <input type="hidden" class='bank_id' name="bank_id">
                    </div>
                    <div class="modal-footer">
                        <div style="position:absolute;left:10px;"><button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times"></i> CLOSE</button></div>
                        <div style=""><button type="submit" class="btn btn-success"><i class="fa fa-save"></i> SAVE</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditClientsData" tabindex="-1" role="dialog" aria-labelledby="modalLable" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLable">Edit data client</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                @include('banks.forminternal')
            </div>
        </div>
    </div>


@endsection
@section('js')
<!-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@9"></script> -->

<script type="text/javascript">

    $(document).ready(function() {

        $('#modalEditClientsData').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget)
            var recipient = button.data('client_id')
            var modal = $(this)

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url:"{{switchUrl('banks/getBank')}}",
                method:"POST",
                dataType:"json",
                data:{id:recipient},
                success:function(response){

                    $('#editClientsData input[name="id"]').val(response.id);
                    $('#editClientsData input[name="code"]').val(response.code);
                    $('#editClientsData input[name="name"]').val(response.name);
                    $('#editClientsData input[name="holder"]').val(response.holder);
                    $('#editClientsData input[name="type_account"]').val(response.type_account);
                    $('#editClientsData input[name="agency"]').val(response.agency);
                    $('#editClientsData input[name="account"]').val(response.account);
                    $('#editClientsData input[name="document"]').val(response.document);
                    $('#editClientsData input[name="status"]').val(response.status);
                    $('#editClientsData input[name="prefix"]').val(response.prefix);
                    $('#editClientsData input[name="type_key_pix"]').val(response.type_key_pix);
                    $('#editClientsData input[name="pix_key_withdraw_fee"]').val(response.pix_key_withdraw_fee);

                },
                error:function(err){
                    console.log(err);
                }
            });
        })

        $('.edit-clients').click(function() {
            var bankID = $(this).attr('data-bank');
            var bankInfo = $(this).attr('data-bank_info');
            $('.bank_id').val(bankID);
            $(".data_bank").html(bankInfo);
        });

        $('#editClients').submit(function(e){
            e.preventDefault()
            var data = $(this).serialize()

            Swal.fire({
                title: 'Are you sure?',
                text: "You want update clients",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#aaa',
                confirmButtonText: 'Yes, update it!'
            }).then((result) => {
                if (result.value) {

                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.ajax({
                        url:"{{switchUrl('banks/updateClients')}}",
                        method:"POST",
                        dataType:"json",
                        data:data,
                        success:function(response){
                            console.log(response)
                            if(response.status == "success"){
                                Swal.fire(
                                    response.status + '!',
                                    response.message,
                                    'success'
                                );
                                $('#modalEditClientsData').modal('hide');
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




        $('#modalEditClientsSendWithBank').submit(function(e){
            e.preventDefault()
            var data = $(this).serialize()

            Swal.fire({
                title: 'Are you sure?',
                text: "You want update clients",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#aaa',
                confirmButtonText: 'Yes, update it!'
            }).then((result) => {
                if (result.value) {

                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.ajax({
                        url:"{{switchUrl('banks/updateClients')}}",
                        method:"post",
                        dataType:"json",
                        data:data,
                        success:function(response){
                                // console.log(response)

                                Swal.fire(
                                    response.status + '!',
                                    response.message,
                                    'success'
                                    );
                                $('#modalEditClientsData').modal('hide');
                                location.reload();

                            },
                            error:function(err){
                                console.log(err);
                            }

                        });
                }
            })
        });

        $('#editClientsData').submit(function(e) {
            e.preventDefault()
            var data = $(this).serialize()

            Swal.fire({
                title: 'Are you sure?',
                text: "You want update clients",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#aaa',
                confirmButtonText: 'Yes, update it!'
            }).then((result) => {
                if (result.value) {

                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.ajax({
                        url: "{{switchUrl('banks/updateClientsData')}}",
                        method: "POST",
                        dataType: "json",
                        data: data,
                        success: function(response) {
                            console.log(response)

                            Swal.fire(
                                response.status + '!',
                                response.message,
                                'success'
                                );
                            $('#modalEditClientsData').modal('hide');
                            location.reload();
                        },
                        error: function(err) {
                            console.log(err);
                        }

                    });
                }
            })
        })

        $('.freeze').click(function() {

            var id = $(this).data('id');
            var name = $(this).data('name');
            var status = $(this).data('status');

            Swal.fire({
                title: 'Are you sure?',
                text: "You want " + status + " bank: " + name,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#aaa',
                confirmButtonText: 'Yes, ' + status + ' it!'
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: "{{ switchUrl('banks') }}" + '/' + id + '/freeze',
                        method: "GET",
                        dataType: "json",
                        success: function(response) {
                            if (response.status = 'success') {
                                Swal.fire(
                                    'Freezed!',
                                    response.message,
                                    'success'
                                    );
                            } else {
                                Swal.fire(
                                    'Error',
                                    response.message,
                                    'error'
                                    );
                            }

                            location.reload();

                        },
                        error: function(err) {
                            console.log(err);
                        }

                    });

                }
            })

        });
    });

</script>
@endsection
