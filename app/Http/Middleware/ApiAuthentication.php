<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiAuthentication
{
    public function handle(Request $request, Closure $next)
    {
        // Get API key from various possible sources
        $apiKey = $request->header('X-API-Key') ??
            $request->header('API-Key') ??
            $request->query('api_key') ??
            $request->input('api_key');

        // Get configured API key
        $validApiKey = config('services.psp.api_key');

        // Log for debugging
        Log::info('API Authentication', [
            'received_key' => $apiKey,
            'valid_key' => $validApiKey,
            'headers' => $request->headers->all(),
            'method' => $request->method(),
            'path' => $request->path()
        ]);

        if (!$apiKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'API key is missing',
                'error_code' => 'MISSING_API_KEY',
                'required_header' => 'X-API-Key'
            ], 401);
        }

        if ($apiKey !== $validApiKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid API key provided',
                'error_code' => 'INVALID_API_KEY'
            ], 401);
        }

        return $next($request);
    }
}
