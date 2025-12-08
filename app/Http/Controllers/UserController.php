<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\UserBankDetails;
use App\Models\AdminConfiguration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Services\PaymentGatewayService;

class UserController extends Controller
{

    public function updateProfile(Request $request)
    {

        $user = $request->user();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;

        $user->save();



        return response()->json(['status' => true]);
    }

    public function deductBalance(Request $request)
    {

     return response()->json(['status' => true], 200);
        $user = $request->user();

         if($user->whipple_point>=80){
            $user->whipple_point = $user->whipple_point - 80;
            $user->save();
                    return response()->json(['status'=>true]);
                }
                if($user->whipple_point >=40 &&$user->whipple_point<80){
                    $user->wallet_balance = $user->wallet_balance - (intval($request->stake) + (intval($request->stake)*0.25)-(intval($request->stake)*0.25));
            $user->whipple_point = $user->whipple_point - 40;   
             $user->save(); 
             if ($user->wallet_balance < $request->amount) {
            return response()->json(['error' => 'You do not have sufficient funds to play this game']);
        }
                return response()->json(['status'=>true], 200);
                }








        if ($user->wallet_balance < $request->amount) {
            return response()->json(['error' => 'You do not have sufficient funds to play this game']);
        }
        $user->wallet_balance = $user->wallet_balance - $request->amount;
        $user->save();

        return response()->json(['status' => true], 200);
    }


    public function referralList()
    {
        $referrals = User::where('referred_by', Auth::user()->id)->get();
        $data = [];
        foreach ($referrals as $referral) {
            $data[] = [
                'id' => $referral->id,
                'name' => $referral->name,
                'email' => $referral->email,
                'phone' => $referral->phone,
            ];
        }

        return $this->sucRes(
            $data,
            'Referral list retrieved successfully'
        );
    }

    public function bankSave(Request $request)
    {
        \Log::info($request->all());
        $check = UserBankDetails::where('user_id', Auth::user()->id)->where('account_number', $request->account_number)->where('bank_code', $request->bank_code)->first();

        if ($check) {
            return $this->errRes(null, "This bank account already exists for this user.");
        }

        $resolver = (new PaymentGatewayService())->resolveAccount(
            $request->account_number,
            $request->bank_code
        );
       

        if ($resolver['status'] == false) {
            return $this->errRes(null, $resolver['message']);
        }

        // $recipient = (new PaymentGatewayService())->createRecipient(
        //     $resolver['data']["account_name"],
        //     $request->account_number,
        //     $request->bank_code
        // );


        // if ($recipient['status'] == false) {
        //     return $this->errRes(null, $recipient['message']);
        // }

        $data = UserBankDetails::create([
            'user_id' => Auth::user()->id,
            'account_number' => $request->account_number,
            'account_name' => $resolver['data']["account_name"],
            // 'bank_id' => $resolver['data']["bank_id"],
            'bank_name' => $request->bank_name,
            'bank_code' => $request->bank_code,
            // 'recipient_code' => $recipient['data']['recipient_code'],
            // 'recipient_id' => $recipient['data']['id'],
            // 'recipient_integration' => $recipient['data']['integration'],
            // 'recipient_type' => $recipient['data']['type'],
            // 'recipient_detail' => json_encode($recipient['data']['details']),
            // 'recipient_metal' => json_encode($recipient['data']['metadata']),
        ]);

        return $this->sucRes(
            $data,
            'Bank details saved successfully'
        );
    }
    public function bankList()
    {
        $banks = UserBankDetails::where('user_id', Auth::user()->id)->get();
        $data = [];
        foreach ($banks as $bank) {
            $data[] = [

                'account_number' => $bank->account_number,
                'account_name' => $bank->account_name,
                'bank_name' => $bank->bank_name,
                'bank_code' => $bank->bank_code,
            ];
        }

        return $this->sucRes(
            $data,
            'Bank list retrieved successfully'
        );
    }

    public function adminParameter()
    {
        return $this->sucRes(
            AdminConfiguration::first(),
            'Bank list retrieved successfully'
        );
    }
}
