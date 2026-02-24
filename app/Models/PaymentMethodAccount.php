<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethodAccount extends Model
{
    protected $fillable = [
        'payment_method_id',
        'name',
        'type',
        'payment_method_type',
        'withdrawal_payment_type',
        'enabled',
        'is_default',
        'credentials',
        'success_url',
        'cancel_url',
    ];

    protected $casts = [
        'credentials' => 'array',
        'enabled' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'brand_payment_method_account', 'payment_method_account_id', 'brand_id')
            ->withPivot('enable')
            ->withTimestamps();
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }
}
