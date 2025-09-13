<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NexaPay | Dashboard</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{asset('js/plugins/fontawesome-free/css/all.min.css')}}">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- Tempusdominus Bootstrap 4 -->
    <link rel="stylesheet" href="{{asset('js/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css')}}">
    <!-- iCheck -->
    <link rel="stylesheet" href="{{asset('js/plugins/icheck-bootstrap/icheck-bootstrap.min.css')}}">
    <!-- JQVMap -->
    <link rel="stylesheet" href="{{asset('js/plugins/jqvmap/jqvmap.min.css')}}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{asset('js/dist/css/adminlte.min.css')}}">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="{{asset('js/plugins/overlayScrollbars/css/OverlayScrollbars.min.css')}}">
    <!-- Daterange picker -->
    <link rel="stylesheet" href="{{asset('js/plugins/daterangepicker/daterangepicker.css')}}">

    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">

    <!-- summernote -->
    <link rel="stylesheet" href="{{asset('js/plugins/summernote/summernote-bs4.min.css')}}">
    <link rel="stylesheet" href="{{asset('css/style.css')}}">

    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/8.11.8/sweetalert2.css">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.2.2/css/fixedHeader.bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" />


    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">

    <link rel="shortcut icon" href="{{asset('img/favicon.png')}}">
    <link rel="icon" type="image/vnd.microsoft.icon" href="{{asset('img/favicon.png')}}">
    <link rel="icon" type="image/x-icon" href="{{asset('img/favicon.png')}}">
    <link rel="icon" href="{{asset('img/favicon.png')}}">
    <link rel="icon" type="image/gif" href="{{asset('img/favicon.png')}}">
    <link rel="icon" type="image/png" href="{{asset('img/favicon.png')}}">
    <link rel="icon" type="image/svg+xml" href="{{asset('img/favicon.png')}}">
    <style type="text/css">
        .search-title strong.text-light{
            color: #27a644 !important;
        }

    </style>
  @yield("css")
</head>
<body class="hold-transition sidebar-mini @if(auth()->user()->theme == 'light') light-mode @else dark-mode @endif">

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

        $permition = auth()->user()->especificPermition();
    @endphp

    <div class="wrapper">

        <!-- Preloader -->
        <div class="preloader flex-column justify-content-center align-items-center">
            <img class="animation__shake" src="{{asset('img/nexapay-color.png')}}" alt="NexaPay">
        </div>

        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand @if(auth()->user()->theme == 'light') navbar-white navbar-light @else navbar-black navbar-dark @endif">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link barclick" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="/" class="nav-link">Home</a>
                </li>
            </ul>

            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
            <!-- Navbar Search -->

            <li class="nav-item">
                <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                <i class="fas fa-expand-arrows-alt"></i>
                </a>
            </li>

            @if(Auth::user()->level == 'master')
                <li class="nav-item">
                    <a class="nav-link createWithdrawPixCelcoin" href="#" data-toggle="modal" data-target="#createWithdrawPixCelcoin" role="button">
                        <i class="fas fa-hand-holding-usd"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ switchUrl('/user/2fa') }}" role="button">
                        <i class="fas fa-fingerprint"></i>
                    </a>
                </li>
            @endif

            <li class="nav-item">
                <a class="nav-link" href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </li>
            </ul>
        </nav>
        <!-- /.navbar -->
        <form method="POST" id="logout-form" action="{{ route('logout') }}">
            @csrf
        </form>

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar @if(auth()->user()->theme == 'light') sidebar-light-primary @else sidebar-dark-primary @endif  elevation-4">
            <!-- Brand Logo -->

            <a href="{{switchUrl('/dashboard')}}" class="brand-link">
                <img src="{{asset('img/fast-payments-menu.png')}}" alt="NexaPay" style="width:220px;">
            </a>

            <!-- Sidebar -->
            <div class="sidebar">

                <br/>

                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                        @php
                            $route = \Request::route()->getName();
                            $withdrawal_list = ['withdrawal.index','withdrawal.create','withdrawal.store','withdrawal.show','withdrawal.edit','withdrawal.update','withdrawal.destroy','solicitation-withdrawal'];
                        @endphp

                        @if(auth()->user()->level == 'master')
                            <li class="nav-item menu-open">
                                <a href="{{switchUrl('/dashboard')}}" class="nav-link @if($route == "dashboard") active @endif">
                                    <p>Dashboard</p>
                                    <i class="nav-icon fas fa-tachometer-alt float-right"></i>
                                </a>
                            </li>
                        @elseif(auth()->user()->level == 'merchant')
                            @if($permition['permition'] == "yes")
                                <li class="nav-item menu-open">
                                    <a href="{{switchUrl('/dashboard')}}" class="nav-link @if($route == "dashboard") active @endif">
                                        <p>Dashboard</p>
                                        <i class="nav-icon fas fa-tachometer-alt float-right"></i>
                                    </a>
                                </li>
                            @endif
                        @elseif(auth()->user()->level == 'crypto')
                            <li class="nav-item menu-open">
                                <a href="{{switchUrl('/dashboard')}}" class="nav-link @if($route == "dashboard") active @endif">
                                    <p>Dashboard</p>
                                    <i class="nav-icon fas fa-tachometer-alt float-right"></i>
                                </a>
                            </li>
                        @endif

                        @if(auth()->user()->level == 'crypto')
                            <li class="nav-item menu-open">
                                <div class="card">
                                    <div class="card-body">
                                        <b>BANK DATA</b><br/>
                                        <small><b>BANK CODE:</b> 509</small><br/>
                                        <small><b>AG:</b> {{ auth()->user()->client->bankPix->agency }}</small>&nbsp;&nbsp;
                                        <small><b>CC:</b> {{ auth()->user()->client->bankPix->account }}</small><br/>
                                        <small><b>PIX KEY:</b><br/>
                                        <input type="text" class="form-control" id="pixkey" onclick="copyToClipboard()" readonly style="cursor:pointer;" value="{{ auth()->user()->client->bankPix->pixkey }}"/>
                                        <center><small><b style="color:red;">click on key to copy</b></small></center>
                                    </div>
                                </div>
                            </li>
                        @endif

                        @if(auth()->user()->level != 'crypto')
                        <li class="nav-item menu-open">
                            <a href="{{switchUrl('/transactions')}}" class="nav-link @if($route == "transactions") active @endif">
                                <p>Transactions</p>
                                <i class="nav-icon fas fa-server float-right"></i>
                            </a>
                        </li>
                        @endif
                        @if(Auth::user()->level == 'master')
                        <li class="nav-item menu-open">
                            <a href="{{switchUrl('/merchants')}}" class="nav-link @if($route == "merchants") active @endif">
                                <p>Merchant</p>
                                <i class="nav-icon fas fa-people-arrows float-right"></i>
                            </a>
                        </li>
                        @elseif(Auth::user()->level == 'merchant')
                        <li class="nav-item menu-open">
                            <a href="{{switchUrl('/myinfo')}}" class="nav-link @if($route == "merchants") active @endif">
                                <p>Merchant</p>
                                <i class="nav-icon fas fa-people-arrows float-right"></i>
                            </a>
                        </li>
                        @endif
                        @if(Auth::user()->level == 'master')
                        <li class="nav-item menu-open">
                            <a href="{{switchUrl('/withdrawal')}}" class="nav-link @if(in_array($route,$withdrawal_list)) active @endif">
                                <p>Withdrawal</p>
                                <i class="nav-icon fas fa-file-invoice-dollar float-right"></i>
                            </a>
                        </li>
                        @elseif(Auth::user()->level == 'merchant')
                        <li class="nav-item menu-open">
                            <a href="{{switchUrl('/solicitation-withdrawal')}}" class="nav-link @if(in_array($route,$withdrawal_list)) active @endif">
                                <p>Withdrawal</p>
                                <i class="nav-icon fas fa-file-invoice-dollar float-right"></i>
                            </a>
                        </li>
                        @endif

                        @if(Auth::user()->level == 'master')
                        <li class="nav-item menu-open">
                            <a href="{{switchUrl('/banks')}}" class="nav-link @if($route == "banks") active @endif">
                                <p>Bancos</p>
                                <i class="nav-icon fas fa-university float-right"></i>
                            </a>
                        </li>
                        @endif

                        @if(Auth::user()->level == 'merchant')
                        <li class="nav-item menu-open">
                            <a href="{{switchUrl('/clientapi')}}" class="nav-link @if($route == "api") active @endif">
                                <p>API</p>
                                <i class="nav-icon fas fa-key float-right"></i>
                            </a>
                        </li>
                        @endif
                        @if(Auth::user()->level != 'crypto')
                        <li class="nav-item menu-open">
                            <a href="{{switchUrl('/users')}}" class="nav-link @if(strpos($route,'users')  !== false) active @endif">
                                <p>Users</p>
                                <i class="nav-icon fas fa-users float-right"></i>
                            </a>
                        </li>

                        <li class="nav-item menu-open">
                            <a href="{{switchUrl('/logs')}}" class="nav-link @if($route == "logs") active @endif">
                                <p>Logs</p>
                                <i class="nav-icon fas fa-file-alt float-right"></i>
                            </a>
                        </li>
                        @endif
                    </ul>
                </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">

            {{-- <div class="alert alert-danger">
                IMPORTANTE! Estamos passando por um problema com o banco liquidante na retirada de saldo! Retornamos ASAP!
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div> --}}

            @yield("content")

        </div>

        @if(auth()->user()->level == "master")
        <!-- Modal -->
        <div class="modal fade" id="createWithdrawPixCelcoin" tabindex="-1" role="dialog" aria-labelledby="createWithdrawPixCelcoinTitle" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">BALANCE CELCOIN: <span class="balanceCelcoin" style="color:#28a311;"></span></h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>

                        <form id="formcreateWithdrawPixCelcoin" method="post">
                            <div  class="modal-body">
                                <div class="row">
                                    <div class="col-md-4 margin15">
                                        Bank<br/>
                                        <select name="bank_pix_withdraw" id="bank_pix_withdraw" class='form-control bank_pix_withdraw_celcoin'>
                                        @php
                                            $banks = \App\Models\Banks::where("code","587")->get();
                                        @endphp
                                        @foreach($banks as $bank)
                                            <option value="{{ $bank->id }}">{{$bank->name.' - Ag:'.$bank->agency.' - CC:'.$bank->account.' - '.$bank->holder}}</option>
                                        @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 margin15">
                                        PIX KEY<br/>
                                        <input type="text" id="pix_key_celcoin" name="pix_key_celcoin" class="form-control pix_key_celcoin" style="width:100%;">
                                    </div>
                                    <div class="col-md-4 margin15">
                                        REQUESTED AMOUNT<br>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <div class="input-group-text">BRL</div>
                                            </div>
                                            <input required name="amount_solicitation" type="text" value="" required="" class="form-control money_pix width100 text-left amount_solicitation_withdrawal_pix_celcoin" style="text-align:right;" placeholder="Amount BRL" maxlength="22">
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
        @endif

        <!-- Modal -->
        <div class="modal fade" id="modalReceipt" tabindex="-1" role="dialog" aria-labelledby="modalReceiptTitle" aria-hidden="true">
            <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-content">
                        <div  class="modal-body">
                            <div class="contentReceipt" style="text-align:center;">

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- /.content-wrapper -->
        <footer class="main-footer">
            <strong>Copyright &copy; <?=date("Y");?> NexaPay.</strong>
            All rights reserved.
            <div class="float-right d-none d-sm-inline-block">
            <b>Version</b> 1.0.0
            </div>
        </footer>

        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
        <!-- /.control-sidebar -->
    </div>
    <!-- ./wrapper -->

    <!-- jQuery -->
    <script src="{{asset('js/plugins/jquery/jquery.min.js')}}"></script>
    <!-- jQuery UI 1.11.4 -->
    <script src="{{asset('js/plugins/jquery-ui/jquery-ui.min.js')}}"></script>
    <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
    <script>
    $.widget.bridge('uibutton', $.ui.button)
    </script>
    <!-- Bootstrap 4 -->
    <script src="{{asset('js/plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
    <!-- ChartJS -->
    <script src="{{asset('js/plugins/chart.js/Chart.min.js')}}"></script>
    <!-- Sparkline -->
    <script src="{{asset('js/plugins/sparklines/sparkline.js')}}"></script>
    <!-- JQVMap -->
    <script src="{{asset('js/plugins/jqvmap/jquery.vmap.min.js')}}"></script>
    <script src="{{asset('js/plugins/jqvmap/maps/jquery.vmap.usa.js')}}"></script>
    <!-- jQuery Knob Chart -->
    <script src="{{asset('js/plugins/jquery-knob/jquery.knob.min.js')}}"></script>
    <!-- daterangepicker -->
    <script src="{{asset('js/plugins/moment/moment.min.js')}}"></script>
    <script src="{{asset('js/plugins/daterangepicker/daterangepicker.js')}}"></script>
    <!-- Tempusdominus Bootstrap 4 -->
    <script src="{{asset('js/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js')}}"></script>
    <!-- Summernote -->
    <script src="{{asset('js/plugins/summernote/summernote-bs4.min.js')}}"></script>
    <!-- overlayScrollbars -->
    <script src="{{asset('js/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js')}}"></script>
    <!-- AdminLTE App -->
    <script src="{{asset('js/dist/js/adminlte.js')}}"></script>
    <!-- AdminLTE for demo purposes -->
    <script src="{{asset('js/dist/js/demo.js')}}"></script>
    <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
    @if($route == "dashboard") <script src="{{asset('js/dist/js/pages/dashboard.js')}}"></script> @endif
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <!-- Latest compiled and minified JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>

    <!-- (Optional) Latest compiled and minified JavaScript translation files -->
    {{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/i18n/defaults-*.min.js"></script> --}}

    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/fixedheader/3.2.2/js/dataTables.fixedHeader.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@9"></script>
    <script type="text/javascript">

            function copyToClipboard() {
                // Get the text field
                var copyText = document.getElementById("pixkey");

                // Select the text field
                copyText.select();
                copyText.setSelectionRange(0, 99999); // For mobile devices

                // Copy the text inside the text field
                navigator.clipboard.writeText(copyText.value);

                alert("Copied: " + copyText.value);
            }

        $(document).ready(function() {

            $('.money_pix').mask('#.##0,00', {reverse: true, maxlength: false});

            $(".createWithdrawPixCelcoin").click(function() {

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                $.ajax({
                    url:"{{switchUrl('banks/getBalanceCelcoin')}}",
                    method:"GET",
                    dataType:"json",
                    success:function(response){
                        console.log(response);

                        $(".balanceCelcoin").html(response.balance);
                    },
                    error:function(err){
                        console.log(err);
                    }
                });

            });

            $('#formcreateWithdrawPixCelcoin').submit(function(e){
                e.preventDefault();
                // var data = $(this).serialize();

                var amount_solicitation = $(".amount_solicitation_withdrawal_pix_celcoin").val();
                var pix_key = $(".pix_key_celcoin").val();
                var bank_pix_withdraw = $(".bank_pix_withdraw_celcoin").val();

                var data = {
                    pix_key : pix_key,
                    amount_solicitation : amount_solicitation,
                    bank_pix_withdraw : bank_pix_withdraw,
                };

                Swal.fire({
                    title: 'Are you sure?',
                    text: "Do you really want to execute this withdraw?",
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
                            url:"{{switchUrl('withdrawal/createWithdrawCelcoin')}}",
                            method:"POST",
                            dataType:"json",
                            data:data,
                            success:function(response){
                                // console.log(response);

                                $('#createWithdrawPixCelcoin').modal('hide');

                                Swal.fire(
                                    'success!',
                                    "Withdraw Executed Successfully",
                                    'success'
                                );

                                // let content = JSON.parse(response);
                                $(".contentReceipt").html(response.slip);
                                $('#modalReceipt').modal('show');

                                // location.reload();
                            },
                            error:function(err){
                                console.log(err);
                            }
                        });

                    }
                })
            });

        });

    </script>
    @yield("js")

</body>
</html>
