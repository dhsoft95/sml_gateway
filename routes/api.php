<?php

use App\Http\Middleware\ApiAuthentication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\{API\V1\CallbackController,
    API\V1\InvoiceController,
    API\V1\PaymentController,
    API\V1\WebhookController};


// All routes are prefixed with 'api' by default in Laravel 11
Route::prefix('v1')->group(function () {
    // Invoice routes with rate limiting
    Route::middleware('throttle:invoice')->group(function () {
        Route::post('/invoices', [InvoiceController::class, 'store']);
        Route::get('/invoices/{external_id}', [InvoiceController::class, 'show']);
        Route::get('/invoices/{external_id}/status', [InvoiceController::class, 'status']);
        Route::get('/invoices/{external_id}/qr', [InvoiceController::class, 'showQrCode']);
    });

    Route::middleware(['throttle:payment', 'payment.api'])
        ->withoutMiddleware([ApiAuthentication::class])
        ->group(function () {
            Route::controller(PaymentController::class)->prefix('payments')->name('payments.')->group(function () {
                Route::post('/process', 'process')->name('process');
                Route::post('/confirm', 'confirm')->name('confirm');
                Route::get('/{transaction_id}/status', 'status')->name('status');
            });
        });

    // Webhook routes with rate limiting
    Route::post('/webhooks/simba', [WebhookController::class, 'handleSimbaWebhook'])
        ->middleware('throttle:webhook')
        ->withoutMiddleware(['api.auth']);


    // Callback routes with general API rate limiting
    Route::middleware('throttle:api')->group(function () {
        Route::post('/callback/{external_id}', [CallbackController::class, 'handle']);
    });

});


