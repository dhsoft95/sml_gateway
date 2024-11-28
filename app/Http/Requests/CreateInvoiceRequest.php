<?php

namespace App\Http\Requests;

class CreateInvoiceRequest
{

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:100',
            'currency' => 'required|string|size:3',
            'customer_id' => 'required|string',
            'customer_phone' => 'required|string',
            'description' => 'nullable|string|max:255',
            'expires_at' => 'nullable|date|after:now'
        ];
    }
}
