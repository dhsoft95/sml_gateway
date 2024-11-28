<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'transaction_id',
        'control_number',
        'provider_reference',
        'amount',
        'currency',
        'payment_method',
        'status',
        'payer_details',
        'provider_response',
        'processed_at'

    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payer_details' => 'array',
        'provider_response' => 'array',
        'processed_at' => 'datetime'
    ];

    const METHOD_SIMBA_MONEY = 'SIMBA_MONEY';
    const METHOD_MOBILE_MONEY = 'MOBILE_MONEY';
    const METHOD_CARD = 'CARD';



    public const STATUS_INITIATED = 'INITIATED';
    public const STATUS_PROCESSING = 'PROCESSING';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_FAILED = 'FAILED';

    // Relationships
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    // Methods
    public function markAsConfirmed(): void
    {
        $this->update([
            'status' => self::STATUS_CONFIRMED,
            'processed_at' => now()
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'processed_at' => now()
        ]);
    }

    public function isProcessable(): bool
    {
        return in_array($this->status, [self::STATUS_INITIATED, self::STATUS_PROCESSING]);
    }
}
