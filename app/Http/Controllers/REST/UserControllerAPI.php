<?php

namespace App\Http\Controllers\REST;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FunctionsController;
use \App\Models\{Extract,Clients,Transactions,Taxes,Keys,Banks,Logs,User,Whitelist,Blocklist,Permitions,UsersHasPermitions};

class UserControllerAPI extends Controller
{
    //
    public function user(Request $request)
    {
        //
        $id = auth()->user()->id;
        $user = User::where("id",$id)->first();
        $permitions_user = UsersHasPermitions::where("user_id",$id)->get();

        $data = [
            "user" => $user,
            "permitions" => $permitions_user
        ];

        return response()->json($data);
    }

    public function getUser(Request $request)
    {
        //
        $id = $request->id;
        $user = User::where("id",$id)->first();
        $permitions_user = UsersHasPermitions::where("user_id",$id)->get();

        $data = [
            "user" => $user,
            "permitions" => $permitions_user
        ];

        return response()->json($data);
    }

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

        return response()->json($data);
    }

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

                return response()->json(["user" => $user, "status" => "success","message" => "User created successfully"]);
            }catch (Exception $e) {
                DB::rollback();
                return response()->json(["status" => "error","message" => "Error on create user"],400);
            }

        }


    }

    public function update(Request $request)
    {
        //
        $user = User::find($request->user_id);

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

            return response()->json(["status" => "success","message" => "User data updated successfully"]);
        }catch (Exception $e) {
            DB::rollback();
            return response()->json(["status" => "error","message" => "Error on update user data"],400);
        }
    }

    public function destroy(Request $request)
    {

        $user = User::find($request->user_id);

        if(isset($user)){
            DB::beginTransaction();
            try{
                $user->delete();
                DB::commit();
                return response()->json(["status" => "success","message" => "User deleted successfully"]);
            }catch (Exception $e) {
                DB::rollback();
                return response()->json(["status" => "error","message" => "Error on delete user data"],400);
            }
        }else{
            return response()->json(["status" => "error","message" => "User not found"],400);
        }

    }

}
