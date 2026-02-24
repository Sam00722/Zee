<?php

namespace App\Filament\Resources\BrandResource\Pages;

use App\Filament\Resources\BrandResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBrand extends EditRecord
{
    protected static string $resource = BrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('paymentMethodAccounts')
                ->label('Payment Gateways')
                ->url(fn (): string => BrandResource::getUrl('payment-method-accounts', ['record' => $this->record]))
                ->icon('heroicon-o-wallet'),
            Actions\DeleteAction::make(),
        ];
    }
}
