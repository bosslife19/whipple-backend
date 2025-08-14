<?php

namespace App\Http\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function depositVerified($ref, $reference = null,  $meta = null)
    {
        $user = User::find(Auth::user()->id);
        return DB::transaction(function () use ($user, $ref, $reference, $meta) {
            $tran = Transaction::where('ref', $ref)->where('user_id', $user->id)->first();
            if (!$tran) {
                return false;
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
