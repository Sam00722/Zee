<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    protected $fillable = ['name', 'type', 'enabled'];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function paymentMethodAccounts(): HasMany
    {
        return $this->hasMany(PaymentMethodAccount::class);
    }
}
