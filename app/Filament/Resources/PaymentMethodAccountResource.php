<?php

namespace App\Filament\Resources;

use App\Enums\PaymentMethodType;
use App\Filament\Resources\PaymentMethodAccountResource\Pages;
use App\Models\PaymentMethodAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Builder;

class PaymentMethodAccountResource extends Resource
{
    protected static ?string $model = PaymentMethodAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationGroup = 'Cashier';

    protected static ?int $navigationSort = 5;

    protected static ?string $label = 'Payment Method Accounts';

    public static function getSlug(): string
    {
        return 'payment-method-accounts';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('payment_method_id')
                            ->relationship(
                                'paymentMethod',
                                'name',
                                fn (Builder $query): Builder => $query->where('enabled', true)
                            )
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('type')
                            ->options([
                                'deposit' => 'Deposit',
                                'withdrawal' => 'Withdrawal',
                                'cash_app_deposit' => 'Cash App Deposit',
                                'crypto_deposit' => 'Crypto Deposit',
                                'crypto_withdrawal' => 'Crypto Withdrawal',
                            ])
                            ->required()
                            ->native(false)
                            ->live(),
                        Forms\Components\Select::make('withdrawal_payment_type')
                            ->options(['RTP' => 'RTP', 'ACH' => 'ACH'])
                            ->native(false)
                            ->visible(fn (Get $get): bool => $get('type') === 'withdrawal'),
                        Forms\Components\Select::make('payment_method_type')
                            ->options(
                                collect(PaymentMethodType::cases())
                                    ->mapWithKeys(fn ($case) => [$case->value => ucfirst(str_replace('-', ' ', $case->value))])
                                    ->toArray()
                            )
                            ->required()
                            ->native(false)
                            ->searchable(),
                    ])->columns(2),
                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('enabled'),
                        Forms\Components\Toggle::make('is_default'),
                    ])->columns(2),
                Forms\Components\Section::make('Credentials')
                    ->schema([
                        Forms\Components\KeyValue::make('credentials')
                            ->label('API Credentials')
                            ->addActionLabel('Add Credential')
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('URLs')
                    ->schema([
                        Forms\Components\TextInput::make('success_url')->url()->maxLength(255),
                        Forms\Components\TextInput::make('cancel_url')->url()->maxLength(255),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('paymentMethod.name')->label('Payment Method')->sortable(),
                Tables\Columns\TextColumn::make('type')->sortable(),
                Tables\Columns\TextColumn::make('payment_method_type')->label('Gateway Type')->sortable(),
                Tables\Columns\IconColumn::make('enabled')->boolean()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentMethodAccounts::route('/'),
            'create' => Pages\CreatePaymentMethodAccount::route('/create'),
            'edit' => Pages\EditPaymentMethodAccount::route('/{record}/edit'),
        ];
    }
}
