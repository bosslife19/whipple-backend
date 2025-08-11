<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class PayGatewayController extends Controller
{
    /**
     * Initialize Payment
     */
    public function paystackInitialize(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'amount' => 'required|numeric|min:1'
        ]);

        $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
            ->post(env('PAYSTACK_PAYMENT_URL') . '/transaction/initialize', [
                'email' => $request->email,
                'amount' => $request->amount * 100, // amount in kobo
                'callback_url' => route('paystack.callback'),
            ]);

        return response()->json($response->json());
    }

    /**
     * Handle Paystack Callback
     */
    public function paystackCallback(Request $request)
    {
        $reference = $request->query('reference');

        $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
            ->get(env('PAYSTACK_PAYMENT_URL') . "/transaction/verify/{$reference}");

        $data = $response->json();

        if ($data['status'] && $data['data']['status'] === 'success') {
            // TODO: Update user payment record in DB
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
