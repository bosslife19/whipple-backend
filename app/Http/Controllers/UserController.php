<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
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
}
