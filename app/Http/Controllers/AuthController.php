<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function signUp(Request $request)
    {
        try {
            //code...


            $data = $request->validate(['email' => 'required', 'name' => 'required', 'password' => 'required', 'phoneNumber' => 'required']);
            try {
                //code...
                $referred_by = null;
                if (isset($request->referrals) || $request->referrals != null) {
                    $referred_by = User::where('referral_code', $request->referrals)->first()->id;
                }

                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phoneNumber'],
                    'referral_code' => uniqid(),
                    'referred_by' => $referred_by,
                    'password' => bcrypt($data['password']),

                ]);



                $token =  $user->createToken('main')->plainTextToken;

                try {
                 $otp = random_int(1000, 9999);

        // Save OTP and its expiration time
        $user->update([
            'otp' => $otp,

        ]);

      
        Mail::to($user->email)->send(new \App\Mail\SendOtpMail($otp));
                } catch (\Throwable $th) {
                    // \Log::info($th->getMessage());
                    
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

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function loginWeb(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->remember)) {
            $user = User::find(Auth::user()->id);
            if($user->referral_code == 'admin'){
                $request->session()->regenerate();
                return redirect()->intended(route('admin.dashboard'));
            }else{
                Auth::logout();
                return redirect()->intended(route('login'));
            }
            
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Since we are not writing migrations, we will check if the user exists
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'We could not find a user with that email address.']);
        }

        // Generate a simple token and store it in the user model for simplicity 
        // given the "don't write any migration file" constraint.
        // Usually, this goes into password_resets table.
        // I'll check if OTP can be repurposed or just use a simple flow.
        
        $token = Str::random(60);
        // We can't add columns, so let's use the 'otp' column for the reset token if possible, 
        // or just use Laravel's default Password broker.
        // Actually, password_reset_tokens table exists in the standard Laravel migration I saw earlier!
        
        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetPasswordForm(Request $request, $token = null)
    {
        return view('auth.reset-password')->with(['token' => $token, 'email' => $request->email]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));
                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}

