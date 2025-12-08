<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Http\Services\TransactionService;

class PayGatewayController extends Controller
{

    public function getBanks()
    {
        $response = Http::withToken(config('services.korapay.public_key'))
            ->get("https://api.korapay.com/merchant/api/v1/misc/banks?countryCode=NG");

        if ($response->failed()) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to fetch banks'
            ], 500);
        }

        return response()->json([
            'status' => true,
            'banks' => $response->json()['data']
        ]);
    }

    /**
     * Initialize Payment
     */
    public function paystackInitialize(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->post(config('services.paystack.payment_url') . '/transaction/initialize', [
                'email' => $request->user()->email,
                'amount' => $request->amount * 100, // amount in kobo
                'callback_url' => route('paystack.callback'),
            ])->json();
        $resolver = (new TransactionService())->deposit($request->amount, $response['data']['reference'], 'paystack', $response['data']);

        return response()->json($response, 200);
    }

    /**
     * Handle Paystack Callback
     */
    public function paystackCallback(Request $request)
    {
        $reference = $request->query('reference');

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->get(config('services.paystack.payment_url') . "/transaction/verify/{$reference}");

        $data = $response->json();

        if ($data['status'] && $data['data']['status'] === 'success') {

            $resolver = (new TransactionService())->transactionVerify($reference);

            return response()->json([
                'message' => 'Payment verified successfully',
                'data' => $data['data']
            ]);
        }

        return response()->json([
            'message' => 'Payment verification failed',
            'data' => $data
        ], 400);
    }


    // ######################################################################################
    /**
     * Step 1: Withdrawal Account Resolution
     */

    /**
     * Step 3: Initiate Transfer
     */
    public function initiateTransfer(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100', // amount in Naira
            'recipient' => 'required|string', // recipient_code from createRecipient
        ]);

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->post(config('services.paystack.payment_url') . "/transfer", [
                "source" => "balance",
                "amount" => $request->amount * 100, // Paystack expects kobo
                "recipient" => $request->recipient,
                "reason" => $request->reason ?? "User Withdrawal"
            ]);

        return $response->json();
    }
}
