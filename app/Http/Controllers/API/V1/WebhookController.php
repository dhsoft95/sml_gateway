<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessCallbackJob;
use App\Models\Invoice;
use App\Models\QRCode;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class WebhookController extends Controller
{
    private const QR_STATUS_ACTIVE = 'ACTIVE';
    private const QR_STATUS_USED = 'USED';
    private const QR_STATUS_EXPIRED = 'EXPIRED';

    private const PAYMENT_STATUS_MAP = [
        'SUCCESS' => 'PAID',
        'COMPLETED' => 'PAID',
        'FAILED' => 'FAILED',
        'REJECTED' => 'FAILED',
        'EXPIRED' => 'EXPIRED',
        'PENDING' => 'PENDING'
    ];

    private const QR_STATUS_MAP = [
        'PAID' => self::QR_STATUS_USED,
        'FAILED' => self::QR_STATUS_EXPIRED,
        'EXPIRED' => self::QR_STATUS_EXPIRED,
        'PENDING' => self::QR_STATUS_ACTIVE
    ];

    public function handleSimbaWebhook(Request $request): \Illuminate\Http\JsonResponse
    {
        Log::info('Simba Money webhook received', [
            'payload' => $request->all(),
            'headers' => $request->headers->all()
        ]);

        try {
            $validated = $this->validateWebhookPayload($request);

            return DB::transaction(function() use ($validated) {
                $invoice = $this->getInvoice($validated);
                $qrCode = $this->getActiveQRCode($invoice);

                $this->verifyAmount($invoice, $validated['amount']);

                $transaction = $this->processTransaction($invoice, $qrCode, $validated);
                $newStatus = $this->updateStatuses($invoice, $qrCode, $validated['status']);

                // Queue callback after transaction commits
                dispatch(new ProcessCallbackJob($invoice, $transaction))->afterCommit();

                Log::info('Webhook processed successfully', [
                    'transaction_id' => $transaction->transaction_id,
                    'invoice_id' => $invoice->external_id,
                    'status' => $newStatus,
                    'qr_code_status' => $qrCode->status
                ]);

                return $this->successResponse($transaction, $newStatus, $qrCode->status);
            });

        } catch (ValidationException $e) {
            Log::warning('Webhook validation failed', [
                'errors' => $e->errors(),
                'payload' => $request->all()
            ]);
            return $this->validationErrorResponse($e);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);
            return $this->errorResponse($e);
        }
    }

    private function validateWebhookPayload(Request $request): array
    {
        return $request->validate([
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
    }

    private function getInvoice(array $validated): Invoice
    {
        return Invoice::whereHas('merchant', function($query) use ($validated) {
            $query->where('merchant_code', $validated['merchant_id']);
        })
            ->where('external_id', $validated['invoice_id'])
            ->firstOrFail();
    }

    private function getActiveQRCode(Invoice $invoice): QRCode
    {
        return QRCode::where('invoice_id', $invoice->id)
            ->where('status', self::QR_STATUS_ACTIVE)
            ->firstOrFail();
    }

    private function verifyAmount(Invoice $invoice, array $amount): void
    {
        $paidAmount = (float) $amount['value'];

        if (abs($invoice->bill_amount - $paidAmount) >= 0.01 ||
            $invoice->currency_code !== $amount['currency']) {
            throw new \Exception(sprintf(
                'Amount mismatch: expected %s %s, got %s %s',
                $invoice->bill_amount,
                $invoice->currency_code,
                $paidAmount,
                $amount['currency']
            ));
        }
    }

    private function processTransaction(Invoice $invoice, QRCode $qrCode, array $validated): Transaction
    {
        try {
            return Transaction::updateOrCreate(
                ['transaction_id' => $validated['transaction_id']],
                [
                    'invoice_id' => $invoice->id,
                    'control_number' => $qrCode->control_number,
                    'amount' => (float) $validated['amount']['value'],
                    'currency' => $validated['amount']['currency'],
                    'status' => $this->mapPaymentStatus($validated['status']),
                    'payment_method' => 'simba_money',
                    'payer_details' => $validated['payer_details'],
                    'provider_reference' => $validated['provider_reference'],
                    'processed_at' => $validated['processed_at'],
                    'provider_response' => $validated
                ]
            );
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return Transaction::where('transaction_id', $validated['transaction_id'])->firstOrFail();
            }
            throw $e;
        }
    }

    private function updateStatuses(Invoice $invoice, QRCode $qrCode, string $status): string
    {
        $newStatus = $this->mapPaymentStatus($status);
        $qrCodeStatus = self::QR_STATUS_MAP[$newStatus] ?? self::QR_STATUS_ACTIVE;

        $invoice->update(['status' => $newStatus]);

        if ($qrCodeStatus !== $qrCode->status) {
            $qrCode->status = $qrCodeStatus;
            $qrCode->save();
        }

        return $newStatus;
    }

    private function mapPaymentStatus(string $status): string
    {
        return self::PAYMENT_STATUS_MAP[strtoupper($status)] ?? 'UNKNOWN';
    }

    private function successResponse(Transaction $transaction, string $status, string $qrCodeStatus): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Webhook processed successfully',
            'data' => [
                'transaction_id' => $transaction->transaction_id,
                'invoice_status' => $status,
                'qr_code_status' => $qrCodeStatus,
                'processed_at' => now()->toIso8601String()
            ]
        ]);
    }

    private function validationErrorResponse(ValidationException $e): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid webhook payload',
            'errors' => $e->errors()
        ], 422);
    }

    private function errorResponse(\Exception $e): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to process webhook',
            'error_code' => 'WEBHOOK_PROCESSING_FAILED',
            'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
        ], 500);
    }
}
