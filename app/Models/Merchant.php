<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Merchant extends Model
{
    protected $fillable = [
        'user_id',
        'business_name',
        'merchant_code',
        'callback_url',
        'notification_email',
        'webhook_secret',
        'api_key',
        'api_key_generated_at',
        'status',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'api_key_generated_at' => 'datetime'
    ];

    public function generateApiKey(): string
    {
        $apiKey = 'sk_live_' . Str::random(32);
        $this->update([
            'api_key' => bcrypt($apiKey),
            'webhook_secret' => Str::random(32),
            'api_key_generated_at' => now()
        ]);
        return $apiKey;
    }
    public function regenerateApiKey(): string
    {
        return $this->generateApiKey();
    }

    public function verifyApiKey(string $key): bool
    {
        return password_verify($key, $this->api_key);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

}
