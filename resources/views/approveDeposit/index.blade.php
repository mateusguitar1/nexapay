@extends('layouts.appdash')

@section('css')
<style type="text/css">
.wrapper2{
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1040;
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
</style>
@endsection

@section('content')
<div id="load" class="wrapper2 text-center"  style="display:none">
    <div class="spinner-border spin" role="status">
        <span class="sr-only">Loading...</span>
    </div>
</div>
<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Deposits</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
                <form method="POST" action="{{switchUrl('approveDeposit/search')}}">
                    {{ csrf_field() }}
                        <div class="row" style="margin:1px">
                            <div class="col-md-4 ">
                                <label for="client">CLIENT</label>
                                <select name="client_id" id="client" class='form-control'>
                                    @foreach($data['clients'] as $client)
                                        <option @if(isset($data['request'])) @if($client->id == $data['request']->client_id) selected @endif @endif value="{{$client->id}}">{{$client->name}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="search">SEARCH</label>
                                <input type="text" name="search" id="search" class='form-control'>
                            </div>

                            <div class="col-md-1 offset-md-5 mt1">
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

      <div class="card">

        <div class="card-body p-0">

            <table class='table' >
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Order ID</th>
                        <th>User ID</th>
                        <th>Bank</th>
                        <th>Amount</th>
                        <th>status</th>
                        <th>Action</th>
                        <th>Edit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['transactions'] as $transaction)
                    <?php
                        if($transaction->id_bank == null){
                            $bank = '--';
                        }else{
                            $bank = $transaction->bank->name.'<br/>'.$transaction->bank->agency.'<br/>'.$transaction->bank->account;
                        }
                    ?>
                    <tr>
                        <td>{{date('d/m/Y H:i',strtotime($transaction->solicitation_date))}}</td>
                        <td>{{$transaction->client->name}}</td>
                        <td>{{$transaction->order_id}}</td>
                        <td><a href="#!" class='openModalUser' data-toggle="modal" data-order="{{$transaction->order_id}}" data-client="{{$transaction->client_id}}" data-target="#userInfo">{{$transaction->user_id}}</a></td>
                        <td><?=$bank?></td>
                        <td>
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon1">R$</span>
                                <input type="text" class='form-control' value="{{number_format($transaction->amount_solicitation,2,",",".")}}">
                            </div>
                        </td>
                        <td>
                            {{ $transaction->status }}
                        <td>
                            <input type="hidden" name="id_transaction" value="{{$transaction->id}}">
                            <div class="row">
                                <div class="col col-sm-2">
                                    Amount:
                                </div>
                                <div class="col col-sm-4 div-amount">
                                    <input type="text" name="" placeholder="Amount confirmed" class="form-control money" >
                                </div>
                                <div class="col col-sm-6">
                                    <button class="btn btn-success approveDeposit" data-order="{{$transaction->order_id}}" data-client="{{$transaction->client_id}}" data-amount-solicitation="{{$transaction->amount_solicitation}}" data-toggle="modal" data-target="#approveDeposit" >Aproove Deposit</button>
                                    <button class="btn btn-secondary reportError" data-toggle="modal" data-order="{{$transaction->order_id}}" data-client="{{$transaction->client_id}}" data-target="#reportError" >Report Error</button>

                                </div>
                            </div>
                        </td>
                        <td>
                            <button class="btn btn-primary update" data-toggle="modal"  data-id="{{$transaction->id}}" data-status="{{ $transaction->status }}" data-solicitation="{{ $transaction->amount_solicitation }}"  data-final="{{ $transaction->final_amount }}" data-percent="{{ $transaction->percent_fee }}" data-fixed="{{ $transaction->fixed_fee }}"  data-target="#update"><i class="fa fa-edit"></i> Edit</button>
                        </td>

                    </tr>
                    @endforeach
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


<!-- Modal Update-->
<div class="modal fade" id="update" tabindex="-1" role="dialog" aria-labelledby="updateLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title" id="approveDepositLabel">Edit Transaction</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        </div>
        <form id="update-transaction">
        <div class="modal-body">
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

                    <div class="col-md-4">
                        <label for="">Amount Solicitation</label>
                        <input type="text" name="amount_solicitation" class="form-control amount_solicitation money" >
                    </div>
                    <div class="col-md-4">
                        <label for="">Final Amount</label>
                        <input type="text" name="final_amount" class="form-control final_amount money" >
                    </div>
                    <div class="col-md-4">
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
                </div>

        </div>
        <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary" >Save changes</button>
        </div>
    </form>
    </div>
    </div>
    </div>

<!-- Modal -->
<div class="modal fade" id="approveDeposit" tabindex="-1" role="dialog" aria-labelledby="approveDepositLabel" aria-hidden="true">
<div class="modal-dialog" role="document">
<div class="modal-content">
    <div class="modal-header">
    <h5 class="modal-title" id="approveDepositLabel">Aproove Deposit</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
    </div>
    <form id="deposit">
    <div class="modal-body">
    <p>Confirm the details below to prove the deposit</p>
    <table width="100%" cellpadding="0" cellspacing="0">
        <tbody>
            <tr>
                <td width="49%">REQUESTED<br>
                    <input type="text" readonly="" value="" class="form-control amount_before">
                </td>
                <td width="2%">&nbsp;</td>
                <td width="49%">
                    APPROVAL<br>
                    <input type="text" readonly="" value="" class="form-control amount_after">
                </td>
            </tr>
        </tbody>
    </table>
    <input class="approveDeposit" type="hidden" name="order_id">
    <input class="approveDeposit" type="hidden" name="client_id">
    <input class="approveDeposit" type="hidden" name="amount_confirmed">
    </div>
    <div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
    <button type="submit" class="btn btn-primary">Save changes</button>
    </div>
    </form>
</div>
</div>
</div>

<!-- Modal -->
<div class="modal fade" id="reportError" tabindex="-1" role="dialog" aria-labelledby="reportErrorLabel" aria-hidden="true">
<div class="modal-dialog" role="document">
<div class="modal-content">
    <div class="modal-header">
    <h5 class="modal-title" id="reportErrorLabel">Report Error</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
    </div>
    <form id="formError">
    <div class="modal-body">
    <div class="row">
        <div class="col-lg-12 col-xs-12">
            <div class="form-group">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td width="50%">
                                <label for="optionsRadios1">
                                <input type="radio" name="type_error" id="optionsRadios1" value="amount_error" checked="">
                                Incorrect Value
                                </label>
                                <br>
                                <label for="optionsRadios2">
                                <input type="radio" name="type_error" id="optionsRadios2" value="document_error">
                                Invalid document
                                </label>
                                <br>
                                <label for="optionsRadios3">
                                <input type="radio" name="type_error" id="optionsRadios3" value="payment_error">
                                Incorrect payment method
                                </label>
                            </td>
                            <td width="50%">
                                <label for="optionsRadios4">
                                <input type="radio" name="type_error" id="optionsRadios4" value="transfer_error">
                                Third party transfer
                                </label>
                                <br>
                                <label for="optionsRadios5">
                                <input type="radio" name="type_error" id="optionsRadios5" value="delay_error">
                                Payment after the deadline
                                </label>
                                <br>
                                <label for="optionsRadios6">
                                <input type="radio" name="type_error" id="optionsRadios6" value="other_error">
                                Other
                                </label>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <br>
        <div class="col-lg-12 col-xs-12">
            <textarea name="description_error" class="form-control" style="height:100px;" placeholder="Descreva..."></textarea>
        </div>
        <input class="reportError" type="hidden" name="order_id" >
        <input class="reportError" type="hidden" name="client_id" >
    </div>
    </div>
    <div class="modal-footer">
    <button type="button" class="btn btn-secondary " data-dismiss="modal">Close</button>
    <button type="submit" class="btn btn-primary ">Save changes</button>
    </div>
    </form>
</div>
</div>
</div>


<!-- Modal -->
<div class="modal fade" id="userInfo" tabindex="-1" role="dialog" aria-labelledby="userInfoLabel" aria-hidden="true">
<div class="modal-dialog" role="document">
<div class="modal-content">
    <div class="modal-header">
    <h5 class="modal-title" id="userInfoLabel">User Information</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
    </div>
    <div class="modal-body">

    <div id="carrega_informacao_usuario" class="carrega_informacao_usuario"></div>

    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary " data-dismiss="modal">Close</button>
    </div>
</div>
</div>
</div>


<!-- Modal import chargeback-->
<div class="modal fade" id="import_chargeback" tabindex="-1" role="dialog" aria-labelledby="importChargebackLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title" id="userInfoLabel">Import Chargeback</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        </div>
        <div class="modal-body">
            <form action="{{ url('import/import') }}" enctype="multipart/form-data" method="POST">
                {{ csrf_field() }}
                <div class="row">

                    <div class="col-sm-12 mb-2 col-md-12">
                        <label for="receipt">Chargebacks <div style="color:red">*xls/xlsx</div></label>
                        <div class="custom-file">
                            <input type="file" name="select_file" id="select_file" class="custom-file-input" >
                            <label class="custom-file-label" for="customFile">Choose file</label>
                        </div>
                    </div>

                </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success " >Save</button>
                    <button type="button" class="btn btn-secondary " data-dismiss="modal">Close</button>
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


        $('.money').mask("###0.00", {reverse: true})

        $('.reportError').click(function(){
            var order_id = $(this).data('order')
            var client_id = $(this).data('client')
            $("input[name='order_id']").val(order_id)
            $("input[name='client_id']").val(client_id)
        })

        $('.approveDeposit').click(function(){
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


        $('.update').click(function(){
            var fixed = $(this).data('fixed')
            var percent = $(this).data('percent')
            var status = $(this).data('status')
            var solicitation = $(this).data('solicitation')
            var final = $(this).data('final')
            var id = $(this).data('id')

            $('.comission').val(fixed+percent)
            $('.percent_fee').val(percent)
            $('.fixed_fee').val(fixed)
            $('.status').val(status)
            $('.final_amount').val(final)
            $('.amount_solicitation').val(solicitation)
            $('.id').val(id)


        })

        $('#formError').submit(function(e){
            e.preventDefault();
            data = $(this).serialize();

            Swal.fire({
                title: 'Are you sure?',
                text: "Do you really want to cancel this deposit?",
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
                        url:"{{switchUrl('approveDeposit/cancel')}}",
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


        $('#deposit').submit(function(e){
            e.preventDefault();
            data = $(this).serialize();

            console.log(data);

            Swal.fire({
                title: 'Are you sure?',
                text: "Do you really want to approve this deposit?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, aprove it!'
            }).then((result) => {
                if (result.value) {

                    $("#approveDeposit").modal("hide");
                    $('#load').show();

                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });

                    $.ajax({
                        url:"{{switchUrl('approveDeposit/aproove')}}",
                        method:"POST",
                        dataType:"json",
                        data:data,
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

                }
            })

        })


        $('#update-transaction').submit(function(e){
            e.preventDefault();
            data = $(this).serialize();

            console.log(data);


            Swal.fire({
                title: 'Are you sure?',
                text: "Do you really want to update this transaction?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, updated it!'
            }).then((result) => {
                if (result.value) {

                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });

                    $.ajax({
                        url:"{{switchUrl('approveDeposit/update')}}",
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



    });

</script>

@endsection
