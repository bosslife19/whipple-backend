<?php

namespace App\Http\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymentGatewayService
{
    public function resolveAccount($account_number, $bank_code)
    {

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->get(config('services.paystack.payment_url') . "/bank/resolve", [
                'account_number' => $account_number,
                'bank_code' => $bank_code
            ]);

        return $response->json();
    }

    /**
     * Step 2: Create recipient
     */
    public function createRecipient($name, $account_number, $bank_code)
    {

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->post(config('services.paystack.payment_url') . "/transferrecipient", [
                "type" => "nuban",
                "name" => $name,
                "account_number" => $account_number,
                "bank_code" => $bank_code,
                "currency" => "NGN"
            ]);

        return $response->json();
    }

    public function paymentVerified($reference)
    {
        $response = Http::withToken(config('services.paystack.secret_key'))
            ->get(config('services.paystack.payment_url') . "/transaction/verify/{$reference}");

        $data = $response->json();
    }
}
