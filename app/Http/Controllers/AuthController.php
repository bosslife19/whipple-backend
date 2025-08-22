<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function signUp(Request $request)
    {
        try {
            //code...


            $data = $request->validate(['email' => 'required', 'name' => 'required', 'password' => 'required', 'phoneNumber' => 'required']);
            try {
                //code...
                $referred_by = User::where('referral_code', $request->referred_by)
                    ->whereNotNull('referral_code')
                    ->first();
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phoneNumber'],
                    'referral_code' => uniqid(),
                    'referred_by' => $referred_by ? $referred_by->id : null,
                    'password' => bcrypt($data['password']),

                ]);



                $token =  $user->createToken('main')->plainTextToken;

                try {
                 $otp = random_int(1000, 9999);

        // Save OTP and its expiration time
        $user->update([
            'otp' => $otp,

        ]);

        // Send OTP via email
        Mail::to($user->email)->send(new \App\Mail\SendOtpMail($otp));
                } catch (\Throwable $th) {
                    \Log::info($th->getMessage());
                    return response()->json(['error'=>'We could not verify your email. Please make sure it is a valid email']);
                }

                return response()->json(['token' => $token, 'user' => $user, 'status' => true], 200);
            } catch (\Exception $th) {
                return response()->json(['error' => $th->getMessage()]);
            }
        } catch (\Exception $e) {

            if ($e->getCode() == 23000) {
                return response()->json(['error' => 'User with this email already exists']);
            }
            return response()->json(['error' => $e->getMessage()]);
            // if ($e->getCode() === '23000') {
            //     return response()->json(['message' => 'Email already exists, please login instead.'], 422);
            // }
        }
    }

    public function verifyOtp(Request $request){
        try {
            //code...\
             $request->validate(['otp'=>'required']);
             $user = $request->user();

             if($request->otp == $user->otp){
                $user->update(['email_verified_at'=>now()]);
                $user->update(['otp'=>null]);
                return response()->json(['status'=>true]);
             }else{
                return response()->json(['error'=>'Otp code does not match']);
             }
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(['error'=>$th->getMessage()]);
        }
       
    }

    public function resendOtp(Request $request){
        try {
            $user = $request->user();
           Mail::to($user->email)->send(new \App\Mail\SendOtpMail($user->otp)); 
           return response()->json(['status'=>true]);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(['error'=>'We could not verify your email. Please check if it is a valid email']);
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

        $user = User::find(Auth::user()->id);
        $token = $user->createToken('main')->plainTextToken;
        if ($user->referral_code == null) {
            $user->referral_code = uniqid();
            $user->save();
        }


        return response(
            [
                'status' => true,
                'token' => $token,
                'user' => $user->fresh(),
                'isAdmin' => $user->isAdmin,

            ]
        );
    }
}
