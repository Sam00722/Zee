<?php

namespace App\Filament\Resources\DepositResource\Pages;

use App\Filament\Resources\DepositResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDeposit extends CreateRecord
{
    protected static string $resource = DepositResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        // Auto-set paid_at when admin creates a deposit already marked as paid
        if (($data['status'] ?? '') === 'paid' && empty($data['paid_at'])) {
            $data['paid_at'] = now();
        }

        return $data;
    }
}
