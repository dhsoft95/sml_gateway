<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\FailedCallback;
use App\Models\QRCode;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Verify and process payment in one step
     */
    public function process(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'control_number' => 'required|string',
            'payment_method' => 'required|string',
            'payer_details' => 'required|array',
            'payer_details.phone' => 'required|string',
            'payer_details.email' => 'nullable|email'
        ]);

        $qrCode = QRCode::where('control_number', $request->control_number)
            ->where('status', 'ACTIVE')
            ->where('expires_at', '>', now())
            ->with('invoice')
            ->first();

        if (!$qrCode || $qrCode->invoice->status !== 'PENDING') {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid control number or invoice status',
                'error_code' => 'INVALID_PAYMENT_REQUEST'
            ], 400);
        }

        // Create transaction and update invoice status in one step
        $transaction = Transaction::create([
            'transaction_id' => 'TXN' . Str::random(12),
            'invoice_id' => $qrCode->invoice->id,
            'amount' => $qrCode->invoice->amount,
            'currency' => $qrCode->invoice->currency,
            'payment_method' => $request->payment_method,
            'status' => 'PROCESSING',
            'payer_details' => $request->payer_details,
        ]);

        // Update invoice status
        $qrCode->invoice->update(['status' => 'PROCESSING']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'transaction_id' => $transaction->transaction_id,
                'amount' => [
                    'value' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'formatted' => number_format($transaction->amount, 2) . ' ' . $transaction->currency
                ],
                'payment_instructions' => $this->getPaymentInstructions($transaction)
            ]
        ]);
    }

    /**
     * Confirm payment (called by payment provider)
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|string',
            'provider_reference' => 'required|string',
            'status' => 'required|string|in:SUCCESS,FAILED',
            'amount' => 'required|numeric',
            'currency' => 'required|string',
            'paid_at' => 'required|date'
        ]);

        $transaction = Transaction::where('transaction_id', $request->transaction_id)
            ->with('invoice')
            ->firstOrFail();

        if ($transaction->status !== 'PROCESSING') {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid transaction status',
                'error_code' => 'INVALID_TRANSACTION_STATUS'
            ], 400);
        }

        // Update transaction and invoice status
        $transaction->update([
            'status' => $request->status === 'SUCCESS' ? 'COMPLETED' : 'FAILED',
            'provider_reference' => $request->provider_reference,
            'provider_response' => $request->all(),
            'processed_at' => $request->paid_at
        ]);

        $transaction->invoice->update([
            'status' => $request->status === 'SUCCESS' ? 'PAID' : 'FAILED'
        ]);

        // Send callback to merchant
        $this->sendCallback($transaction);

        return response()->json([
            'status' => 'success',
            'message' => 'Payment confirmation processed'
        ]);
    }

    private function getPaymentInstructions($transaction)
    {
        return [
            'en' => [
                'title' => 'Payment Instructions',
                'amount' => number_format($transaction->amount, 2) . ' ' . $transaction->currency,
                'steps' => [
                    'Make payment using your preferred method',
                    'Use transaction ID: ' . $transaction->transaction_id,
                    'Payment will be confirmed automatically'
                ]
            ],
            'sw' => [
                'title' => 'Maelekezo ya Malipo',
                'amount' => number_format($transaction->amount, 2) . ' ' . $transaction->currency,
                'steps' => [
                    'Fanya malipo kwa njia unayopendelea',
                    'Tumia namba ya muamala: ' . $transaction->transaction_id,
                    'Malipo yatathibitishwa moja kwa moja'
                ]
            ]
        ];
    }

    private function sendCallback($transaction)
    {
        try {
            // Verify callback URL is valid
            if (!filter_var($transaction->invoice->callback_url, FILTER_VALIDATE_URL)) {
                Log::error('Invalid callback URL', [
                    'transaction_id' => $transaction->transaction_id,
                    'callback_url' => $transaction->invoice->callback_url
                ]);
                return false;
            }

            // Prepare callback data
            $callbackData = [
                'invoice_id' => $transaction->invoice->external_id,
                'transaction_id' => $transaction->transaction_id,
                'status' => $transaction->status,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'provider_reference' => $transaction->provider_reference,
                'processed_at' => $transaction->processed_at,
                'payer_details' => $transaction->payer_details,
                'control_number' => $transaction->invoice->qrCode->control_number
            ];

            // Add signature for security (if needed)
            $callbackData['signature'] = hash_hmac('sha256',
                json_encode($callbackData),
                config('services.psp.webhook_secret')
            );

            // Send callback with timeout and retry
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-API-Key' => config('services.psp.api_key'),
                    'Content-Type' => 'application/json',
                ])
                ->post($transaction->invoice->callback_url, $callbackData);

            // Log success
            Log::info('Payment callback sent successfully', [
                'transaction_id' => $transaction->transaction_id,
                'callback_url' => $transaction->invoice->callback_url,
                'response_status' => $response->status(),
                'response_body' => $response->json()
            ]);

            return true;

        } catch (\Exception $e) {
            // Log the error with details
            Log::error('Payment callback failed', [
                'transaction_id' => $transaction->transaction_id,
                'callback_url' => $transaction->invoice->callback_url,
                'error' => $e->getMessage(),
                'error_type' => get_class($e)
            ]);

            // Store failed callback for retry
            $this->storeFailedCallback($transaction, $callbackData ?? []);

            return false;
        }
    }

    private function storeFailedCallback($transaction, $callbackData)
    {
        // Create a failed callback record for retry
        FailedCallback::create([
            'transaction_id' => $transaction->transaction_id,
            'callback_url' => $transaction->invoice->callback_url,
            'payload' => $callbackData,
            'attempts' => 0,
            'next_retry_at' => now()->addMinutes(5), // First retry after 5 minutes
            'status' => 'PENDING'
        ]);
    }
}
