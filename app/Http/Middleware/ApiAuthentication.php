<?php

namespace App\Http\Middleware;

use App\Models\Merchant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiAuthentication
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-Key') ??
            $request->header('API-Key') ??
            $request->query('api_key') ??
            $request->input('api_key');

        if (!$apiKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'API key is missing',
                'error_code' => 'MISSING_API_KEY',
                'required_header' => 'X-API-Key'
            ], 401);
        }

        $merchant = Merchant::where('api_key', $apiKey)
        ->where('status', 'ACTIVE')
            ->first();

        Log::info('API Auth Debug', [
            'provided_key' => $apiKey,
            'hashed_key' => bcrypt($apiKey),
            'found_merchant' => Merchant::where('api_key', bcrypt($apiKey))->exists(),
            'all_merchant_keys' => Merchant::pluck('api_key')
        ]);


        if (!$merchant) {
            Log::warning('Invalid API key used', [
                'received_key' => substr($apiKey, 0, 8) . '...',
                'method' => $request->method(),
                'path' => $request->path()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid API key provided',
                'error_code' => 'INVALID_API_KEY'
            ], 401);
        }

        $request->merge(['merchant' => $merchant]);
        return $next($request);
    }
}
