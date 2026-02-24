<?php

namespace App\Filament\Resources\PaymentMethodAccountResource\Pages;

use App\Filament\Resources\PaymentMethodAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentMethodAccount extends EditRecord
{
    protected static string $resource = PaymentMethodAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
