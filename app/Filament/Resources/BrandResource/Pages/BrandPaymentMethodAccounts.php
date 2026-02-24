<?php

namespace App\Filament\Resources\BrandResource\Pages;

use App\Filament\Resources\BrandResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BrandPaymentMethodAccounts extends ManageRelatedRecords
{
    protected static string $resource = BrandResource::class;

    protected static string $relationship = 'paymentMethodAccounts';

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    public static function getNavigationLabel(): string
    {
        return 'Payment Gateways';
    }

    public function getTitle(): string
    {
        return "{$this->getOwnerRecord()->name} – Payment Method Accounts";
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('payment_method_type')->label('Gateway Type'),
                Tables\Columns\IconColumn::make('enabled')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->recordSelectOptionsQuery(function (Builder $query): Builder {
                        return $query->where('enabled', true)->orderBy('name');
                    })
                    ->preloadRecordSelect()
                    ->after(function (): void {
                        Notification::make()
                            ->title('Payment method account attached.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
