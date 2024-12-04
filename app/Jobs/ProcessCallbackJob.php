<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Transaction;
use App\Exceptions\CallbackException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProcessCallbackJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $maxExceptions = 3;
    public $backoff = [10, 60, 180];
    public $timeout = 30;

    protected Invoice $invoice;
    protected Transaction $transaction;
    protected array $callbackData;

    public function __construct(Invoice $invoice, Transaction $transaction)
    {
        $this->invoice = $invoice;
        $this->transaction = $transaction;
    }

    public function uniqueId(): string
    {
        return $this->transaction->transaction_id;
    }

    protected function validateCallbackUrl(): bool
    {
        return Validator::make(
            ['url' => $this->invoice->callback_url],
            ['url' => ['required', 'url', 'regex:/^https?:\/\//i']]
        )->passes();
    }

    protected function prepareCallbackData(): array
    {
        $data = [
            'invoice_id' => $this->invoice->external_id,
            'transaction_id' => $this->transaction->transaction_id,
            'status' => $this->transaction->status,
            'amount' => [
                'value' => $this->transaction->amount,
                'currency' => $this->transaction->currency,
                'formatted' => number_format($this->transaction->amount, 2) . ' ' . $this->transaction->currency
            ],
            'provider_reference' => $this->transaction->provider_reference,
            'processed_at' => $this->transaction->processed_at->toIso8601String(),
            'payer_details' => $this->transaction->payer_details,
            'merchant_id' => $this->invoice->merchant->merchant_code,
            'metadata' => $this->invoice->metadata
        ];

        $data['signature'] = hash_hmac(
            'sha256',
            json_encode($data),
            config('services.simba.webhook_secret')
        );

        return $data;
    }

    protected function sendCallback(): void
    {
        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'User-Agent' => 'SimbaMoneyCallback/1.0',
                'X-Callback-Token' => config('services.simba.callback_token'),
                'X-Transaction-ID' => $this->transaction->transaction_id
            ])
            ->post($this->invoice->callback_url, $this->callbackData);

        if (!$response->successful()) {
            throw new CallbackException(
                "Callback failed with status: {$response->status()}, Body: " .
                substr($response->body(), 0, 500)
            );
        }

        Log::info('Callback sent successfully', [
            'transaction_id' => $this->transaction->transaction_id,
            'status_code' => $response->status(),
            'response' => $response->json()
        ]);
    }

    protected function validateModels(): void
    {
        $this->invoice->refresh();
        $this->transaction->refresh();

        if (!$this->invoice->exists || !$this->transaction->exists) {
            throw new CallbackException('Required models not found');
        }

        if (!$this->invoice->merchant) {
            throw new CallbackException('Invoice merchant not found');
        }
    }

    public function handle(): void
    {
        try {
            DB::beginTransaction();

            Log::info('Processing callback for transaction', [
                'transaction_id' => $this->transaction->transaction_id,
                'invoice_id' => $this->invoice->external_id
            ]);

            $this->validateModels();

            if (!$this->validateCallbackUrl()) {
                throw new CallbackException('Invalid callback URL');
            }

            $this->callbackData = $this->prepareCallbackData();
            $this->sendCallback();

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Callback processing failed', [
                'transaction_id' => $this->transaction->transaction_id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            if ($this->attempts() < $this->tries) {
                throw $e;
            }

            $this->fail($e);
        }
    }

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
