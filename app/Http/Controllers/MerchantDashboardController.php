<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MerchantDashboardController extends Controller
{
    public function index()
    {
        $merchant = auth()->user()->merchant;

        // Get some basic stats for the dashboard
        $stats = [
            'total_transactions' => 1234, // Replace with actual data
            'total_revenue' => 45678,
            'total_customers' => 892,
            'avg_response_time' => '1.2s'
        ];

        return view('merchant.dashboard', compact('merchant', 'stats'));
    }

    public function generateApiKey()
    {
        $merchant = auth()->user()->merchant;

        if ($merchant->status !== 'ACTIVE') {
            return back()->withErrors(['error' => 'Only active merchants can generate API keys.']);
        }

        $merchant->update([
            'api_key' => Str::random(32),
            'api_key_generated_at' => now()
        ]);

        return back()->with('success', 'API key generated successfully.');
    }

}
