<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    protected $fillable = [
        'brand_id',
        'payment_method_account_id',
        'user_id',
        'amount',
        'status',
        'notes',
        'paid_at',
        'metadata',
        'runa_order_id',
        'sent_at',
        'is_email',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'sent_at' => 'datetime',
        'metadata' => 'array',
        'is_email' => 'boolean',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DENIED = 'denied';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_FAILED = 'failed';

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function paymentMethodAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentMethodAccount::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
