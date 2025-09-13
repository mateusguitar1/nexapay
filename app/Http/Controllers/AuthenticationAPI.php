<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

use App\Models\{User};

class AuthenticationAPI extends Controller
{
    //

    public function login(Request $request){

        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where("email",$request->email)->first();

        if(!$user){
            return response()->json(["message" => "E-mail Incorrect"]);
        }

        if(!Hash::check($request->password, $user->password)){
            return response()->json(["message" => "Password Incorrect"]);
        }

        $token = $user->createToken($request->email.strtotime("now"))->plainTextToken;

        return response()->json([
            "access_token" => $token,
            "token_type" => "bearer"
        ]);

    }

    public function logout(Request $request){

        $request->user()->tokens()->delete();
        return response()->json(["message" => "logout"],201);

    }
}
