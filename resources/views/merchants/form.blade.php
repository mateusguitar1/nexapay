@extends('layouts.appdash')

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
<style>
    h1,h2,h3,h4,h5{
        margin-bottom: 0px;
    }
    .horizontal-center{
        vertical-align: middle;align-self: center;
    }
    .fa-chevron-down{
        cursor:pointer;
    }
</style>
@endsection
@section('content')
<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <form action="{{$data['url']}}" method="POST" enctype='multipart/form-data'>
            {{ csrf_field() }}
            @if($data['model'])
                @method('PUT')
            @endif
            <div class="card">
                <div class="card-header">
                    <div class="row">  <h5 class="ml-2"> <i class="fa fa-user text-black mr-2"></i>Client Infos</h5></div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-3 mb-4">
                            <div class="md-form">
                                <label for="name">Name</label>
                                <input type="text" name="client[name]" id="name" value="{{ $data['model'] ? $data['model']->name : old('name', '') }}" class='form-control'>
                            </div>
                        </div>

                        <div class="col-sm-3 mb-4">
                            <div class="md-form">
                                <label for="address">Address</label>
                                <input type="text" name="client[address]" id="address" value="{{ $data['model'] ? $data['model']->address : old('address', '') }}" class='form-control'>
                            </div>
                        </div>

                        <div class="col-sm-3 mb-4">
                            <div class="md-form">
                                <label for="contact">Contact</label>
                                <input type="text" name="client[contact]" id="contact" value="{{ $data['model'] ? $data['model']->contact : old('contact', '') }}" class='form-control'>
                            </div>
                        </div>

                        <div class="col-sm-3">
                            <div class="md-form">
                                <label for="document_holder">Document Holder</label>
                                <input type="text" name="client[document_holder]" id="document_holder" value="{{ $data['model'] ? $data['model']->document_holder : old('document_holder', '') }}" class='form-control'>
                            </div>
                        </div>

                        <div class="col-sm-3">
                            <label for="customFile">Contract</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" name="contract" value="{{ $data['model'] ? 'Update Contract' : old('contract', '') }}" id="customFile">
                                <label class="custom-file-label" for="customFile">Choose file</label>
                            </div>
                        </div>

                        <div class="col-sm-3 mb-4">
                            <div class="md-form">
                                <label for="country">Country</label>
                                <select id="country" name="client[country]" class="form-control">
                                    @foreach( availableCountries() as $key => $country )
                                    <option {{$data['model'] && $data['model']->currency == $key ? 'selected' : ''}}  value="{!! $key !!}">{!! $country !!}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-sm-3 mb-4">
                            <div class="md-form">
                                <label for="currency">Currency</label>
                                <!-- <input type="text" name="client[currency]" id="currency" value="{{ $data['model'] ? $data['model']->currency : old('currency', '') }}" class='form-control'> -->
                                <select name="client[currency]" id="currency" class='form-control'>
                                    <option {{$data['model'] && $data['model']->currency == 'brl' ? 'selected' : ''}} value="brl">Real</option>
                                    <option {{$data['model'] && $data['model']->currency == 'usd' ? 'selected' : ''}} value="usd">Dolar</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <div class="row">
                        <div class="col-sm-12">
                            <h5> <i class="fa fa-money text-black mr-2"></i>License, Comission, Availability and Limits</h5>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row" style="margin: 0px;">

                        <table class='table'>
                            <thead>
                                <tr>
                                    <th>TYPE</th>
                                    <th>METHOD</th>
                                    <th>FEE %</th>
                                    <th>FIXED FEE</th>
                                    <th>MIN. FEE</th>
                                    <th>LIQ(D + X)</th>
                                    <th>MIN. VALUE</th>
                                    <th>MAX. VALUE</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td width="5%">Deposit</td>
                                    <td width="5%">Invoice</td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">%</span>
                                            <input type="text" name="tax[boleto_percent]" id="boleto_percent" value="{{ $data['model'] ? number_format($data['taxs']->boleto_percent,"2",",",".") : old('boleto_percent', '') }}" maxlength="5" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1"><?php echo 'R$'; ?></span>
                                            <input type="text" name="tax[boleto_absolute]" id="boleto_absolute" value="{{ $data['model'] ? number_format($data['taxs']->boleto_absolute,"2",",",".") : old('boleto_absolute', '') }}" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1"><?php echo 'R$'; ?></span>
                                            <input type="text" name="tax[min_fee_boleto]" id="min_fee_boleto" value="{{ $data['model'] ? number_format($data['taxs']->min_fee_boleto,"2",",",".") : old('min_fee_boleto', '') }}" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1"><i class="fa fa-calendar"></i></span>
                                            <input type="text" name="client[days_safe_boleto]" id="days_safe_boleto" value="{{ $data['model'] ? $data['model']->days_safe_boleto : old('days_safe_boleto', '2') }}" class='form-control'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">R$</span>
                                            <input type="text" name="tax[min_boleto]" id="min_boleto" value="{{ $data['model'] ? number_format($data['taxs']->min_boleto,"2",",",".") : old('min_boleto', '') }}" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">R$</span>
                                            <input type="text" name="tax[max_boleto]" id="max_boleto" value="{{ $data['model'] ? number_format($data['taxs']->max_boleto,"2",",",".") : old('max_boleto', '') }}" class='form-control money'>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td width="5%">Deposit</td>
                                    <td width="5%">PIX</td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">%</span>
                                            <input type="text" name="tax[pix_percent]" id="pix_percent" value="{{ $data['model'] ? number_format($data['taxs']->pix_percent,"2",",",".") : old('pix_percent', '') }}" maxlength="5" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1"><?php echo 'R$'; ?></span>
                                            <input type="text" name="tax[pix_absolute]" id="pix_absolute" value="{{ $data['model'] ? number_format($data['taxs']->pix_absolute,"2",",",".") : old('pix_absolute', '') }}" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1"><?php echo 'R$'; ?></span>
                                            <input type="text" name="tax[min_fee_pix]" id="min_fee_pix" value="{{ $data['model'] ? number_format($data['taxs']->min_fee_pix,"2",",",".") : old('min_fee_pix', '') }}" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1"><i class="fa fa-calendar"></i></span>
                                            <input type="text" name="client[days_safe_pix]" id="days_safe_pix" value="{{ $data['model'] ? $data['model']->days_safe_pix : old('days_safe_pix', '2') }}" class='form-control'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">R$</span>
                                            <input type="text" name="tax[min_pix]" id="min_pix" value="{{ $data['model'] ? number_format($data['taxs']->min_pix,"2",",",".") : old('min_pix', '') }}" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">R$</span>
                                            <input type="text" name="tax[max_pix]" id="max_pix" value="{{ $data['model'] ? number_format($data['taxs']->max_pix,"2",",",".") : old('max_pix', '') }}" class='form-control money'>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td width="5%">Deposit</td>
                                    <td width="5%">Credit Card</td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">%</span>
                                            <input type="text" name="tax[cc_percent]" id="cc_percent" value="{{ $data['model'] ? number_format($data['taxs']->cc_percent,"2",",",".") : old('cc_percent', '') }}" maxlength="5" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1"><?php echo 'R$'; ?></span>
                                            <input type="text" name="tax[cc_absolute]" id="cc_absolute" value="{{ $data['model'] ? number_format($data['taxs']->cc_absolute,"2",",",".") : old('cc_absolute', '') }}" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1"><?php echo 'R$'; ?></span>
                                            <input type="text" name="tax[min_fee_cc]" id="min_fee_cc" value="{{ $data['model'] ? number_format($data['taxs']->min_fee_cc,"2",",",".") : old('min_fee_cc', '') }}" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1"><i class="fa fa-calendar"></i></span>
                                            <input type="text" name="client[days_safe_cc]" id="days_safe_cc" value="{{ $data['model'] ? $data['model']->days_safe_cc : old('days_safe_cc', '2') }}" class='form-control'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">R$</span>
                                            <input type="text" name="tax[min_cc]" id="min_cc" value="{{ $data['model'] ? number_format($data['taxs']->min_cc,"2",",",".") : old('min_cc', '') }}" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">R$</span>
                                            <input type="text" name="tax[max_cc]" id="max_cc" value="{{ $data['model'] ? number_format($data['taxs']->max_cc,"2",",",".") : old('max_cc', '') }}" class='form-control money'>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td width="5%">Deposit</td>
                                    <td width="5%">Transfer</td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">%</span>
                                            <input type="text" name="tax[replacement_percent]" id="replacement_percent" value="{{ $data['model'] ? number_format($data['taxs']->replacement_percent,"2",",",".") : old('replacement_percent', '') }}" maxlength="5" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1"><?php echo 'R$'; ?></span>
                                            <input type="text" name="tax[replacement_absolute]" id="replacement_absolute" value="{{ $data['model'] ? number_format($data['taxs']->replacement_absolute,"2",",",".") : old('replacement_absolute', '') }}" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1"><?php echo 'R$'; ?></span>
                                            <input type="text" name="tax[min_replacement]" id="min_replacement" value="{{ $data['model'] ? number_format($data['taxs']->min_replacement,"2",",",".") : old('min_replacement', '') }}" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1"><i class="fa fa-calendar"></i></span>
                                            <input type="text" name="client[days_safe_ted]" id="days_safe_ted" value="{{ $data['model'] ? $data['model']->days_safe_ted : old('days_safe_ted', '2') }}" class='form-control'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">R$</span>
                                            <input type="text" name="tax[min_deposit]" id="min_deposit" value="{{ $data['model'] ? number_format($data['taxs']->min_deposit,"2",",",".") : old('min_deposit', '') }}" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">R$</span>
                                            <input type="text" name="tax[max_deposit]" id="max_deposit" value="{{ $data['model'] ? number_format($data['taxs']->max_deposit,"2",",",".") : old('max_deposit', '') }}" class='form-control money'>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="5%">Withdraw</td>
                                    <td width="5%">TEF</td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">%</span>
                                            <input type="text" name="tax[withdraw_percent]" id="withdraw_percent" value="{{ $data['model'] ? number_format($data['taxs']->withdraw_percent,"2",",",".") : old('withdraw_percent', '') }}" maxlength="5" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1"><?php echo 'R$'; ?></span>
                                            <input type="text" name="tax[withdraw_absolute]" id="withdraw_absolute" value="{{ $data['model'] ? number_format($data['taxs']->withdraw_absolute,"2",",",".") : old('withdraw_absolute', '') }}" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1"><?php echo 'R$'; ?></span>
                                            <input type="text" name="tax[min_fee_withdraw]" id="min_fee_withdraw" value="{{ $data['model'] ? number_format($data['taxs']->min_fee_withdraw,"2",",",".") : old('min_fee_withdraw', '') }}" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%" align='center'>
                                        <span> - </span>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">R$</span>
                                            <input type="text" name="tax[min_withdraw]" id="min_withdraw" value="{{ $data['model'] ? number_format($data['taxs']->min_withdraw,"2",",",".") : old('min_withdraw', '') }}" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">R$</span>
                                            <input type="text" name="tax[max_withdraw]" id="max_withdraw" value="{{ $data['model'] ? number_format($data['taxs']->max_withdraw,"2",",",".") : old('max_withdraw', '') }}" class='form-control money'>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="5%">Withdraw</td>
                                    <td width="5%">Transfer</td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">%</span>
                                            <input type="text" name="tax[remittance_percent]" id="remittance_percent" value="{{ $data['model'] ? number_format($data['taxs']->remittance_percent,"2",",",".") : old('remittance_percent', '') }}" maxlength="5" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1"><?php echo 'R$'; ?></span>
                                            <input type="text" name="tax[remittance_absolute]" id="remittance_absolute" value="{{ $data['model'] ? number_format($data['taxs']->remittance_absolute,"2",",",".") : old('remittance_absolute', '') }}" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%" align="center">
                                        -
                                    </td>
                                    <td width="10%" align='center'>
                                        <span> - </span>
                                    </td>
                                    <td width="10%" align="center">
                                        -
                                    </td>
                                    <td width="10%" align="center">
                                        -
                                    </td>
                                </tr>
                                <tr>
                                    <td width="5%">Withdraw</td>
                                    <td width="5%">PIX</td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">%</span>
                                            <input type="text" name="tax[withdraw_pix_percent]" id="withdraw_pix_percent" value="{{ $data['model'] ? number_format($data['taxs']->withdraw_pix_percent,"2",",",".") : old('withdraw_pix_percent', '') }}" maxlength="5" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1"><?php echo 'R$'; ?></span>
                                            <input type="text" name="tax[withdraw_pix_absolute]" id="withdraw_pix_absolute" value="{{ $data['model'] ? number_format($data['taxs']->withdraw_pix_absolute,"2",",",".") : old('withdraw_pix_absolute', '') }}" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%" align="center">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1"><?php echo 'R$'; ?></span>
                                            <input type="text" name="tax[min_fee_withdraw_pix]" id="min_fee_withdraw_pix" value="{{ $data['model'] ? number_format($data['taxs']->min_fee_withdraw_pix,"2",",",".") : old('min_fee_withdraw_pix', '') }}" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%" align='center'>
                                        <span> - </span>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">R$</span>
                                            <input type="text" name="tax[min_withdraw_pix]" id="min_withdraw_pix" value="{{ $data['model'] ? number_format($data['taxs']->min_withdraw_pix,"2",",",".") : old('min_withdraw_pix', '') }}" class='form-control money'>
                                        </div>
                                    </td>
                                    <td width="10%">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">R$</span>
                                            <input type="text" name="tax[max_withdraw_pix]" id="max_withdraw_pix" value="{{ $data['model'] ? number_format($data['taxs']->max_withdraw_pix,"2",",",".") : old('max_withdraw_pix', '') }}" class='form-control money'>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <div class="row">
                        <div class="col-sm-12">
                            <h5> <i class="fa fa-money text-black mr-2"></i> Aditional Taxes</h5>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">

                        <div class="col-sm-2 mb-4">
                            <div class="md-form">
                                <label for="boleto_cancel">FEE BOLETO CANCEL</label>
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="basic-addon1">R$</span>
                                    <input type="text" name="tax[boleto_cancel]" id="boleto_cancel" value="{{ $data['model'] ? number_format($data['taxs']->boleto_cancel,"2",",",".") : old('boleto_cancel', '') }}" maxlength="5" class='form-control money'>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-2 mb-4">
                            <div class="md-form">
                                <label for="boleto_cancel">MAX FEE WITHDRAW</label>
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="basic-addon1">R$</span>
                                    <input type="text" name="tax[max_fee_withdraw]" id="max_fee_withdraw" value="{{ $data['model'] ? number_format($data['taxs']->max_fee_withdraw,"2",",",".") : old('max_fee_withdraw', '') }}" maxlength="5" class='form-control money'>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <div class="row">
                        <div class="col-sm-12">
                            <h5> <i class="fa fa-university text-black mr-2"></i> Banks</h5>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-3 mb-4">
                            <div class="md-form">
                                <label for="bank_invoice">Bank Invoice</label>
                                <select name="client[bank_invoice]" id="bank_invoice" class='form-control'>
                                @foreach($data['banks'] as $bank)
                                    <option {{$data['model'] && $data['model']->bankInvoice->id == "$bank->id" ? 'selected' : ''}} value="{{$bank->id}}">{{$bank->name.' - Ag:'.$bank->agency.' - CC:'.$bank->account.' - '.$bank->holder}}</option>
                                @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-sm-3 mb-4">
                            <div class="md-form">
                                <label for="bank_pix">PIX</label>
                                <select name="client[bank_pix]" id="bank_pix" class='form-control'>
                                @foreach($data['banks'] as $bank)
                                    <option {{$data['model'] && $data['model']->bankPix->id == "$bank->id" ? 'selected' : ''}} value="{{$bank->id}}">{{$bank->name.' - Ag:'.$bank->agency.' - CC:'.$bank->account.' - '.$bank->holder}}</option>
                                @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-sm-3 mb-4">
                            <div class="md-form">
                                <label for="bank_ted">TED</label>
                                <select name="client[bank_ted]" id="bank_ted" class='form-control'>
                                @foreach($data['banks'] as $bank)
                                    <option {{$data['model'] && $data['model']->bankTed->id == "$bank->id" ? 'selected' : ''}} value="{{$bank->id}}">{{$bank->name.' - Ag:'.$bank->agency.' - CC:'.$bank->account.' - '.$bank->holder}}</option>
                                @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-sm-3 mb-4">
                            <div class="md-form">
                                <label for="bank_cc">Credit Card</label>
                                <select name="client[bank_cc]" id="bank_cc" class='form-control'>
                                @foreach($data['banks'] as $bank)
                                    <option {{$data['model'] && $data['model']->bankCC->id == "$bank->id" ? 'selected' : ''}} value="{{$bank->id}}">{{$bank->name.' - Ag:'.$bank->agency.' - CC:'.$bank->account.' - '.$bank->holder}}</option>
                                @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-sm-3 mb-4">
                            <div class="md-form">
                                <label for="bank_withdraw_permition">BANK WITHDRAW PERMITION </label>
                                <select name="client[bank_withdraw_permition]" id="bank_withdraw_permition" class='form-control'>
                                @foreach($data['banks'] as $bank)
                                    @if(isset($data['model']->bankWithdrawPix->id))
                                        <option {{$data['model'] && $data['model']->bankWithdrawPix->id == "$bank->id" ? 'selected' : ''}} value="{{$bank->id}}">{{$bank->name.' - Ag:'.$bank->agency.' - CC:'.$bank->account.' - '.$bank->holder}}</option>
                                    @else
                                        <option value="{{$bank->id}}">{{$bank->name.' - Ag:'.$bank->agency.' - CC:'.$bank->account.' - '.$bank->holder}}</option>
                                    @endif
                                @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <div class="row">
                        <div class="col-sm-12">
                            <h5> <i class="fa fa-link text-black mr-2"></i> Base URL</h5>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">

                        <div class="col-sm-12 mb-12">
                            <div class="md-form">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="basic-addon1"><i class="fa fa-key"></i></span>
                                    <input readonly type="text" value="https://tech.fastpayments.com.br/" class='form-control'>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-2">
                            <a href="{{switchUrl('clients')}}" style='width:100%' class="btn btn-light left"><i class="fa fa-undo pr-2" aria-hidden="true"></i>Back</a>
                        </div>

                        <div class="col-sm-2 offset-sm-8">
                            <button style='width:100%' type="submit" class="btn btn-success right width-100"><i class="fa fa-save pr-2" aria-hidden="true"></i>{{$data['button']}}</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="card mt-4">
            <div class="card-header">
                <div class="row">
                    <div class="col-sm-12">
                        <h5> <i class="fa fa-key text-black mr-2"></i> Authentication Keys</h5>
                    </div>
                </div>
            </div>

            <div class="card-body" >
                <form action="{{switchUrl('merchants/update_webhook')}}" method="POST" style="width:100%;">
                    {{ csrf_field() }}
                    <div class="row">
                        <input type="hidden" name="action" value="update">
                        <div class="col-lg-4 col-xs-12" >
                            <div class="form-group">
                                <label for="">Token</label>
                                <input type="text" name="token" id="token" value="{{ $data['model'] ? $data['model']->key()->first()->authorization : old('authorization', '') }}" class="form-control">
                            </div>
                        </div>

                        <div class="col-lg-4 col-xs-12" >
                            <div class="form-group">
                                <label for="">URL callback</label>
                                <input type="text" name="url_callback" id="url_callback" value="{{ $data['model'] ? $data['model']->key()->first()->url_callback : old('url_callback', '') }}" class="form-control">
                            </div>
                        </div>

                        <div class="col-lg-4 col-xs-12" >
                            <div class="form-group">
                                <label for="">URL callback withdraw</label>
                                <input type="text" name="url_callback_withdraw" id="url_callback_withdraw" value="{{ $data['model'] ? $data['model']->key()->first()->url_callback_withdraw : old('url_callback_withdraw', '') }}" class="form-control">
                            </div>
                        </div>

                        @if(isset($data['model']))

                            <div class="col-md-2 col-xs-2">
                                <a href="{{switchUrl('merchants/update_api_keys/'.$data['model']->id)}}" class="confirmation-api-keys"><button type="button" class="btn" style="width:100%;background-color:#e8900c;color:#FFF;"><i class="da da-key"></i> UPDATE API KEYS</button></a>
                            </div>

                            <div class="form-group offset-md-8 offset-xs-8 col-md-2 col-xs-2" >
                                <div class="form-group">
                                <button type="submit" class="btn btn-success confirmation-url-callback" style="width:100%;"><i class="fa fa-save"></i> UPDATE URL CALLBACK</button>
                                </div>
                            </div>
                        @endif
                    </div>
                    <input type="hidden" name="client_id" value="{{ $data['model'] ? $data['model']->id : old('id', '') }}">
                </form>
            </div>
        </div>

    </div>
</div>


@endsection
@section('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script type='text/javascript' src='https://cdnjs.cloudflare.com/ajax/libs/jquery-maskmoney/3.0.2/jquery.maskMoney.min.js'></script>

<script>

    $( document ).ready(function() {

        $(".money").maskMoney({prefix:'', allowNegative: true, thousands:'.', decimal:',', affixesStay: false});

        $(".custom-file-input").on("change", function() {
            var fileName = $(this).val().split("\\").pop();
            $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
        });

    });
</script>
@endsection
