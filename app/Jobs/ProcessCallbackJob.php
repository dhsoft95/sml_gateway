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

    public function __construct(
        private Invoice $invoice,
        private Transaction $transaction
    ) {}

    public function handle(): void
    {
        $callbackData = [
            'invoice_id' => $this->invoice->external_id,
            'control_number' => $this->transaction->control_number,
            'status' => $this->transaction->status,
            'amount' => $this->transaction->amount,
            'currency' => $this->transaction->currency,
            'transaction_id' => $this->transaction->transaction_id,
            'provider_reference' => $this->transaction->provider_reference,
            'payment_method' => $this->transaction->payment_method,
            'payer_details' => $this->transaction->payer_details,
            'processed_at' => $this->transaction->processed_at,
            'metadata' => $this->invoice->metadata
        ];

        try {
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->invoice->callback_url, $callbackData);

            if (!$response->successful()) {
                throw new \Exception("Callback failed with status: {$response->status()}");
            }

            Log::info('Callback sent', [
                'invoice_id' => $this->invoice->external_id,
                'status_code' => $response->status(),
                'response' => $response->json()
            ]);
        } catch (\Exception $e) {
            Log::error('Callback failed', [
                'invoice_id' => $this->invoice->external_id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            $this->release(
                $this->attempts() * 300
            );
        }
    }
}
