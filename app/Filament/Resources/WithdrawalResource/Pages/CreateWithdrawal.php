<?php

namespace App\Filament\Resources\WithdrawalResource\Pages;

use App\Filament\Resources\WithdrawalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWithdrawal extends CreateRecord
{
    protected static string $resource = WithdrawalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        // Auto-set paid_at when admin creates a withdrawal already marked as completed
        if (($data['status'] ?? '') === 'completed' && empty($data['paid_at'])) {
            $data['paid_at'] = now();
        }

        return $data;
    }
}
