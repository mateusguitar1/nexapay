@extends('layouts.appdash')

@section('style')
<meta name="csrf-token" content="{{ csrf_token() }}" />
<style>
.fa-user:hover{
    cursor: pointer;
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
<div class="card">
    <div class="card-header margin15">
        <div class="col-sm-12 col-md-12" style="margin-bottom: 15px;">

            <form method="POST" action="{{switchUrl('/receita/search')}}">
                {{ csrf_field() }}
                <div class="row" >
                    <div class="col-md-3">
                        <label for="client">CLIENT</label>
                        <select name="client_id" id="client" class='form-control'>
                            <option value="all">All</option>
                            @foreach($data['clients'] as $client)
                            <option value="{{ $client->id }}" {!! @(int)$data['client_id'] === (int)$client->id ? 'selected' : Null !!}>{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="client">METHOD</label>
                        <select name="method" id="method" class='form-control'>
                            <option selected value="all">All</option>
                            <option  value="pix">PIX</option>
                            <option  value="invoice">BOLETO</option>
                            <option  value="automatic_checking">SHOP</option>
                            <option  value="credit_card">CREDIT CARD</option>
                            <option  value="debit_card">DEBIT CARD</option>
                            <option  value="bank_transfer">BANK TRANSFER</option>
                            <option  value="TEF">TEF</option>
                        </select>
                    </div>
                    <div class="col-md-3 mt1">
                        <label for="client">DATE</label>
                        <button type="button" class="btn btn-date" name="new_date" id="daterangepicker2" style="width:100%;">
                            <i class="fa fa-calendar"></i>
                            <span>{{date('F j, Y')}} - {{date('F j, Y')}}</span> <b class="caret"></b>

                        </button>
                        <input type="hidden" class="form-control" name="minall" value="{{date('d-m-Y')}}" placeholder="Start" id="minall"  autocomplete="off" />
                        <input type="hidden" class="form-control" name="maxall" value="{{date('d-m-Y')}}" placeholder="End" id="maxall"  autocomplete="off" />
                    </div>


                    <div class="col-md-2  mt1">
                        <button class="btn btn-success" type="submit" style="width:100%;margin-top:26px;"><i class="fa fa-search"></i></button>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-sm-12 col-md-6" style="margin-bottom: 15px;">

        </div>
    </div>
</div>

<br>
<div class="container-fluid">
    <section class="margin-15 section-table" style="margin-top:0px !important">
        <table class="table table-striped table-bordered statement" style="width:100%">
            <thead>
                <tr>
                    <th width="10%">Cliente</th>
                    <th width="10%">Receita Spread</th>
                    <th width="10%">Receita Comission</th>
                </tr>
            </thead>
            <tbody>
                @php
                    if (isset($data['tabela'])):
                        print $data['tabela'];
                    endif
                @endphp
            </tbody>
        </table>
    </section>
</div>


@endsection
@section('js')

<!-- Latest compiled and minified JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>

<script>
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
        //   startDate: moment(),//.subtract('days', 29),
        //   endDate: moment()

        },
        function(start, end){
          $('#daterangepicker2 span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
          $("#minall").val(start.format('DD-MM-YYYY'));
          $("#maxall").val(end.format('DD-MM-YYYY'));
        });




</script>





@endsection

