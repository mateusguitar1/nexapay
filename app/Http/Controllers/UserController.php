<?php

namespace App\Http\Controllers;

use \Google\Authenticator\GoogleQrUrl;
use \Google\Authenticator\FixedBitNotation;
use \Google\Authenticator\GoogleAuthenticator;
use \Google\Authenticator\GoogleAuthenticatorInterface;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\FunctionsController;
use \App\Models\{Extract,Clients,Transactions,Taxes,Keys,Banks,Logs,User,Whitelist,Blocklist,Permitions,UsersHasPermitions};

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        if(auth()->user()->level == 'merchant'){
            $users = User::where('client_id','=',auth()->user()->client_id)->get();
        }else{
            $users = User::all();
        }

        $count_actives = $users->where("freeze","=",false)->count();

        $data = [
            'users' => $users,
            'count_actives' => $count_actives,
        ];
        return view('user.index',compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        $data = [
            'model' => null,
            'clients' => Clients::all(),
            'permitions' => Permitions::all(),
            'title' => 'User Data',
            'url' => url('/users'),
            'button' => 'Save',
        ];

        return view('user.form',compact('data'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $check_user = User::where("email",$request->admin['email'])->first();

        if(isset($check_user)){

            return back()->with('error', 'Email already exists');

        }else{


            if(isset($request->admin['theme'])){
                $theme = $request->admin['theme'];
            }else{
                $theme = "light";
            }

            //
            DB::beginTransaction();
            try {
                $password = Hash::make($request->admin['password']);

                $user = User::create([
                    'name' => $request->admin['name'],
                    'email' => $request->admin['email'],
                    'level' => $request->admin['level'],
                    'client_id' => $request->admin['client_id'],
                    'password' => $password,
                    'theme' => $theme,
                ]);

                foreach($request->permitions as $key => $row){
                    if($row){
                        $permition = UsersHasPermitions::create([
                            "user_id" => $user->id,
                            "permition_id" => $row[0]
                        ]);
                    }
                }

                Logs::create([
                    'user_id' =>  auth()->user()->id,
                    'client_id' =>  $request->client_id,
                    'type' =>  'add',
                    'action' => 'User '.auth()->user()->name.' created new User: '.$user->name.'.',
                ]);

                DB::commit();

                return redirect('users')->with('success', 'user created sucessful');
            }catch (Exception $e) {
                DB::rollback();
                return back()->with('error', 'Server error');
            }

        }


    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        //
        if($user->haspermitions){
            $afpermitions = $user->haspermitions;
            $permitions = [];
            foreach($afpermitions as $row){
                array_push($permitions,$row->permition_id);
            }
        }else{
            $permitions = [];
        }

        if(auth()->user()->haspermitions){
            $logeedpermition = auth()->user()->haspermitions;
            $permitions_logged = [];
            foreach($logeedpermition as $row){
                array_push($permitions_logged,$row->permition_id);
            }
        }else{
            $permitions_logged = [];
        }

        if(auth()->user()->level != 'master'){
            if(auth()->user()->client_id != $user->client_id){
                return redirect('home')->with('warning', 'You are not authorized to access this page');
            }
            if(!in_array(6,$permitions_logged)){
                if($user->id != auth()->user()->id){
                    return redirect('home')->with('warning', 'You are not authorized to access this page');
                }
            }
        }

        $data = [
            'model' => $user,
            'clients' =>  Clients::all(),
            'permitions' => Permitions::orderBy("id", "ASC")->get(),
            'permitions_set' => $permitions,
            'title' => 'Edit User',
            'url' => url("/users/$user->id"),
            'button' => 'Edit',

        ];

        return view('user.form',compact('data'));
    }

    public function user2fa(User $user)
    {
        //
        if($user->haspermitions){
            $afpermitions = $user->haspermitions;
            $permitions = [];
            foreach($afpermitions as $row){
                array_push($permitions,$row->permition_id);
            }
        }else{
            $permitions = [];
        }

        if(auth()->user()->haspermitions){
            $logeedpermition = auth()->user()->haspermitions;
            $permitions_logged = [];
            foreach($logeedpermition as $row){
                array_push($permitions_logged,$row->permition_id);
            }
        }else{
            $permitions_logged = [];
        }

        if(auth()->user()->level != 'master'){
            if(auth()->user()->client_id != $user->client_id){
                return redirect('home')->with('warning', 'You are not authorized to access this page');
            }
            if(!in_array(6,$permitions_logged)){
                if($user->id != auth()->user()->id){
                    return redirect('home')->with('warning', 'You are not authorized to access this page');
                }
            }
        }

        $g = new GoogleAuthenticator();
        // $secret = 'XVQ2UIGO75XRUKJO';
        //Você pode usar o $g->generateSecret() para gerar o secret
        $secret = $g->generateSecret();

        //o método "getUrl" recebe como parâmetro: "username", "host" e a chave "secret"
        $url = $g->getURL($user->name, 'fastpayments.com.br', $secret);

        $data = [
            'model' => $user,
            'clients' =>  Clients::all(),
            'title' => '2FA User',
            '2faurl' => $url,
            'secret' => $secret,

        ];

        return view('user.2fa',compact('data'));
    }

    public function save2fa(Request $request){

        $user = User::where('id',auth()->user()->id)->first();

        if(!Hash::check($request->password, $user->password)){
            return back()->with('error', 'Password Incorrect');
        }

        $g = new GoogleAuthenticator();
        $secret = $request->secret;

        $code = $request->code;

        if($g->checkCode($secret, $code)){

            DB::beginTransaction();
            try{

                $user->update([
                    "secret" => $secret,
                ]);

                DB::commit();

                return redirect('/user/2fa')->with('success', '2fa code registered successfully');

            }catch(Exception $e){
                DB::rollBack();
            }

        }else{
            return back()->with('error', 'Code 2fa Incorrect');
        }

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        //
        if(isset($request->admin['theme'])){
            $theme = $request->admin['theme'];
        }else{
            $theme = "light";
        }

        DB::beginTransaction();
        try {

            if($request->admin['password'] != ''){
                $password = Hash::make($request->admin['password']);
                $user->update([
                    'name' => $request->admin['name'],
                    'email' => $request->admin['email'],
                    'level' => $request->admin['level'],
                    'client_id' => $request->admin['client_id'],
                    'password' => $password,
                    'theme' => $theme,
                ]);
            }else{
                $user->update([
                    'name' => $request->admin['name'],
                    'email' => $request->admin['email'],
                    'level' => $request->admin['level'],
                    'client_id' => $request->admin['client_id'],
                    'theme' => $theme,
                ]);
            }

            if($user->haspermitions){
                $delete = UsersHasPermitions::where("user_id","=",$user->id)->get();
                foreach($delete as $del){
                    $del->delete();
                }
            }

            DB::commit();

            if($request->permitions){
                foreach($request->permitions as $key => $row){
                    if($row){
                        $permition = UsersHasPermitions::create([
                            "user_id" => $user->id,
                            "permition_id" => $row[0]
                        ]);
                    }
                }
            }

            DB::commit();

            $all_changes = $user->getChanges();
            $changes = "";
            foreach($all_changes as $key => $value ){
                if($key != 'updated_at'){
                    $changes .=$key.',';
                }
            }

            $changes = substr($changes,0,-1);

            Logs::create([
                'user_id' =>  auth()->user()->id,
                'client_id' =>  $request->client_id,
                'type' =>  'change',
                'action' => 'User '.auth()->user()->name.' update User: '.$user->name.'. Fields: '.$changes,
            ]);

            DB::commit();

            return redirect('users')->with('success', 'user updated sucessful');
        }catch (Exception $e) {
            DB::rollback();
            return back()->with('error', 'Server error');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function freeze(User $user)
    {
        DB::beginTransaction();
        try {

            if($user->freeze == false){
                $user->update([
                    'freeze' => true,
                ]);

                $message = 'User Successfully Frozen';
                $status = 'freeze';

            }else{
                $user->update([
                    'freeze' => false,
                ]);

                $message = 'User Successfully Defrosted';
                $status = 'restore';

            }

            Logs::create([
                'user_id' =>  auth()->user()->id,
                'client_id' =>  auth()->user()->client_id,
                'type' =>  'change',
                'action' => 'User '.auth()->user()->name.' '.$status.': '.$user->name.'.',
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message'=> $message
            ]);

        }catch (Exception $e) {
            DB::rollback();

            return response()->json([
                'status'=>'error',
                'message'=> "Server error"
            ]);
        }
    }

    public function userData(User $user){
        return $user;
    }
}
