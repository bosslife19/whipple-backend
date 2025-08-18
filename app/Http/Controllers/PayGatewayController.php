<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Http\Services\TransactionService;

class PayGatewayController extends Controller
{
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
}
