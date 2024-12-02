<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessCallbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * Maximum number of exceptions to allow before failing.
     */
    public $maxExceptions = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [10, 60, 180]; // Retry after 10s, 1min, 3min

    /**
     * The invoice instance.
     */
    protected Invoice $invoice;

    /**
     * The transaction instance.
     */
    protected Transaction $transaction;

    /**
     * Create a new job instance.
     */
    public function __construct(Invoice $invoice, Transaction $transaction)
    {
        $this->invoice = $invoice;
        $this->transaction = $transaction;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Processing callback for transaction', [
                'transaction_id' => $this->transaction->transaction_id,
                'invoice_id' => $this->invoice->external_id
            ]);

            // Prepare callback data
            $callbackData = [
                'invoice_id' => $this->invoice->external_id,
                'transaction_id' => $this->transaction->transaction_id,
                'status' => $this->transaction->status,
                'amount' => [
                    'value' => $this->transaction->amount,
                    'currency' => $this->transaction->currency,
                    'formatted' => number_format($this->transaction->amount, 2) . ' ' . $this->transaction->currency
                ],
                'provider_reference' => $this->transaction->provider_reference,
                'processed_at' => $this->transaction->processed_at,
                'payer_details' => $this->transaction->payer_details,
                'merchant_id' => $this->invoice->merchant_id,
                'metadata' => $this->invoice->metadata
            ];

            // Generate signature
            $callbackData['signature'] = hash_hmac(
                'sha256',
                json_encode($callbackData),
                config('services.simba.webhook_secret')
            );

            // Send callback
            $response = Http::timeout(15)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'SimbaMoneyCallback/1.0',
                    'X-Callback-Token' => config('services.simba.callback_token'),
                    'X-Transaction-ID' => $this->transaction->transaction_id
                ])
                ->post($this->invoice->callback_url, $callbackData);

            if (!$response->successful()) {
                Log::error('Callback request failed', [
                    'transaction_id' => $this->transaction->transaction_id,
                    'status_code' => $response->status(),
                    'response' => $response->json()
                ]);

                throw new \Exception(
                    "Callback failed with status: {$response->status()}, Body: " .
                    substr($response->body(), 0, 500)
                );
            }

            Log::info('Callback sent successfully', [
                'transaction_id' => $this->transaction->transaction_id,
                'status_code' => $response->status(),
                'response' => $response->json()
            ]);

        } catch (\Exception $e) {
            Log::error('Callback processing failed', [
                'transaction_id' => $this->transaction->transaction_id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            // If we haven't exceeded max retries, throw the exception to trigger a retry
            if ($this->attempts() < $this->tries) {
                throw $e;
            }

            $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Callback job failed permanently', [
            'transaction_id' => $this->transaction->transaction_id,
            'invoice_id' => $this->invoice->external_id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}
