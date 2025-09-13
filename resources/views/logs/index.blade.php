@extends('layouts.appdash')

@section('css')
<meta name="csrf-token" content="{{ csrf_token() }}" />
@endsection

@section('content')

    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-11">
                <h1 class="m-0">Logs</h1>
            </div><!-- /.col -->
        </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <div class="content">
        <div class="container-fluid">

            <div class="card">

                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                        <div class="card-body" style='padding:15px !important;'>

                            <form action="{{switchUrl('logs/search')}}" method="post">
                            {{ csrf_field() }}
                                <div class="row">
                                    @if(auth()->user()->level == 'master')
                                    <div class="form-group col-sm-2">
                                        <label for="client">Client</label>
                                        <select name="client_id" class="form-control" id="client">
                                            <option value="">ALL</option>
                                            @foreach($data['clients'] as $client)
                                                <option  value="{{$client->id}}">{{$client->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @else
                                    <div class="form-group col-sm-2">
                                        <label for="client">Client</label>
                                        <select name="client_id" class="form-control" id="client">
                                            @foreach($data['clients'] as $client)
                                                @if(auth()->user()->client_id == $client->id)
                                                    <option  value="{{$client->id}}">{{$client->name}}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    @endif

                                    <div class="form-group col-sm-2">
                                        <label for="user">Users</label>
                                        <select name="user_id" class="form-control" id="user">
                                            <option value="">ALL</option>
                                            @foreach($data['users'] as $user)
                                                <option  value="{{$user->id}}">{{$user->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>


                                    <div class="form-group col-sm-2">
                                        <label for="type">Type</label>
                                        <select name="type" class="form-control" id="type">
                                            <option value="">ALL</option>
                                            <option value="system">System</option>
                                            <option value="add">Add</option>
                                            <option value="change">Change</option>
                                            <option value="freeze">Freeze</option>
                                            <option value="request">Request</option>
                                            <option value="blocklist">Block List</option>
                                            <option value="whitelist">White List</option>
                                            <option value="watchlist">Highlight</option>
                                        </select>
                                    </div>

                                    <div class="col-sm-3">
                                        <label for="date">DATE</label>
                                        <button type="button" class="btn btn-date" name="new_date" id="daterangepicker2" style="width:100%;">
                                            <i class="fa fa-calendar"></i>
                                            <span>{{date('F j, Y')}} - {{date('F j, Y')}}</span> <b class="caret"></b>
                                        </button>
                                        <input type="hidden" class="form-control" name="minall" placeholder="Start" id="minall"  autocomplete="off" />
                                        <input type="hidden" class="form-control" name="maxall" placeholder="End" id="maxall"  autocomplete="off" />
                                    </div>


                                    <div class="col-sm-1 offset-sm-2">
                                        <button style=" width: 100%; margin-top: 32px;" class='btn btn-success' type="submit"><i class="fa fa-search" aria-hidden="true"></i></button>
                                    </div>
                                </div>
                            </form>

                            <table id="table" class='table table-striped'>
                                <thead>
                                    <tr>
                                        @if(Auth::user()->level == 'master')
                                            <th>Client</th>
                                        @endif
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Type</th>
                                        <th>Action</th>
                                        <th class='text-right'>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($data['logs'] as $log)
                                    <tr>
                                        @if(Auth::user()->level == 'master')
                                            <td>
                                                @if($log->client_id != '')
                                                    {{$log->client->name}}
                                                @else
                                                    {{'-'}}
                                                @endif
                                            </td>
                                        @endif
                                        <td>@if(isset($log->user->name)) {{$log->user->name}} @else deleted @endif</td>
                                        <td>@if(isset($log->user->email)) {{$log->user->email}} @else deleted @endif</td>
                                        <td>{{$log->type}}</td>
                                        <td>{{$log->action}}</td>
                                        <td class='text-right'>{{date('d/m/Y H:i:s' ,strtotime($log->created_at))}}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')

<script>
    $( document ).ready(function() {
        $('#table').DataTable({
            "paging":   '50',
            "lengthChange": false,
            "ordering": false,
        });

        $('#daterangepicker2').daterangepicker({
            ranges: {
                'Today': [moment().subtract('days', 0), moment().subtract('days', 0)],
                'Yesterday': [moment().subtract('days', 1), moment().subtract('days', 1)],
                'Yesterday + Today': [moment().subtract('days', 1), moment()],
                'Last 7 Days': [moment().subtract('days', 6), moment()],
                'Last 30 Days': [moment().subtract('days', 29), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
            },
            opens: 'left',
            startDate: moment(),//.subtract('days', 29),
            endDate: moment()
            },
            function(start, end){
                $('#daterangepicker2 span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
                $("#minall").val(start.format('DD-MM-YYYY'));
                $("#maxall").val(end.format('DD-MM-YYYY'));
        });
    });
</script>
@endsection
