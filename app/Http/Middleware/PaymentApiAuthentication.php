<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentApiAuthentication
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-Payment-API-Key');

        if (!$apiKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment API key is missing',
                'error_code' => 'MISSING_PAYMENT_API_KEY',
                'required_header' => 'X-Payment-API-Key'
            ], 401);
        }

        if ($apiKey !== config('services.payment.api_key')) {
            Log::warning('Invalid payment API key used', [
                'method' => $request->method(),
                'path' => $request->path()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid payment API key provided',
                'error_code' => 'INVALID_PAYMENT_API_KEY'
            ], 401);
        }

        return $next($request);
    }
}
