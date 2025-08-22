<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\UserBankDetails;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Http\Services\TransactionService;
use App\Http\Services\PaymentGatewayService;

class TransactionController extends Controller
{
    public function withdrawRequest(Request $request)
    {
        if (!$request->amount || !is_numeric($request->amount) || $request->amount < 1) {
            return $this->errRes(null, 'Invalid amount');
        }

        $user = User::find(Auth::user()->id);

        if (!$request->pin) {
            return $this->errRes(null, 'Please enter your pin');
        }
        if ($request->pin !== decrypt($user->pin)) {
            return $this->errRes(null, 'The pin entered is not correct!!');
        }

        $bank = UserBankDetails::find(decrypt($request->bank_id));
        if ($user->wallet_balance < $request->amount) {
            return $this->errRes(null, 'Insufficient balance');
        }

        $data = (new TransactionService())->withdraw($request->amount, "paystack");
        try {
            $response = Http::withToken(config('services.paystack.secret_key'))
                ->post(config('services.paystack.payment_url') . "/transfer", [
                    "source" => "balance",
                    "amount" => $data[1] * 100, // Paystack expects kobo
                    "recipient" => $bank->recipient_code,
                    "reason" => $request->reason ?? "User Withdrawal"
                ])->json();
        } catch (\Exception $e) {
            Log::error('Paystack transfer error: ' . $e->getMessage());
            return $this->errRes(null, 'Failed to process withdrawal request');
        }

        if ($response['status'] == false) {
            return $this->errRes(null, $response['message']);
        } else {
            $data[0]->update([
                'status' => 'completed',
                'reference' => $response['data']['reference'],
                'meta' => json_encode($response['data'])
            ]);
            return $this->sucRes($data, 'Withdrawal request submitted successfully');
        }
    }

    public function depositInitialize(Request $request)
    {
        if (!$request->amount || !is_numeric($request->amount) || $request->amount < 1) {
            return $this->errRes(null, 'Invalid amount');
        }
        $data = (new TransactionService())->deposit($request->amount, $request->reference, $request->gateway, $request->meta, $request->ref);
        return $this->sucRes($data, 'Deposit initialized successfully');
    }

    public function depositVerified(Request $request)
    {
        // $payment = (new PaymentGatewayService())->paymentVerified($request->reference);
        // Log::info($payment);
        // if ($payment['status'] == false) {
        //     return $this->errRes(null, $payment['message']);
        // }

        $data = (new TransactionService())->depositVerified($request->ref, $request->reference, $request->meta);
        if (!$data) {
            return $this->errRes(null, 'Deposit verification failed');
        }
        return $this->sucRes($data, 'Deposit initialized successfully');
    }

    public function transactionPin(Request $request)
    {
        $user = User::find(Auth::user()->id);
        if (!$user) {
            return $this->errRes(null, 'User not found');
        }

        $pin = $request->pin;
        if (!$pin || strlen($pin) < 4) {
            return $this->errRes(null, 'Invalid transaction pin');
        }

        $user->pin = encrypt($pin);
        $user->save();

        return $this->sucRes(null, 'Transaction pin set successfully');
    }

    /**
     * Deposit money
     */
    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);

        $user = $request->user();

        DB::transaction(function () use ($user, $request) {
            $before = $user->balance;
            $after = $before + $request->amount;

            // Create transaction
            Transaction::create([
                'user_id' => $user->id,
                'type' => 'deposit',
                'amount' => $request->amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'status' => 'completed',
                'description' => 'Deposit to wallet',
            ]);

            // Update balance
            $user->update(['balance' => $after]);
        });

        return response()->json([
            'message' => 'Deposit successful',
            'balance' => $user->fresh()->balance
        ], 201);
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

    public function transactionList($type = null)
    {
        $query = Transaction::where('user_id', Auth::user()->id);

        if ($type) {
            $query->where('type', $type);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        return $this->sucRes($transactions, 'Transaction list retrieved successfully');
    }
}
