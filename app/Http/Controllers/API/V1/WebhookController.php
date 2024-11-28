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

class WebhookController extends Controller
{
    public function handleSimbaWebhook(Request $request)
    {
        Log::info('Webhook received', $request->all());

        try {
            $payload = $request->validate([
                'invoice_id' => 'required|string',
                'transaction_id' => 'required|string',
                'status' => 'required|string',
                'amount' => 'required',
                'currency' => 'required|string',
                'provider_reference' => 'required|string',
                'payment_method' => 'required|string',
                'processed_at' => 'required',
                'payer_details' => 'required|array',
                'payer_details.phone' => 'required|string',
                'payer_details.email' => 'nullable|email',
                'control_number' => 'required|string'
            ]);

            return DB::transaction(function() use ($payload) {
                $qrCode = QRCode::where('control_number', $payload['control_number'])->firstOrFail();
                $invoice = $qrCode->invoice;

                if (!$this->verifyAmount($invoice, $payload['amount'])) {
                    throw new \Exception('Amount mismatch detected');
                }

                try {
                    $transaction = Transaction::where('transaction_id', $payload['transaction_id'])->first();

                    if ($transaction) {
                        // If transaction exists, verify it matches current payload
                        if ($transaction->invoice_id != $invoice->id ||
                            $transaction->amount != $payload['amount']) {
                            Log::error('Transaction mismatch', [
                                'existing' => $transaction->toArray(),
                                'payload' => $payload
                            ]);
                            throw new \Exception('Transaction data mismatch');
                        }

                        // Update status if needed
                        $newStatus = $this->mapPaymentStatus($payload['status']);
                        if ($transaction->status !== $newStatus) {
                            $transaction->update(['status' => $newStatus]);
                            $invoice->update(['status' => $newStatus]);
                        }
                    } else {
                        // Create new transaction
                        $transaction = Transaction::create([
                            'invoice_id' => $invoice->id,
                            'transaction_id' => $payload['transaction_id'],
                            'control_number' => $payload['control_number'],
                            'amount' => $payload['amount'],
                            'currency' => $payload['currency'],
                            'status' => $this->mapPaymentStatus($payload['status']),
                            'payment_method' => $payload['payment_method'],
                            'payer_details' => $payload['payer_details'],
                            'processed_at' => $payload['processed_at'],
                            'provider_response' => $payload
                        ]);

                        $invoice->update([
                            'status' => $this->mapPaymentStatus($payload['status'])
                        ]);
                    }

                    dispatch(new ProcessCallbackJob($invoice, $transaction));

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Webhook processed successfully'
                    ]);

                } catch (QueryException $e) {
                    if ($e->getCode() === '23000') {
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Transaction already processed'
                        ]);
                    }
                    throw $e;
                }
            });

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process webhook',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function verifyAmount(Invoice $invoice, float $paidAmount): bool
    {
        return abs($invoice->amount - $paidAmount) < 0.01;
    }

    private function mapPaymentStatus(string $status): string
    {
        return match($status) {
            'COMPLETED' => 'PAID',
            'FAILED', 'REJECTED' => 'FAILED',
            'EXPIRED' => 'EXPIRED',
            'PENDING' => 'PENDING',
            default => 'UNKNOWN'
        };
    }
}
