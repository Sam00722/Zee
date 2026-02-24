<?php

namespace App\Filament\Resources\PaymentMethodAccountResource\Pages;

use App\Filament\Resources\PaymentMethodAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentMethodAccounts extends ListRecords
{
    protected static string $resource = PaymentMethodAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
