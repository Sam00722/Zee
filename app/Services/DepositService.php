<?php

namespace App\Services;

use App\Models\Deposit;

class DepositService
{
    public function updateStatus(Deposit $deposit, string $status, ?\DateTimeInterface $paidAt = null): void
    {
        $deposit->update(array_filter([
            'status' => $status,
            'paid_at' => $paidAt ?? ($status === Deposit::STATUS_PAID ? now() : null),
        ]));
    }
}
