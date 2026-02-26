<?php

namespace App\Filament\Brand\Resources;

use App\Filament\Brand\Resources\DepositResource\Pages;
use App\Models\Deposit;
use Filament\Forms;
use Filament\Forms\Form;
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
                Forms\Components\Section::make('New Deposit Request')
                    ->description('Select a payment gateway and enter the amount you wish to deposit.')
                    ->icon('heroicon-o-arrow-down-circle')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('payment_method_account_id')
                                    ->label('Payment Gateway')
                                    ->options(
                                        $brand
                                            ? $brand->depositGateways()->get()->mapWithKeys(fn ($pma) => [$pma->id => $pma->name])
                                            : []
                                    )
                                    ->required()
                                    ->searchable()
                                    ->native(false)
                                    ->placeholder('Choose a gateway...')
                                    ->helperText(fn () => $brand && $brand->depositGateways()->count() === 0
                                        ? 'No deposit gateways assigned yet. Contact the Super Admin to attach one to your brand.'
                                        : null),

                                Forms\Components\TextInput::make('amount')
                                    ->label('Amount')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0.01)
                                    ->prefix('$')
                                    ->placeholder('0.00')
                                    ->inputMode('decimal')
                                    ->helperText('Minimum deposit: $0.01'),
                            ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes (optional)')
                            ->placeholder('Add any reference or additional information for this deposit...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
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
            ->actions([])
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
