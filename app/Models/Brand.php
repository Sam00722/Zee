<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Brand extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'timezone',
        'can_deposit',
        'can_withdraw',
    ];

    protected $casts = [
        'can_deposit' => 'boolean',
        'can_withdraw' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Brand $brand) {
            if (empty($brand->slug)) {
                $brand->slug = Str::slug($brand->name);
            }
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'brand_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function paymentMethodAccounts(): BelongsToMany
    {
        return $this->belongsToMany(
            PaymentMethodAccount::class,
            'brand_payment_method_account',
            'brand_id',
            'payment_method_account_id'
        )->withPivot('enable')->withTimestamps();
    }

    public function getEnabledPaymentMethodAccounts()
    {
        return $this->paymentMethodAccounts()->wherePivot('enable', true)->get();
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function depositGateways()
    {
        return $this->paymentMethodAccounts()->where('type', 'deposit')->wherePivot('enable', true);
    }

    public function withdrawalGateways()
    {
        return $this->paymentMethodAccounts()->where('type', 'withdrawal')->wherePivot('enable', true);
    }
}
