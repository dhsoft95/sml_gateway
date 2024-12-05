<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'merchant_id' => [
                'required',
                'string',
            ],
            'payer_name' => [
                'required',
                'string',
                'max:255'
            ],
            'service_code' => [
                'required',
                'string',
            ],
            'invoice_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('invoices', 'invoice_number')
            ],
            'bill_amount' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999.99'
            ],
            'currency_code' => [
                'required',
                'string',
                'size:3',
                Rule::in(['TZS', 'USD', 'EUR'])
            ],
            'callback_url' => [
                'required',
                'url',
                'max:2048'
            ],
            'bank_name' => [
                'required',
                'string',
                'max:255'
            ],
            'bank_account' => [
                'required',
                'string',
                'max:50'
            ],
            'metadata' => [
                'sometimes',
                'array'
            ],
            'metadata.order_id' => [
                'sometimes',
                'string',
                'max:50'
            ],
            'metadata.customer_id' => [
                'sometimes',
                'string',
                'max:50'
            ],
            'metadata.description' => [
                'sometimes',
                'string',
                'max:255'
            ],
            'metadata.department' => [
                'sometimes',
                'string',
                'max:100'
            ],
            'metadata.reference' => [
                'sometimes',
                'string',
                'max:100'
            ]
        ];
    }

    public function attributes(): array
    {
        return [
            'merchant_id' => 'merchant ID',
            'payer_name' => 'payer name',
            'service_code' => 'service code',
            'invoice_number' => 'invoice number',
            'bill_amount' => 'bill amount',
            'currency_code' => 'currency code',
            'callback_url' => 'callback URL',
            'bank_name' => 'bank name',
            'bank_account' => 'bank account',
            'metadata.order_id' => 'order ID',
            'metadata.customer_id' => 'customer ID',
            'metadata.description' => 'description',
            'metadata.department' => 'department',
            'metadata.reference' => 'reference'
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_number.unique' => 'The invoice number has already been used.',
            'bill_amount.min' => 'The bill amount must be greater than zero.',
            'bill_amount.max' => 'The bill amount exceeds the maximum allowed value.',
            'currency_code.in' => 'The selected currency is not supported.',
            'callback_url.url' => 'The callback URL must be a valid URL.',
            'metadata.array' => 'The metadata must be provided as an array.',
            'bank_name.required' => 'The bank name is required.',
            'bank_account.required' => 'The bank account is required.',
            'bank_account.max' => 'The bank account may not exceed 50 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'currency_code' => strtoupper($this->currency_code),
            'invoice_number' => trim($this->invoice_number),
            'payer_name' => trim($this->payer_name),
            'bank_name' => trim($this->bank_name),
            'bank_account' => trim($this->bank_account),
        ]);
    }
}
