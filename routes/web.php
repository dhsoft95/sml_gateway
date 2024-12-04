<?php

use App\Http\Controllers\MerchantAuthController;
use App\Http\Controllers\MerchantDashboardController;
use App\Http\Controllers\MerchantPortalController;
use Illuminate\Support\Facades\Route;



// routes/web.php

Route::get('/', function () {
    return redirect('merchant/login');
});



Route::prefix('merchant')->name('merchant.')->group(function () {
    // Auth routes
    Route::middleware('guest')->group(function () {
        Route::get('/login', [MerchantAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [MerchantAuthController::class, 'login']);
        Route::get('/register', [MerchantAuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [MerchantAuthController::class, 'register']);
    });

    // Protected routes
    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', [MerchantDashboardController::class, 'index'])->name('dashboard');
        Route::post('/generate-api-key', [MerchantDashboardController::class, 'generateApiKey'])->name('generate-api-key');
        Route::post('/logout', [MerchantAuthController::class, 'logout'])->name('logout'); // Added this line
    });
});
