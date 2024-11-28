<?php

namespace App\Providers;

use DateTimeImmutable;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Rate Limiter configurations
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->getClientIp());
        });

        RateLimiter::for('payment', function (Request $request) {
            return Limit::perMinute(30)->by($request->getClientIp());
        });

        RateLimiter::for('webhook', function (Request $request) {
            return Limit::perMinute(120)->by($request->getClientIp());
        });

        RateLimiter::for('invoice', function (Request $request) {
            return Limit::perMinute(20)->by($request->getClientIp());
        });

        // Passport configurations with explicit expiration times
        Passport::tokensExpireIn(
            new DateTimeImmutable('now +1 day')
        );

        Passport::refreshTokensExpireIn(
            new DateTimeImmutable('now +7 days')
        );

        Passport::personalAccessTokensExpireIn(
            new DateTimeImmutable('now +1 year')
        );

        // Enable token pruning (optional)
//        Passport::pruneRevokedTokens();
    }
}
