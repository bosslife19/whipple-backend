<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
 public function signUp(Request $request)
    {
        try {
            //code...


            $data = $request->validate(['email' => 'required', 'name' => 'required', 'password' => 'required','phoneNumber'=>'required']);
           try {
            //code...
            
             $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone'=>$data['phoneNumber'],

                    'password' => bcrypt($data['password']),
                    
                ]);

            

            $token =  $user->createToken('main')->plainTextToken;

            return response()->json(['token' => $token, 'user' => $user, 'status'=>true], 200);
           } catch (\Exception $th) {
             return response()->json(['error'=>$th->getMessage()]);
           }
               
        } catch (\Exception $e) {
           
            if($e->getCode()==23000){
                return response()->json(['error'=>'User with this email already exists']);
            }
            return response()->json(['error'=>$e->getMessage()]);
            // if ($e->getCode() === '23000') {
            //     return response()->json(['message' => 'Email already exists, please login instead.'], 422);
            // }
        }
    }


    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required',
            'password' => 'required'
        ]);

        if (!Auth::attempt($credentials)) {
            return response([
                'error' => 'Provided email address or password is incorrect'
            ]);
        }

        $user = Auth::user();


        $token = $user->createToken('main')->plainTextToken;


        return response(
            [
                'status' => true,
                'token' => $token,
                'user' => $user,
                'isAdmin' => $user->isAdmin,

            ]
        );
    }
}
