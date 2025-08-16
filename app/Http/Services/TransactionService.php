<?php

namespace App\Http\Services;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\AdminConfiguration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class TransactionService
{
    public function transactionVerify($reference)
    {

        $user = User::find(Auth::user()->id);
        $tran = Transaction::where('reference',)->first();

        DB::transaction(function () use ($user, $tran) {
            $before = $user->wallet_balance;
            $after = $before + $tran->amount;

            // Create transaction
            $tran->update([
                'balance_before' => $before,
                'balance_after' => $after,
            ]);

            // Update balance
            $user->update(['wallet_balance' => $after]);
        });

        return response()->json([
            'message' => 'Deposit successful',
            'balance' => $user->fresh()->balance
        ], 201);
    }

    /**
     * Deposit money
     */
    public function deposit($amount, $reference = null, $gateway = null, $meta = null)
    {

        return DB::transaction(function () use ($amount, $reference, $gateway, $meta) {

            // Create transaction
            return Transaction::create([
                'user_id' => Auth::user()->id,
                'type' => 'deposit',
                'amount' => $amount,
                'status' => 'pending',
                'ref' => uniqid(),
                'gateway' => $gateway,
                'reference' => $reference,
                'description' => 'Deposit to wallet',
                'meta' => $meta ? json_encode($meta) : null,
            ]);
        });
    }

    public function depositVerified($ref, $reference = null,  $meta = null, $waiver = true)
    {
        return DB::transaction(function () use ($ref, $reference, $meta, $waiver) {
            $user = User::find(Auth::user()->id);
            $adminConf = AdminConfiguration::first();
            $tran = Transaction::where('ref', $ref)->where('user_id', $user->id)->first();
            if (!$tran) {
                return false;
            }

            if ($waiver && $adminConf->deposit_charge_waived_points > 0 && $user->whipple_point >= $adminConf->deposit_charge_waived_points) {
                $point = $adminConf->deposit_charge_waived_points;
                $beforePoint = $user->whipple_point;
                $afterPoint = $beforePoint - $point;
                $user->update(['whipple_point' => $afterPoint]);
            } else {
                if ($adminConf->deposit_charge > 0) {
                    if ($adminConf->deposit_type == 'amount') {
                        $fee = $adminConf->deposit_charge;
                        $tran->amount -= $adminConf->deposit_charge;
                    } else if ($adminConf->deposit_type == 'percent') {
                        $fee = ($adminConf->deposit_charge / 100) * $tran->amount;
                        $tran->amount -= $fee;
                    }
                }
            }

            if ($adminConf->referral_point > 0 && $user->referred_by) {
                $referrer = User::find($user->referred_by);
                if ($referrer) {
                    $beforeReferrer = $referrer->whipple_point;
                    $afterReferrer = $beforeReferrer + $adminConf->referral_point;
                    $referrer->update(['whipple_point' => $afterReferrer]);
                    $referral_bonus = $adminConf->referral_point;
                }
            }

            $before = $user->wallet_balance;
            $after = $before + $tran->amount;
            // Update transaction
            $tran->update([
                'status' => 'completed',
                'reference' => $reference,
                'meta' => $meta ? json_encode($meta) : null,
                'balance_before' => $before,
                'balance_after' => $after,
                'fee' => isset($fee) ? $fee : null,
                'point' => isset($point) ? $point : null,
                'point_before' => isset($beforePoint) ? $beforePoint : null,
                'point_after' => isset($afterPoint) ? $afterPoint : null,
                'referral_bonus' => isset($referral_bonus) ? $referral_bonus : null
            ]);
            // Update user balance
            $user->update(['wallet_balance' => $after]);
            return $user->fresh()->wallet_balance;
        });
    }


    /**
     * Withdraw money
     */
    public function withdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);

        $user = $request->user();

        if ($user->balance < $request->amount) {
            return response()->json(['message' => 'Insufficient balance'], 422);
        }

        DB::transaction(function () use ($user, $request) {
            $before = $user->balance;
            $after = $before - $request->amount;

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'withdrawal',
                'amount' => $request->amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'status' => 'pending', // pending until approved
                'description' => 'Withdrawal request',
            ]);

            $user->update(['balance' => $after]);
        });

        return response()->json([
            'message' => 'Withdrawal request submitted',
            'balance' => $user->fresh()->balance
        ], 201);
    }

    /**
     * Spend money on a game
     */
    public function spendOnGame(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'game_id' => 'required|integer'
        ]);

        $user = $request->user();

        if ($user->balance < $request->amount) {
            return response()->json(['message' => 'Insufficient balance'], 422);
        }

        DB::transaction(function () use ($user, $request) {
            $before = $user->balance;
            $after = $before - $request->amount;

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'game',
                'amount' => $request->amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'status' => 'completed',
                'description' => 'Spent on game',
                'meta' => ['game_id' => $request->game_id],
            ]);

            $user->update(['balance' => $after]);
        });

        return response()->json([
            'message' => 'Game spending recorded',
            'balance' => $user->fresh()->balance
        ], 201);
    }
}
