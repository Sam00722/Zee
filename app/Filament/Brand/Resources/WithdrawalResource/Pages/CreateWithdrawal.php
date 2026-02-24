<?php

namespace App\Filament\Brand\Resources\WithdrawalResource\Pages;

use App\Filament\Brand\Resources\WithdrawalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWithdrawal extends CreateRecord
{
    protected static string $resource = WithdrawalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $brand = auth()->user()->brands()->first();
        $data['brand_id'] = $brand->id;
        $data['user_id'] = auth()->id();
        $data['status'] = 'pending';
        return $data;
    }
}
