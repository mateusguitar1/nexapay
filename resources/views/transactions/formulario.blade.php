<form action="{{$data['url']}}" method="POST" enctype="multipart/form-data">
    {{ csrf_field() }}
    @if($data['model'])
        @method('PUT')
    @endif
    <div class="card-body">

        <div class="row">
            <div class="col-sm-4 mb-2">
                <div class="md-form">
                    <label for="type">Operation Type</label>
                    <select type="text" name="transaction[type_transaction]" id="type_transaction" class='form-control'>
                        <option {{$data['model'] && $data['model']->type_transaction == 'deposit' ? 'selected' : '' }} value="deposit">Deposit</option>
                        <option {{$data['model'] && $data['model']->type_transaction == 'withdraw' ? 'selected' : '' }} value="withdraw">Withdraw</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-4 mb-2">
                <div class="md-form">
                    <label for="client">Client</label>
                    <select type="text" name="transaction[client_id]" id="client_bank_transfer" class='form-control'>
                        @foreach($data['clients'] as $client)
                            <option {{$data['model'] && $data['model']->client->id == $client->id ? 'selected' : '' }} value="{{$client->id}}">{{$client->name}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-4 mb-2">
                <div class="md-form">
                    <label for="bank">Bank</label>
                    <select type="text" name="transaction[id_bank]" id="banks_transfer" class='form-control'>
                        @foreach($data['banks'] as $bank)
                        <option {{$data['model'] && $data['model']->bank->id == $bank->id ? 'selected' : '' }} value="{{$bank->id}}">{{$bank->name}} - {{$bank->agency}} - {{$bank->account}} - {{$bank->holder}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-3 mb-2">
                <div class="md-form">
                    <label for="final_amount">Final Amount</label>
                    <div class="input-group-prepend">
                        <span class="input-group-text" id="basic-addon1"><span class="currency_client"></span></span>
                        <input type="text" name="transaction[final_amount]" id="final_amount" value="{{ $data['model'] ? $data['model']->final_amount : old('final_amount', '') }}" class='form-control money2'>
                    </div>
                </div>
            </div>
            <div class="col-sm-3 mb-2">
                <div class="md-form">
                <label for="quote_markup">Quote Spread</label>
                    <div class="input-group-prepend">
                        <span class="input-group-text" id="basic-addon1">R$</span>
                        <input type="text" id="quote_markup" disabled value="{{ $data['model'] ? $data['model']->quote_markup : old('quote_markup', '') }}" class='form-control money2'>
                        <input type="hidden" class="quote" name="transaction[quote]" />
                        <input type="hidden" class="percent_markup" name="transaction[percent_markup]" />
                        <input type="hidden" class="quote_markup" name="transaction[quote_markup]" />
                    </div>
                </div>
            </div>
            <div class="col-sm-3 mb-2">
                <div class="md-form">
                    <label for="amount_solicitation">Amount Solicitation</label>
                    <div class="input-group-prepend">
                        <span class="input-group-text" id="basic-addon1">R$</span>
                        <input type="text" name="transaction[amount_solicitation]" id="amount_solicitation" required="required"  value="{{ $data['model'] ? $data['model']->amount_solicitation : old('amount_solicitation', '') }}" class='form-control money2'>
                    </div>
                </div>
            </div>
            <div class="col-sm-3 mb-2">
                <div class="md-form">
                    <label for="fee">Fixed Fee / Comission</label>
                    <div class="input-group-prepend">
                        <span class="input-group-text" id="basic-addon1"><span class="currency_client"></span></span>
                        <input type="text" name="transaction[fixed_fee]" id="fee" value="{{ $data['model'] ? $data['model']->fixed_fee : old('fixed_fee', '') }}" class='form-control money2'>
                    </div>
                </div>
            </div>
            <div class="col-sm-9 mb-2">
                <div class="md-form">
                    <label for="observation">Observation</label>
                    <input type="text" name="transaction[observation]" id="observation" value="{{ $data['model'] ? $data['model']->observation : old('transaction[observation]', '') }}" class='form-control '>
                </div>
            </div>
            <div class="col-sm-3 mb-2">
                <label for="receipt">Receipt</label>
                <div class="custom-file">
                    <input type="file" name="receipt" id="receipt" class="custom-file-input" id="customFile">
                    <label class="custom-file-label" for="customFile">Choose file</label>
                </div>
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-sm-2">
                <a href="#!" data-dismiss="modal" style='width:100%' class="btn btn-light left"><i class="fa fa-undo pr-2" aria-hidden="true"></i>Back</a>
            </div>
            <div class="col-sm-2 offset-sm-8">
                <button style='width:100%' type="submit" class="btn btn-success right width-100"><i class="fa fa-save pr-2" aria-hidden="true"></i>{{$data['button']}}</button>
            </div>
        </div>
    </div>

</form>
