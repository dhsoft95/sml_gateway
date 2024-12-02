<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessCallbackJob;
use App\Models\Invoice;
use App\Models\QRCode;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class WebhookController extends Controller
{
    const QR_STATUS_ACTIVE = 'ACTIVE';
    const QR_STATUS_USED = 'USED';
    const QR_STATUS_EXPIRED = 'EXPIRED';

    public function handleSimbaWebhook(Request $request): \Illuminate\Http\JsonResponse
    {
        Log::info('Simba Money webhook received', [
            'payload' => $request->all(),
            'headers' => $request->headers->all()
        ]);

        try {
            $validated = $request->validate([
                'invoice_id' => 'required|string|exists:invoices,external_id',
                'transaction_id' => 'required|string',
                'status' => 'required|string|in:SUCCESS,COMPLETED,FAILED,REJECTED,EXPIRED,PENDING',
                'amount' => 'required|array',
                'amount.value' => 'required|string',
                'amount.currency' => 'required|string|size:3',
                'amount.formatted' => 'required|string',
                'provider_reference' => 'required|string',
                'processed_at' => 'required|date',
                'payer_details' => 'required|array',
                'payer_details.phone' => 'required|string',
                'payer_details.email' => 'nullable|email',
                'merchant_id' => 'required|string',
                'metadata' => 'required|array',
                'metadata.order_id' => 'required|string',
                'metadata.customer_id' => 'required|string',
                'metadata.description' => 'required|string',
                'metadata.department' => 'required|string',
                'metadata.reference' => 'required|string',
                'signature' => 'required|string'
            ]);

            return DB::transaction(function() use ($validated) {
                // Get invoice
                $invoice = Invoice::where('external_id', $validated['invoice_id'])
                    ->where('merchant_id', $validated['merchant_id'])
                    ->firstOrFail();

                // Get QR code
                $qrCode = QRCode::where('invoice_id', $invoice->id)
                    ->where('status', self::QR_STATUS_ACTIVE)
                    ->firstOrFail();

                // Verify amount
                $paidAmount = (float) $validated['amount']['value'];

                if (abs($invoice->bill_amount - $paidAmount) >= 0.01 ||
                    $invoice->currency_code !== $validated['amount']['currency']) {
                    throw new \Exception(sprintf(
                        'Amount mismatch: expected %s %s, got %s %s',
                        $invoice->bill_amount,
                        $invoice->currency_code,
                        $paidAmount,
                        $validated['amount']['currency']
                    ));
                }

                try {
                    // Create or update transaction
                    $transaction = Transaction::updateOrCreate(
                        ['transaction_id' => $validated['transaction_id']],
                        [
                            'invoice_id' => $invoice->id,
                            'control_number' => $qrCode->control_number,
                            'amount' => $paidAmount,
                            'currency' => $validated['amount']['currency'],
                            'status' => $this->mapPaymentStatus($validated['status']),
                            'payment_method' => 'simba_money',
                            'payer_details' => $validated['payer_details'],
                            'provider_reference' => $validated['provider_reference'],
                            'processed_at' => $validated['processed_at'],
                            'provider_response' => $validated
                        ]
                    );

                    // Update statuses
                    $newStatus = $this->mapPaymentStatus($validated['status']);

                    // Update invoice status
                    $invoice->update(['status' => $newStatus]);

                    // Update QR code status based on payment status
                    $qrCodeStatus = match($newStatus) {
                        'PAID' => self::QR_STATUS_USED,
                        'FAILED', 'EXPIRED' => self::QR_STATUS_EXPIRED,
                        default => self::QR_STATUS_ACTIVE
                    };

                    // Log before QR code update
                    Log::info('Updating QR code status', [
                        'qr_code_id' => $qrCode->id,
                        'current_status' => $qrCode->status,
                        'new_status' => $qrCodeStatus
                    ]);

                    if ($qrCodeStatus !== $qrCode->status) {
                        $qrCode->status = $qrCodeStatus;
                        $qrCode->save();
                    }

                    // Queue callback
                    dispatch(new ProcessCallbackJob($invoice, $transaction));

                    Log::info('Webhook processed successfully', [
                        'transaction_id' => $transaction->transaction_id,
                        'invoice_id' => $invoice->external_id,
                        'status' => $newStatus,
                        'qr_code_status' => $qrCodeStatus
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Webhook processed successfully',
                        'data' => [
                            'transaction_id' => $transaction->transaction_id,
                            'invoice_status' => $newStatus,
                            'qr_code_status' => $qrCodeStatus,
                            'processed_at' => now()->toIso8601String()
                        ]
                    ]);

                } catch (QueryException $e) {
                    Log::error('Database error', [
                        'error' => $e->getMessage(),
                        'code' => $e->getCode()
                    ]);

                    if ($e->getCode() === '23000') {
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Transaction already processed'
                        ]);
                    }
                    throw $e;
                }
            });

        } catch (ValidationException $e) {
            Log::warning('Webhook validation failed', [
                'errors' => $e->errors(),
                'payload' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid webhook payload',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process webhook',
                'error_code' => 'WEBHOOK_PROCESSING_FAILED',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    private function mapPaymentStatus(string $status): string
    {
        return match(strtoupper($status)) {
            'SUCCESS', 'COMPLETED' => 'PAID',
            'FAILED', 'REJECTED' => 'FAILED',
            'EXPIRED' => 'EXPIRED',
            'PENDING' => 'PENDING',
            default => 'UNKNOWN'
        };
    }
}
