<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // You might want to add merchant authorization logic here
        // For example: return $this->user()->can('create-invoice');
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'merchant_id' => [
                'required',
                'string',
                // You might want to add merchant existence validation
                // Rule::exists('merchants', 'id')
            ],
            'payer_name' => [
                'required',
                'string',
                'max:255'
            ],
            'service_code' => [
                'required',
                'string',
                // You might want to add valid service codes validation
                // Rule::in(['BILL_PAY', 'MERCHANT_PAY', 'UTILITY_PAY'])
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
                'max:999999999.99' // Adjust based on your business rules
            ],
            'currency_code' => [
                'required',
                'string',
                'size:3',
                Rule::in(['TZS', 'USD', 'EUR']) // Add supported currencies
            ],
            'callback_url' => [
                'required',
                'url',
                'max:2048'
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

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
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
            'metadata.order_id' => 'order ID',
            'metadata.customer_id' => 'customer ID',
            'metadata.description' => 'description',
            'metadata.department' => 'department',
            'metadata.reference' => 'reference'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'invoice_number.unique' => 'The invoice number has already been used.',
            'bill_amount.min' => 'The bill amount must be greater than zero.',
            'bill_amount.max' => 'The bill amount exceeds the maximum allowed value.',
            'currency_code.in' => 'The selected currency is not supported.',
            'callback_url.url' => 'The callback URL must be a valid URL.',
            'metadata.array' => 'The metadata must be provided as an array.',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'currency_code' => strtoupper($this->currency_code),
            'invoice_number' => trim($this->invoice_number),
            'payer_name' => trim($this->payer_name)
        ]);
    }
}
