<?php

namespace App\Filament\Brand\Resources;

use App\Filament\Brand\Resources\DepositResource\Pages;
use App\Models\Deposit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DepositResource extends Resource
{
    protected static ?string $model = Deposit::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-circle';

    protected static ?string $navigationGroup = 'Transactions';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        $brand = auth()->user()->brands()->first();

        return $form
            ->schema([
                Forms\Components\Select::make('payment_method_account_id')
                    ->label('Payment Gateway')
                    ->options(
                        $brand
                            ? $brand->depositGateways()->get()->mapWithKeys(fn ($pma) => [$pma->id => $pma->name])
                            : []
                    )
                    ->required()
                    ->helperText(fn () => $brand && $brand->depositGateways()->count() === 0
                        ? 'No deposit gateways assigned yet. Ask Super Admin to attach one to your brand (Brand → Payment Gateways).'
                        : null),

                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->minValue(0.01),

                Forms\Components\Textarea::make('notes'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('brand_id', auth()->user()->brands()->first()?->id))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('paymentMethodAccount.name')->label('Gateway')->sortable(),
                Tables\Columns\TextColumn::make('amount')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('paid_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Deposit')
                    ->modalDescription('Mark this deposit as paid?')
                    ->visible(fn (Deposit $record) => $record->status === Deposit::STATUS_PENDING)
                    ->action(function (Deposit $record) {
                        $record->update([
                            'status'  => Deposit::STATUS_PAID,
                            'paid_at' => now(),
                        ]);
                        Notification::make()
                            ->title('Deposit approved.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDeposits::route('/'),
            'create' => Pages\CreateDeposit::route('/create'),
        ];
    }

    public static function canCreate(): bool
    {
        $brand = auth()->user()->brands()->first();

        return $brand && $brand->can_deposit;
    }
}
