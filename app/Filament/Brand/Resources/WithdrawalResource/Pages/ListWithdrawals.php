<?php

namespace App\Filament\Brand\Resources\WithdrawalResource\Pages;

use App\Filament\Brand\Resources\WithdrawalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWithdrawals extends ListRecords
{
    protected static string $resource = WithdrawalResource::class;

    /** Claim link to show in popup after Runa approval (set by approve action). */
    public ?string $redemptionUrlToShow = null;

    protected static string $view = 'filament.brand.pages.list-withdrawals';

    public function showRedemptionUrl(string $url): void
    {
        $this->redemptionUrlToShow = $url;
    }

    public function clearRedemptionUrl(): void
    {
        $this->redemptionUrlToShow = null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
