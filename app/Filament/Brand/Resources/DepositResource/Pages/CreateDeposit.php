<?php

namespace App\Filament\Brand\Resources\DepositResource\Pages;

use App\Filament\Brand\Resources\DepositResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDeposit extends CreateRecord
{
    protected static string $resource = DepositResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $brand = auth()->user()->brands()->first();
        $data['brand_id'] = $brand->id;
        $data['user_id'] = auth()->id();
        $data['status'] = 'pending';
        return $data;
    }
}
