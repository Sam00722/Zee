<?php

namespace App\Filament\Resources\DepositResource\Pages;

use App\Filament\Resources\DepositResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeposit extends EditRecord
{
    protected static string $resource = DepositResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Auto-set paid_at when status is changed to paid
        if (($data['status'] ?? '') === 'paid' && empty($data['paid_at'])) {
            $data['paid_at'] = now();
        }

        return $data;
    }
}
