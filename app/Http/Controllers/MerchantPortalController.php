<?php
// app/Http/Controllers/MerchantPortalController.php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class MerchantPortalController extends Controller
{
    public function showRegister()
    {
        return view('merchant.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'business_name' => 'required|string|max:255',
            'notification_email' => 'required|email',
            'callback_url' => 'nullable|url'
        ]);

        DB::beginTransaction();
        try {
            // Create user first
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password'])
            ]);

            // Create merchant profile
            $merchant = Merchant::create([
                'user_id' => $user->id,
                'business_name' => $validated['business_name'],
                'merchant_code' => 'M' . strtoupper(Str::random(8)),
                'notification_email' => $validated['notification_email'],
                'callback_url' => $validated['callback_url'],
                'webhook_secret' => Str::random(32),
                'status' => 'INACTIVE'
            ]);

            DB::commit();
            auth()->login($user);

            return redirect()->route('merchant.dashboard')
                ->with('success', 'Registration successful! Please wait for account activation.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Registration failed. Please try again.']);
        }
    }

    public function dashboard()
    {
        $merchant = auth()->user()->merchant;
        return view('merchant.dashboard', compact('merchant'));
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

    public function logout(Request $request): \Illuminate\Foundation\Application|\Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
