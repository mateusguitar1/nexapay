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
    <form action="{{$data['url']}}" method="POST">
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
                        @if($data['model'])
                            @method('PUT')
                        @endif
                        <div class="card-body" style="min-height:246px;">

                            <div class="row">

                                <div class="col-sm-12 mb-12 margin15">
                                    <div class="md-form">
                                        <label for="name">Name</label>
                                        <input type="text" name="admin[name]" id="name" value="{{ $data['model'] ? $data['model']->name : old('name', '') }}" class='form-control'>
                                    </div>
                                </div>

                                <div class="col-sm-6 mb-6 margin15">
                                    <div class="md-form">
                                        <label for="email">Email</label>
                                        <input type="email" name="admin[email]" id="email" value="{{ $data['model'] ? $data['model']->email : old('email', '') }}" class='form-control'>
                                    </div>
                                </div>

                                <div class="col-sm-6 mb-6 margin15">
                                    <div class="md-form">
                                        <label for="password">Password</label>
                                        <input type="password" name="admin[password]" id="password" value="{{ $data['model'] ? '' : old('', '') }}" class='form-control'>
                                    </div>
                                </div>

                                @if(Auth::user()->level == 'master')
                                    <div class="col-sm-4 mb-6 margin15">
                                        <div class="md-form">
                                            <label for="level">Level</label>
                                            <select class="form-control" name="admin[level]" id="level">
                                                <option {{$data['model'] && $data['model']->level == 'master' ? 'selected' : '' }} value="master">Master</option>
                                                <option {{$data['model'] && $data['model']->level == 'merchant' ? 'selected' : '' }} value="merchant">Merchant</option>
                                                <option {{$data['model'] && $data['model']->level == 'payment' ? 'selected' : '' }} value="payment">Payment</option>
                                                <option {{$data['model'] && $data['model']->level == 'crypto' ? 'selected' : '' }} value="crypto">Crypto</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-sm-4 mb-6 margin15">
                                        <div class="md-form">
                                            <label for="client_id">Client</label>
                                            <select class="form-control" name="admin[client_id]" id="client_id">
                                                <option selected value="">None</option>
                                                @foreach($data['clients'] as $client)
                                                <option {{$data['model'] && $data['model']->client_id == $client->id ? 'selected' : '' }} value="{{$client->id}}">{{$client->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-sm-4 mb-6 margin15">
                                        <div class="md-form">
                                            <label for="theme">Theme</label>
                                            <select class="form-control" name="admin[theme]" id="theme">
                                                <option {{$data['model'] && $data['model']->theme == 'light' ? 'selected' : '' }} value="light">Light</option>
                                                <option {{$data['model'] && $data['model']->theme == 'dark' ? 'selected' : '' }} value="dark">Dark</option>
                                            </select>
                                        </div>
                                    </div>
                                @else
                                    <input type="hidden" name="admin[client_id]" value="{{ Auth::user()->client_id }}">
                                    <input type="hidden" name="admin[level]" value="merchant">
                                @endif

                            </div>

                        </div>
                </div>
            </div>
            @if(in_array(6,$permitions))
            <div class="col-md-6 margin15">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-sm-6">Choose the options available to the user</div>
                            <div class="col-sm-6 text-right">
                                <button type='button' class='btn btn-sm btn-primary select-all'>Select All</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body" style="padding:0 !important;margin:0 !important;">
                        <div class="tab">
                            <button type="button" class="tablinks" onclick="openCity(event, 'dashboard')" id="defaultOpen">DASHBOARD</button>
                            <button type="button" class="tablinks" onclick="openCity(event, 'transactions')">TRANSACTIONS</button>
                            <button type="button" class="tablinks" onclick="openCity(event, 'infoclient')">INFOS CLIENTS</button>
                            <button type="button" class="tablinks" onclick="openCity(event, 'createuser')">CREATE USERS</button>
                            <button type="button" class="tablinks" onclick="openCity(event, 'pageapi')">PAGE API</button>
                            <button type="button" class="tablinks" onclick="openCity(event, 'pagelist')">PAGE LIST</button>
                        </div>

                        <div id="dashboard" class="tabcontent">
                            <label class="customcheck"><?php print_r($data['permitions'][16]['title']); ?>
                                <input type="checkbox" name="permitions[showdash][]" value="<?php print_r($data['permitions'][16]['id']); ?>" <?php if(isset($data['permitions_set'])){ if(in_array($data['permitions'][16]['id'],$data['permitions_set'])){ ?>checked=""<?php }} ?>>
                                <span class="checkmark"></span>
                            </label>
                        </div>

                        <div id="transactions" class="tabcontent">
                            <label class="customcheck"><?php print_r($data['permitions'][7]['title']); ?>
                                <input type="checkbox" name="permitions[date][]" value="<?php print_r($data['permitions'][7]['id']); ?>" <?php if(isset($data['permitions_set'])){ if(in_array($data['permitions'][7]['id'],$data['permitions_set'])){ ?>checked=""<?php }} ?>>
                                <span class="checkmark"></span>
                            </label>

                            <label class="customcheck"><?php print_r($data['permitions'][8]['title']); ?>
                                <input type="checkbox" name="permitions[orderid][]" value="<?php print_r($data['permitions'][8]['id']); ?>" <?php if(isset($data['permitions_set'])){ if(in_array($data['permitions'][8]['id'],$data['permitions_set'])){ ?>checked=""<?php }} ?>>
                                <span class="checkmark"></span>
                            </label>

                            <label class="customcheck"><?php print_r($data['permitions'][9]['title']); ?>
                                <input type="checkbox" name="permitions[method][]" value="<?php print_r($data['permitions'][9]['id']); ?>" <?php if(isset($data['permitions_set'])){ if(in_array($data['permitions'][9]['id'],$data['permitions_set'])){ ?>checked=""<?php }} ?>>
                                <span class="checkmark"></span>
                            </label>

                            <label class="customcheck"><?php print_r($data['permitions'][10]['title']); ?>
                                <input type="checkbox" name="permitions[bank][]" value="<?php print_r($data['permitions'][10]['id']); ?>" <?php if(isset($data['permitions_set'])){ if(in_array($data['permitions'][10]['id'],$data['permitions_set'])){ ?>checked=""<?php }} ?>>
                                <span class="checkmark"></span>
                            </label>

                            <label class="customcheck"><?php print_r($data['permitions'][11]['title']); ?>
                                <input type="checkbox" name="permitions[userid][]" value="<?php print_r($data['permitions'][11]['id']); ?>" <?php if(isset($data['permitions_set'])){ if(in_array($data['permitions'][11]['id'],$data['permitions_set'])){ ?>checked=""<?php }} ?>>
                                <span class="checkmark"></span>
                            </label>

                            <label class="customcheck"><?php print_r($data['permitions'][12]['title']); ?>
                                <input type="checkbox" name="permitions[type][]" value="<?php print_r($data['permitions'][12]['id']); ?>" <?php if(isset($data['permitions_set'])){ if(in_array($data['permitions'][12]['id'],$data['permitions_set'])){ ?>checked=""<?php }} ?>>
                                <span class="checkmark"></span>
                            </label>

                            <label class="customcheck"><?php print_r($data['permitions'][13]['title']); ?>
                                <input type="checkbox" name="permitions[amount][]" value="<?php print_r($data['permitions'][13]['id']); ?>" <?php if(isset($data['permitions_set'])){ if(in_array($data['permitions'][13]['id'],$data['permitions_set'])){ ?>checked=""<?php }} ?>>
                                <span class="checkmark"></span>
                            </label>

                            <label class="customcheck"><?php print_r($data['permitions'][14]['title']); ?>
                                <input type="checkbox" name="permitions[fees][]" value="<?php print_r($data['permitions'][14]['id']); ?>" <?php if(isset($data['permitions_set'])){ if(in_array($data['permitions'][14]['id'],$data['permitions_set'])){ ?>checked=""<?php }} ?>>
                                <span class="checkmark"></span>
                            </label>

                            <label class="customcheck"><?php print_r($data['permitions'][15]['title']); ?>
                                <input type="checkbox" name="permitions[status][]" value="<?php print_r($data['permitions'][15]['id']); ?>" <?php if(isset($data['permitions_set'])){ if(in_array($data['permitions'][15]['id'],$data['permitions_set'])){ ?>checked=""<?php }} ?>>
                                <span class="checkmark"></span>
                            </label>

                        </div>

                        <div id="infoclient" class="tabcontent">
                            <label class="customcheck"><?php print_r($data['permitions'][1]['title']); ?>
                                <input type="checkbox" name="permitions[infoclient][]" value="<?php print_r($data['permitions'][1]['id']); ?>" <?php if(isset($data['permitions_set'])){ if(in_array($data['permitions'][1]['id'],$data['permitions_set'])){ ?>checked=""<?php }} ?>>
                                <span class="checkmark"></span>
                            </label>
                        </div>

                        <div id="createuser" class="tabcontent">
                            <label class="customcheck"><?php print_r($data['permitions'][2]['title']); ?>
                                <input type="checkbox" name="permitions[createuser][]" value="<?php print_r($data['permitions'][2]['id']); ?>" <?php if(isset($data['permitions_set'])){ if(in_array($data['permitions'][2]['id'],$data['permitions_set'])){ ?>checked=""<?php }} ?>>
                                <span class="checkmark"></span>
                            </label>
                        </div>

                        <div id="pageapi" class="tabcontent">
                            <label class="customcheck"><?php print_r($data['permitions'][3]['title']); ?>
                                <input type="checkbox" name="permitions[pageapi][]" value="<?php print_r($data['permitions'][3]['id']); ?>" <?php if(isset($data['permitions_set'])){ if(in_array($data['permitions'][3]['id'],$data['permitions_set'])){ ?>checked=""<?php }} ?>>
                                <span class="checkmark"></span>
                            </label>
                            <label class="customcheck"><?php print_r($data['permitions'][4]['title']); ?>
                                <input type="checkbox" name="permitions[apikeys][]" value="<?php print_r($data['permitions'][4]['id']); ?>" <?php if(isset($data['permitions_set'])){ if(in_array($data['permitions'][4]['id'],$data['permitions_set'])){ ?>checked=""<?php }} ?>>
                                <span class="checkmark"></span>
                            </label>
                            <label class="customcheck"><?php print_r($data['permitions'][5]['title']); ?>
                                <input type="checkbox" name="permitions[addupdkeys][]" value="<?php print_r($data['permitions'][5]['id']); ?>" <?php if(isset($data['permitions_set'])){ if(in_array($data['permitions'][5]['id'],$data['permitions_set'])){ ?>checked=""<?php }} ?>>
                                <span class="checkmark"></span>
                            </label>
                            <label class="customcheck"><?php print_r($data['permitions'][6]['title']); ?>
                                <input type="checkbox" name="permitions[downloadjson][]" value="<?php print_r($data['permitions'][6]['id']); ?>" <?php if(isset($data['permitions_set'])){ if(in_array($data['permitions'][6]['id'],$data['permitions_set'])){ ?>checked=""<?php }} ?>>
                                <span class="checkmark"></span>
                            </label>
                        </div>

                        <div id="pagelist" class="tabcontent">
                            <label class="customcheck"><?php print_r($data['permitions'][0]['title']); ?>
                                <input type="checkbox" name="permitions[exportlist][]" value="<?php print_r($data['permitions'][0]['id']); ?>" <?php if(isset($data['permitions_set'])){ if(in_array($data['permitions'][0]['id'],$data['permitions_set'])){ ?>checked=""<?php }} ?>>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="col-md-12">
                <div class="row">

                    <div class="col-sm-3 margin15">
                        <a href="{{switchUrl('users')}}" style='width:100%' class="btn btn-light"><i class="fa fa-undo pr-2" aria-hidden="true"></i>Back</a>
                    </div>

                    <div class="col-sm-3 offset-md-6 margin15">
                        <button style='width:100%' type="submit" class="btn btn-success pull-right width-100"><i class="fa fa-save pr-2" aria-hidden="true"></i>{{$data['button']}}</button>
                    </div>

                </div>
            </div>

        </div>
    </form>

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
