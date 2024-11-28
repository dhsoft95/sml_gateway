<?php

namespace App\Http\Requests;

class PaymentCallbackRequest
{
    public function rules(): array
    {
        return [
            'reference' => 'required|string',
            'transaction_id' => 'required|string',
            'status' => 'required|string|in:success,failed',
            'amount' => 'required|numeric',
            'payload' => 'required|array'
        ];
    }
}
