<?php

namespace App\Filament\Resources\WithdrawalResource\Pages;

use App\Filament\Resources\WithdrawalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWithdrawal extends EditRecord
{
    protected static string $resource = WithdrawalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Auto-set paid_at when status is changed to completed
        if (($data['status'] ?? '') === 'completed' && empty($data['paid_at'])) {
            $data['paid_at'] = now();
        }

        return $data;
    }
}
