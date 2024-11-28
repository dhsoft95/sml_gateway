<?php

namespace App\Console\Commands;

use App\Models\FailedCallback;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RetryFailedCallbacks extends Command
{
    protected $signature = 'callbacks:retry';
    protected $description = 'Retry failed payment callbacks';

    public function handle()
    {
        $failedCallbacks = FailedCallback::where('status', 'PENDING')
            ->where('next_retry_at', '<=', now())
            ->where('attempts', '<', 5)
            ->get();

        foreach ($failedCallbacks as $callback) {
            try {
                $response = Http::timeout(15)
                    ->withHeaders([
                        'X-API-Key' => config('services.psp.api_key'),
                        'Content-Type' => 'application/json',
                    ])
                    ->post($callback->callback_url, $callback->payload);

                if ($response->successful()) {
                    $callback->update(['status' => 'COMPLETED']);
                    Log::info('Callback retry successful', [
                        'transaction_id' => $callback->transaction_id
                    ]);
                } else {
                    $this->handleRetryFailure($callback, 'HTTP Error: ' . $response->status());
                }
            } catch (\Exception $e) {
                $this->handleRetryFailure($callback, $e->getMessage());
            }
        }
    }

    private function handleRetryFailure($callback, $error)
    {
        $attempts = $callback->attempts + 1;
        $nextRetry = now()->addMinutes(5 * pow(2, $attempts)); // Exponential backoff

        $callback->update([
            'attempts' => $attempts,
            'next_retry_at' => $nextRetry,
            'last_error' => $error,
            'status' => $attempts >= 5 ? 'FAILED' : 'PENDING'
        ]);

        Log::warning('Callback retry failed', [
            'transaction_id' => $callback->transaction_id,
            'attempts' => $attempts,
            'next_retry' => $nextRetry,
            'error' => $error
        ]);
    }
}
