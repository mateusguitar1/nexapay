@extends('layouts.appdash')
@section('style-import')
<link href="{{ asset('css/custom.css') }}" rel="stylesheet">
@endsection

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">

            <div class="row mb-2">
                <div class="col-sm-11">
                    <h1 class="m-0">{{$data['title']}}</h1>
                </div><!-- /.col -->
            </div><!-- /.row -->
            <br/>
            <div class="card">

                <form action="{{$data['url']}}" method="POST">
                    {{ csrf_field() }}
                    @if($data['model'])
                        @method('PUT')
                    @endif
                    <div class="card-body">

                        <div class="row">
                            <div class="col-sm-4">
                                <div class="md-form">
                                    <label for="name">Code</label>
                                    <input type="text" name="bank[code]" id="code" value="{{ $data['model'] ? $data['model']->code : old('code', '') }}" class='form-control'>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="md-form">
                                    <label for="name">Name</label>
                                    <input type="text" name="bank[name]" id="name" value="{{ $data['model'] ? $data['model']->name : old('name', '') }}" class='form-control'>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="md-form">
                                    <label for="name">Holder</label>
                                    <input type="text" name="bank[holder]" id="holder" value="{{ $data['model'] ? $data['model']->holder : old('holder', '') }}" class='form-control'>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="md-form">
                                    <label for="name">Type Account</label>
                                    <input type="text" name="bank[type_account]" id="type_account" value="{{ $data['model'] ? $data['model']->type_account : old('type_account', '') }}" class='form-control'>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="md-form">
                                    <label for="name">Agency</label>
                                    <input type="text" name="bank[agency]" id="agency" value="{{ $data['model'] ? $data['model']->agency : old('agency', '') }}" class='form-control'>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="md-form">
                                    <label for="name">Account</label>
                                    <input type="text" name="bank[account]" id="account" value="{{ $data['model'] ? $data['model']->account : old('account', '') }}" class='form-control'>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="md-form">
                                    <label for="name">Document</label>
                                    <input type="text" name="bank[document]" id="document" value="{{ $data['model'] ? $data['model']->document : old('document', '') }}" class='form-control'>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="md-form">
                                    <label for="name">Status</label>
                                    <input type="text" name="bank[status]" id="status" value="{{ $data['model'] ? $data['model']->status : old('status', '') }}" class='form-control'>
                                </div>
                            </div>
                            <!--<div class="col-sm-4">
                                <div class="md-form">
                                    <label for="name">Prefix</label>
                                    <input type="text" name="bank[prefix]" id="prefix" value="{{ $data['model'] ? $data['model']->prefix : old('prefix', '') }}" class='form-control'>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="md-form">
                                    <label for="name">Código Estação Santander</label>
                                    <input type="text" name="bank[cod_estacao_santander]" id="cod_estacao_santander" value="{{ $data['model'] ? $data['model']->cod_estacao_santander : old('cod_estacao_santander', '') }}" class='form-control'>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="md-form">
                                    <label for="name">Address</label>
                                    <input type="text" name="bank[address]" id="address" value="{{ $data['model'] ? $data['model']->address : old('address', '') }}" class='form-control'>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="md-form">
                                    <label for="name">Coódigo Empresa Itau </label>
                                    <input type="text" name="bank[cod_empresa_itau]" id="cod_empresa_itau" value="{{ $data['model'] ? $data['model']->cod_empresa_itau : old('cod_empresa_itau', '') }}" class='form-control'>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="md-form">
                                    <label for="name">Chave Itau</label>
                                    <input type="text" name="bank[chave_itau]" id="chave_itau" value="{{ $data['model'] ? $data['model']->chave_itau : old('chave_itau', '') }}" class='form-control'>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="md-form">
                                    <label for="name">ID Convenio BB</label>
                                    <input type="text" name="bank[id_convenio_bb]" id="id_convenio_bb" value="{{ $data['model'] ? $data['model']->id_convenio_bb : old('id_convenio_bb', '') }}" class='form-control'>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="md-form">
                                    <label for="name">Merchant ID Bradesco</label>
                                    <input type="text" name="bank[merchantId_bradesco]" id="merchantId_bradesco" value="{{ $data['model'] ? $data['model']->merchantId_bradesco : old('merchantId_bradesco', '') }}" class='form-control'>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="md-form">
                                    <label for="name">Merchant Key Bradesco</label>
                                    <input type="text" name="bank[merchantKey_bradesco]" id="merchantKey_bradesco" value="{{ $data['model'] ? $data['model']->merchantKey_bradesco : old('merchantKey_bradesco', '') }}" class='form-control'>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="md-form">
                                    <label for="name">ID Convenio BB Boleto</label>
                                    <input type="text" name="bank[id_convenio_bb_boleto]" id="id_convenio_bb_boleto" value="{{ $data['model'] ? $data['model']->id_convenio_bb_boleto : old('id_convenio_bb_boleto', '') }}" class='form-control'>
                                </div>
                            </div> -->

                        </div>

                        <div class="row mt-2">

                            <div class="col-sm-2">
                                <a href="{{switchUrl('banks')}}" style='width:100%' class="btn btn-light left"><i class="fa fa-undo pr-2" aria-hidden="true"></i>Back</a>
                            </div>

                            <div class="col-sm-2 offset-sm-8">
                                <button style='width:100%' type="submit" class="btn btn-success right width-100"><i class="fa fa-save pr-2" aria-hidden="true"></i>{{$data['button']}}</button>
                            </div>

                        </div>

                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection
