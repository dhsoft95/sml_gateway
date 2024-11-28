<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QRCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'qr_data',
        'control_number',
        'status',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime'
    ];

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_USED = 'USED';

    // Relationships
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    // Methods
    public function isValid(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->expires_at->isFuture();
    }

    public function markAsExpired(): void
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
            'expires_at' => now()
        ]);
    }

    public function markAsUsed(): void
    {
        $this->update(['status' => self::STATUS_USED]);
    }
}
