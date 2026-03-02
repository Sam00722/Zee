<?php

namespace App\Filament\Brand\Resources\DepositResource\Pages;

use App\Filament\Brand\Resources\DepositResource;
use App\Models\Deposit;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewDeposit extends ViewRecord
{
    protected static string $resource = DepositResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to Deposits')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),
        ];
    }

    public function getTitle(): string
    {
        return match ($this->record->status) {
            Deposit::STATUS_PAID   => 'Payment Successful',
            Deposit::STATUS_FAILED => 'Payment Failed',
            default                => 'Deposit #'.$this->record->id,
        };
    }
}
