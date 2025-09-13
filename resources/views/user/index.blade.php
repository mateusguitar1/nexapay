@extends('layouts.appdash')

@section('content')
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
<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">

        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-11">
                    <h1 class="m-0">Merchants</h1>
                </div><!-- /.col -->
                <div class="col-sm-1">
                    <a class='btn btn-primary' href="{{switchUrl('users/create')}}" style="padding:7px 10px;width:100%;">
                        <i class="fa fa-users"></i> New User
                    </a>
                </div><!-- /.col -->
            </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->

        <div class="row "style='margin:0px' >
            <div class="col-md-12" style='padding:0px'>
                <div class="card">

                    <!-- Tabs -->
                    <ul class="nav nav-tabs center" id="myTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="home-tab" data-toggle="tab" href="#home" role="tab" aria-controls="home" aria-selected="true">Actives Users ({{$data['count_actives']}})</a>
                        </li>
                    </ul>
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                            <div class="card-body">
                                <table class='table'>
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Level</th>
                                            <th class="text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($data['users'] as $user)
                                        <tr>
                                            <td>{{$user->client ?$user->client->name : ' - ' }}</td>
                                            <td>{{$user->name}}</td>
                                            <td>{{$user->email}}</td>
                                            <td>{{ strtoupper($user->level) }}</td>
                                            <td class="text-right">
                                                @if(Auth::user()->level == 'master')
                                                    <a class="btn btn-primary" href='{{ url("users/$user->id/edit") }}'><i class="fa fa-edit pr-2" aria-hidden='true'></i>Edit</a>
                                                @else
                                                    @if(in_array(6,$permitions))
                                                        <a class="btn btn-primary" href='{{ url("users/$user->id/edit") }}'><i class="fa fa-edit pr-2" aria-hidden='true'></i>Edit</a>
                                                    @elseif($user->id == Auth::user()->id)
                                                        <a class="btn btn-primary" href='{{ url("users/$user->id/edit") }}'><i class="fa fa-edit pr-2" aria-hidden='true'></i>Edit</a>
                                                    @endif
                                                @endif
                                                @if(in_array(7,$permitions))
                                                    <a class="btn btn-primary freeze" href="#!"  data-status="{{$user->freeze}}" data-name="{{$user->name}}" data-user="{{$user->id}}"><i class="fas fa-snowflake pr-2" aria-hidden='true'></i>{{$user->freeze == '0' ? 'Freeze' : 'Unfreeze'}}</a>
                                                @endif
                                                </form>
                                            </td>
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
    </div>
</div>
@endsection
@section('js')

<script type="text/javascript">
    $( document ).ready(function() {
        $('.freeze').click(function(){
            var user_id = $(this).data('user');
            var name = $(this).data('name');
            var freeze = $(this).data('freeze');
            if(freeze == 1){
                var action = 'unfreeze'
            }else{
                var action = 'freeze'
            }
            // console.log(user_id)
            Swal.fire({
                title: 'Are you sure?',
                text: "Do you want "+action+' user '+name,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes!'
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: "{{switchUrl('user')}}"+'/'+user_id+'/freeze',
                        mathod:"GET",
                        dataType: "json",
                        data:{},
                        success:function(response){
                            // console.log(response)
                            Swal.fire(
                                response.status+'!',
                                response.message,
                                response.status
                            )
                            setTimeout(function(){ location.reload(); }, 1500);
                        },
                        error:function(e){
                            console.log(e);
                        }
                    });
                }
            })

        })
    });
</script>

@endsection
