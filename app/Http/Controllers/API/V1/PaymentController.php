<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\FailedCallback;
use App\Models\QRCode;
use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    /**
     * Verify and process payment in one step
     */
    public function process(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $validated = $request->validate([
                'control_number' => 'required|string',
                'payment_method' => 'required|string',
                'payer_details' => 'required|array',
                'payer_details.phone' => 'required|string',
                'payer_details.email' => 'nullable|email'
            ]);

            // Find active QR code and related invoice
            $qrCode = QRCode::where('control_number', $validated['control_number'])
                ->where('status', 'ACTIVE')
                ->where('expires_at', '>', now())
                ->with(['invoice' => function($query) {
                    $query->where('status', 'PENDING');
                }])
                ->first();

            if (!$qrCode || !$qrCode->invoice) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid control number or expired QR code.  Please generate a new invoice.',
                    'error_code' => 'INVALID_PAYMENT_REQUEST'
                ], 400);
            }

            // Process payment in transaction
            $result = DB::transaction(function () use ($qrCode, $validated) {
                // Create transaction
                $transaction = Transaction::create([
                    'transaction_id' => 'TXN' . Str::random(12),
                    'invoice_id' => $qrCode->invoice->id,
                    'amount' => $qrCode->invoice->bill_amount,
                    'currency' => $qrCode->invoice->currency_code,
                    'payment_method' => $validated['payment_method'],
                    'status' => 'PROCESSING',
                    'payer_details' => $validated['payer_details'],
                ]);

                // Update invoice status
                $qrCode->invoice->update(['status' => 'PROCESSING']);

                return $transaction;
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'transaction_id' => $result->transaction_id,
                    'amount' => [
                        'value' => $result->amount,
                        'currency' => $result->currency,
                        'formatted' => number_format($result->amount, 2) . ' ' . $result->currency
                    ],
                    'payment_instructions' => $this->getPaymentInstructions($result)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Payment processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->except(['password', 'token'])
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process payment',
                'error_code' => 'PAYMENT_PROCESSING_FAILED'
            ], 500);
        }
    }

    /**
     * Confirm payment (called by payment provider)
     */

    public function confirm(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $validated = $request->validate([
                'transaction_id' => 'required|string',
                'provider_reference' => 'required|string',
                'status' => 'required|string|in:SUCCESS,FAILED',
                'amount' => 'required|numeric|min:0',
                'currency' => 'required|string|size:3',
                'paid_at' => 'required|date'
            ]);

            // Find transaction
            $transaction = Transaction::where('transaction_id', $validated['transaction_id'])
                ->with('invoice')
                ->first();
            // If transaction not found
            if (!$transaction) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Transaction not found',
                    'error_code' => 'TRANSACTION_NOT_FOUND'
                ], 404);
            }

            // Check transaction status
            if ($transaction->status !== 'PROCESSING') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Transaction is not in processing status',
                    'error_code' => 'INVALID_TRANSACTION_STATUS'
                ], 400);
            }

            // Verify amount matches
            if (bccomp($transaction->amount, $validated['amount'], 2) !== 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Amount mismatch',
                    'error_code' => 'AMOUNT_MISMATCH'
                ], 400);
            }

            // Process confirmation
            try {
                DB::transaction(function () use ($transaction, $validated) {
                    $transaction->update([
                        'status' => $validated['status'],
                        'provider_reference' => $validated['provider_reference'],
                        'provider_response' => $validated,
                        'processed_at' => $validated['paid_at']
                    ]);

                    $transaction->invoice->update([
                        'status' => $validated['status'] === 'SUCCESS' ? 'PAID' : 'FAILED'
                    ]);
                });
            } catch (\Exception $e) {
                Log::error('Database transaction failed', [
                    'error' => $e->getMessage(),
                    'transaction_id' => $validated['transaction_id']
                ]);

                // This is a real 500 error - database failure
                return response()->json([
                    'status' => 'error',
                    'message' => 'System error while processing payment',
                    'error_code' => 'SYSTEM_ERROR'
                ], 500);
            }

            // Send callback
            $this->sendCallback($transaction);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment confirmation processed'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid request data',
                'error_code' => 'VALIDATION_ERROR',
                'errors' => $e->errors()
            ], 400);
        }
    }

    private function getPaymentInstructions($transaction): array
    {
        $invoice = $transaction->invoice;

        return [
            'en' => [
                'title' => 'Payment Instructions',
                'amount' => number_format($transaction->amount, 2) . ' ' . $transaction->currency,
                'transaction_id' => $transaction->transaction_id,
                'payment_methods' => [
                    'mobile_money' => 'Simba Money',
                    'bank_transfer' => [
                        'bank_name' => $invoice->bank_name ?? 'N/A',
                        'bank_account' => $invoice->bank_account ?? 'N/A'
                    ]
                ]
            ],
            'sw' => [
                'title' => 'Maelekezo ya Malipo',
                'amount' => number_format($transaction->amount, 2) . ' ' . $transaction->currency,
                'transaction_id' => $transaction->transaction_id,
                'payment_methods' => [
                    'mobile_money' => 'Simba Money',
                    'bank_transfer' => [
                        'bank_name' => $invoice->bank_name ?? 'N/A',
                        'bank_account' => $invoice->bank_account ?? 'N/A'
                    ]
                ]
            ]
        ];
    }

    private function sendCallback($transaction): void
    {
        try {
            if (!filter_var($transaction->invoice->callback_url, FILTER_VALIDATE_URL)) {
                throw new \Exception('Invalid callback URL');
            }

            $callbackData = [
                'invoice_id' => $transaction->invoice->external_id,
                'transaction_id' => $transaction->transaction_id,
                'status' => $transaction->status,
                'amount' => [
                    'value' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'formatted' => number_format($transaction->amount, 2) . ' ' . $transaction->currency
                ],
                'provider_reference' => $transaction->provider_reference,
                'processed_at' => $transaction->processed_at,
                'payer_details' => $transaction->payer_details,
                'merchant_id' => $transaction->invoice->merchant->merchant_code,
                'metadata' => $transaction->invoice->metadata
            ];

            // Ensure we sort the data before signing
            ksort($callbackData);

            $callbackData['signature'] = hash_hmac('sha256',
                json_encode($callbackData),
                config('services.payment.webhook_secret')
            );

            $response = Http::timeout(15)
                ->withHeaders([
                    'X-API-Key' => config('services.payment.api_key'),
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'PaymentCallback/1.0',
                    'X-Transaction-ID' => $transaction->transaction_id
                ])
                ->post($transaction->invoice->callback_url, $callbackData);

            if (!$response->successful()) {
                throw new \Exception(sprintf(
                    'Callback request failed with status: %d, Body: %s',
                    $response->status(),
                    substr($response->body(), 0, 500)
                ));
            }

            Log::info('Payment callback sent successfully', [
                'transaction_id' => $transaction->transaction_id,
                'response_status' => $response->status(),
                'response_body' => $response->json()
            ]);

        } catch (\Exception $e) {
            Log::error('Payment callback failed', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
                'callback_data' => $callbackData ?? null
            ]);

            $this->storeFailedCallback($transaction, $callbackData ?? []);
        }
    }
    private function storeFailedCallback($transaction, array $callbackData): void
    {
        FailedCallback::create([
            'transaction_id' => $transaction->transaction_id,
            'callback_url' => $transaction->invoice->callback_url,
            'payload' => $callbackData,
            'attempts' => 0,
            'next_retry_at' => now()->addMinutes(5),
            'status' => 'PENDING'
        ]);
    }
}
