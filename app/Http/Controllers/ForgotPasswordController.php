<?php

// app/Http/Controllers/ForgotPasswordController.php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PasswordResetCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    public function sendCode(Request $request)
    {

        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Email not found'], 404);
        }

        $code = rand(1000, 9999);

        PasswordResetCode::updateOrCreate(
            ['email' => $request->email],
            [
                'code' => $code,
                'expires_at' => Carbon::now()->addMinutes(10)
            ]
        );

        Mail::raw("Your password reset code is: {$code}", function ($message) use ($request) {
            $message->to($request->email)
                    ->subject('Password Reset Code');
        });

        return response()->json(['message' => 'Code sent']);
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required'
        ]);

        $record = PasswordResetCode::where('email', $request->email)
            ->where('code', $request->code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid or expired code'], 400);
        }

        return response()->json(['message' => 'Code verified']);
    }

    public function resetPassword(Request $request)
   
    {

        try {
            //code...
             $request->validate([
            'email' => 'required|email',
            'code' => 'required',
            'password' => 'required|min:8'
        ]);

        $record = PasswordResetCode::where('email', $request->email)
            ->where('code', $request->code)
            ->first();


        if (!$record) {
            return response()->json(['message' => 'Invalid request'], 400);
        }

        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password)
        ]);

        $record->delete();

        return response()->json(['message' => 'Password reset successful']);
        } catch (\Throwable $th) {
            //throw $th;
            \Log::info($th->getMessage());
            return response()->json(['message' => 'An error occurred'], 500);
        }
       
    }
}

