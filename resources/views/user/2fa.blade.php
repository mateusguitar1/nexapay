@extends('layouts.appdash')
@section('style-import')
<link href="{{ asset('css/custom.css') }}" rel="stylesheet">
@endsection

@section('content')
    @section('css')
        <style type="text/css">
        .margin15{
            margin-bottom:15px;
        }
        * {box-sizing: border-box}
        /* Style the tab */
        .tab {
        float: left;
        background-color: #fafafa;
        width: 30%;
        min-height: 246px;
        }

        /* Style the buttons inside the tab */
        .tab button {
        display: block;
        background-color: #fafafa;
        color: #666;
        padding: 10px;
        width: 100%;
        border: none;
        outline: none;
        cursor: pointer;
        transition: 0.3s;
        font-size: 14px;
        text-align:center;
        }

        /* Change background color of buttons on hover */
        .tab button:hover {
        background-color: #be70e2;
        color:#FFF;
        }

        /* Create an active/current "tab button" class */
        .tab button.active {
        background-color: #a348cc;
        color:#FFF;
        }

        /* Style the tab content */
        .tabcontent {
        float: left;
        padding: 15px 30px;
        width: 70%;
        border-left: none;
        min-height: 246px;
        background: #FFF;
        }
        h6.title{
            margin-bottom:30px;
            font-size:14px;
            color:#888;
            text-align: center;
        }

        /* The customcheck */
        .customcheck {
            display: block;
            position: relative;
            padding-left: 35px;
            margin-bottom: 12px;
            cursor: pointer;
            font-size: 16px;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            color: #343a40;
        }

        /* Hide the browser's default checkbox */
        .customcheck input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        /* Create a custom checkbox */
        .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            height: 20px;
            width: 20px;
            background-color: #eee;
            border-radius: 5px;
            margin-top: 3px;
        }

        /* On mouse-over, add a grey background color */
        .customcheck:hover input ~ .checkmark {
            background-color: #ccc;
        }

        /* When the checkbox is checked, add a blue background */
        .customcheck input:checked ~ .checkmark {
            background-color: #be70e2;
            border-radius: 5px;
            margin-top: 3px;
        }

        /* Create the checkmark/indicator (hidden when not checked) */
        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }

        /* Show the checkmark when checked */
        .customcheck input:checked ~ .checkmark:after {
            display: block;
        }

        /* Style the checkmark/indicator */
        .customcheck .checkmark:after {
            left: 8px;
            top: 4px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 3px 3px 0;
            -webkit-transform: rotate(45deg);
            -ms-transform: rotate(45deg);
            transform: rotate(45deg);
        }
        </style>
    @endsection
    <br/>
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

    @if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    <div class="row" style="margin-right: 0px;margin-left: 0px;">
        @if(in_array(6,$permitions))
            <div class="col-md-6 margin15">
        @else
            <div class="col-md-12 margin15">
        @endif
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-sm-12">{{$data['title']}}</div>
                    </div>
                </div>
                    {{ csrf_field() }}
                    <div class="card-body" style="min-height:246px;">

                        <div class="row">

                            <div class="col-sm-12 mb-4 margin15">
                                <div class="md-form">
                                    <label for="name">Name</label>
                                    <input type="text" name="name_show" id="name_show" readonly value="{{ auth()->user()->name }}" class='form-control'>
                                </div>
                            </div>

                            <div class="col-sm-6 mb-4 margin15">
                                <div class="md-form">
                                    <label for="email">Email</label>
                                    <input type="email" name="email_show" id="email_show" readonly value="{{ auth()->user()->email }}" class='form-control'>
                                </div>
                            </div>

                            <div class="col-sm-4 mb-4 margin15">
                                <div class="md-form">
                                    <label for="level">Level</label>
                                    <input type="text" class="form-control" readonly value="{{ strtoupper(auth()->user()->level) }}">
                                </div>
                            </div>

                        </div>

                    </div>
            </div>
        </div>
        @if(auth()->user()->secret === null)
            <div class="col-md-6 margin15">
                <form method="POST" action="{{ switchUrl('/user/save2fa') }}">
                    {{ csrf_field() }}
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-sm-6">
                                    @if(auth()->user()->secret === null)
                                        Register your 2fa password
                                    @else
                                        Your 2fa code has been registered!
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="card-body" style="padding:15px !important;margin:0 !important;">
                            <div class="row" style="padding:15px;">
                                <div class="col-md-6">
                                    <center>
                                        <img src="{{ $data['2faurl'] }}" />
                                        <br/>
                                        <div style="padding:5px;margin-top:10px;border:1px solid #dedede;background-color:#f3f3f3;font-weight:bold;">{{ $data['secret'] }}</div>
                                    </center>
                                </div>
                                <div class="col-md-6">
                                    <div class="col-sm-12 mb-4 margin15">
                                        <div class="md-form">
                                            <label for="code">2FA CODE</label>
                                            <input type="text" name="code" id="code" value="" maxlength="6" class='form-control'>
                                        </div>
                                    </div>

                                    <div class="col-sm-12 mb-4 margin15">
                                        <div class="md-form">
                                            <label for="password">Password</label>
                                            <input type="password" name="password" id="password" value="" class='form-control'>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 margin15" style="padding:15px 8px;">
                                    <button style='width:100%' type="submit" class="btn btn-success pull-right width-100"><i class="fa fa-save pr-2" aria-hidden="true"></i>Register</button>
                                </div>
                                <input type="text" name="secret" value="{{ $data['secret'] }}" />
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        @else
            <div class="col-md-6 margin15">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-sm-6">
                                @if(auth()->user()->secret === null)
                                    Register your 2fa password
                                @else
                                    <p>Your 2fa code has been registered!</p>
                                    <p>If an exchange is necessary, contact support!</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>

    <script type="text/javascript">
    function openCity(evt, cityName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(cityName).style.display = "block";
    evt.currentTarget.className += " active";
    }
    document.getElementById("defaultOpen").click();
    </script>

@endsection
@section('js')
    <script type="text/javascript">

        $( document ).ready(function() {
            $('.select-all').click(function(){
                $("input[type='checkbox']").prop( "checked", true )
            })
        })
    </script>
@endsection
