<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class NotificationService
{
    /**
     * Maximum number of retry attempts for callbacks
     */
    const MAX_RETRIES = 3;

    /**
     * Retry delays in seconds (exponential backoff)
     */
    const RETRY_DELAYS = [30, 120, 300]; // 30 seconds, 2 minutes, 5 minutes

    /**
     * Send callback notification to merchant
     */
    public function sendCallback(Invoice $invoice, array $data): bool
    {
        $callbackData = $this->prepareCallbackData($invoice, $data);
        $signature = $this->generateCallbackSignature($callbackData);

        try {
            return $this->executeCallback($invoice->callback_url, $callbackData, $signature);
        } catch (Exception $e) {
            // If callback fails, queue it for retry
            $this->queueCallbackForRetry($invoice, $callbackData, 0);

            Log::error('Callback failed', [
                'invoice_id' => $invoice->external_id,
                'error' => $e->getMessage(),
                'data' => $callbackData
            ]);

            return false;
        }
    }

    /**
     * Prepare callback data
     */
    protected function prepareCallbackData(Invoice $invoice, array $data): array
    {
        return [
            'event_type' => 'payment_update',
            'invoice_id' => $invoice->external_id,
            'merchant_id' => $invoice->merchant_id,
            'amount' => $invoice->amount,
            'currency' => $invoice->currency,
            'status' => $data['status'],
            'transaction_id' => $data['transaction_id'] ?? null,
            'payment_provider' => $data['payment_provider'] ?? null,
            'processed_at' => $data['processed_at'] ?? now(),
            'metadata' => $invoice->metadata,
            'timestamp' => now()->timestamp,
            'error' => $data['error'] ?? null
        ];
    }

    /**
     * Generate signature for callback
     */
    protected function generateCallbackSignature(array $data): string
    {
        $secret = config('services.psp.webhook_secret');
        $payload = json_encode($data);
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Execute callback request
     */
    protected function executeCallback(string $url, array $data, string $signature): bool
    {
        $response = Http::timeout(10)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-PSP-Signature' => $signature,
                'X-PSP-Timestamp' => now()->timestamp
            ])
            ->post($url, $data);

        if (!$response->successful()) {
            throw new Exception("Callback failed with status: {$response->status()}");
        }

        Log::info('Callback successful', [
            'url' => $url,
            'invoice_id' => $data['invoice_id'],
            'status' => $data['status']
        ]);

        return true;
    }

    /**
     * Queue callback for retry
     */
    protected function queueCallbackForRetry(Invoice $invoice, array $data, int $attempts): void
    {
        if ($attempts >= self::MAX_RETRIES) {
            Log::error('Max callback retries reached', [
                'invoice_id' => $invoice->external_id,
                'data' => $data
            ]);
            return;
        }

        $delay = self::RETRY_DELAYS[$attempts];
        $cacheKey = "callback_retry:{$invoice->external_id}:{$attempts}";

        Cache::put($cacheKey, [
            'invoice_id' => $invoice->external_id,
            'data' => $data,
            'attempts' => $attempts + 1
        ], now()->addSeconds($delay));

        // You would typically use Laravel's scheduler to check for and process these retries
        Log::info('Callback queued for retry', [
            'invoice_id' => $invoice->external_id,
            'attempt' => $attempts + 1,
            'delay' => $delay
        ]);
    }

    /**
     * Process callback retries
     */
    public function processRetries(): void
    {
        $pattern = 'callback_retry:*';
        $keys = Cache::get($pattern);

        foreach ($keys as $key) {
            $retry = Cache::get($key);
            if (!$retry) continue;

            try {
                $invoice = Invoice::where('external_id', $retry['invoice_id'])->first();
                if (!$invoice) continue;

                $signature = $this->generateCallbackSignature($retry['data']);
                $success = $this->executeCallback($invoice->callback_url, $retry['data'], $signature);

                if ($success) {
                    Cache::forget($key);
                    Log::info('Retry callback successful', [
                        'invoice_id' => $invoice->external_id,
                        'attempt' => $retry['attempts']
                    ]);
                } else {
                    $this->queueCallbackForRetry($invoice, $retry['data'], $retry['attempts']);
                }
            } catch (Exception $e) {
                Log::error('Retry callback failed', [
                    'key' => $key,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Send payment confirmation notification
     */
    public function sendPaymentConfirmation(Transaction $transaction): void
    {
        $invoice = $transaction->invoice;

        $this->sendCallback($invoice, [
            'status' => 'CONFIRMED',
            'transaction_id' => $transaction->transaction_id,
            'payment_provider' => $transaction->payment_provider,
            'processed_at' => $transaction->processed_at
        ]);
    }

    /**
     * Send payment failure notification
     */
    public function sendPaymentFailure(Transaction $transaction, string $reason): void
    {
        $invoice = $transaction->invoice;

        $this->sendCallback($invoice, [
            'status' => 'FAILED',
            'transaction_id' => $transaction->transaction_id,
            'payment_provider' => $transaction->payment_provider,
            'processed_at' => now(),
            'error' => $reason
        ]);
    }

    /**
     * Send payment cancellation notification
     */
    public function sendPaymentCancellation(Transaction $transaction): void
    {
        $invoice = $transaction->invoice;

        $this->sendCallback($invoice, [
            'status' => 'CANCELLED',
            'transaction_id' => $transaction->transaction_id,
            'payment_provider' => $transaction->payment_provider,
            'processed_at' => now()
        ]);
    }
}
