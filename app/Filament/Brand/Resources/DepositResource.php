<?php

namespace App\Filament\Brand\Resources;

use App\Enums\PaymentMethodType;
use App\Filament\Brand\Resources\DepositResource\Pages;
use App\Models\Deposit;
use App\Models\PaymentMethodAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
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

        $isPayAgency = fn (Get $get): bool => PaymentMethodAccount::find($get('payment_method_account_id'))
            ?->payment_method_type === PaymentMethodType::PAYAGENCY->value;

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
                                    ->live()
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

                Forms\Components\Section::make('Card Details')
                    ->description('Enter your card information to complete the deposit.')
                    ->icon('heroicon-o-credit-card')
                    ->visible($isPayAgency)
                    ->schema([
                        Forms\Components\TextInput::make('card_number')
                            ->label('Card Number')
                            ->placeholder('1234 5678 9012 3456')
                            ->required($isPayAgency)
                            ->maxLength(19)
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('card_expiry_month')
                                    ->label('Expiry Month')
                                    ->options(collect(range(1, 12))->mapWithKeys(
                                        fn ($m) => [str_pad($m, 2, '0', STR_PAD_LEFT) => str_pad($m, 2, '0', STR_PAD_LEFT)]
                                    ))
                                    ->native(false)
                                    ->placeholder('MM')
                                    ->required($isPayAgency),

                                Forms\Components\Select::make('card_expiry_year')
                                    ->label('Expiry Year')
                                    ->options(collect(range(now()->year, now()->year + 10))->mapWithKeys(
                                        fn ($y) => [$y => $y]
                                    ))
                                    ->native(false)
                                    ->placeholder('YYYY')
                                    ->required($isPayAgency),

                                Forms\Components\TextInput::make('card_cvv')
                                    ->label('CVV')
                                    ->placeholder('123')
                                    ->password()
                                    ->revealable()
                                    ->maxLength(4)
                                    ->required($isPayAgency),
                            ]),
                    ]),

                Forms\Components\Section::make('Personal Information')
                    ->description('Your personal details will be submitted to the payment gateway.')
                    ->icon('heroicon-o-user')
                    ->visible($isPayAgency)
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('customer_first_name')
                                    ->label('First Name')
                                    ->required($isPayAgency)
                                    ->default(fn () => auth()->user()?->first_name),

                                Forms\Components\TextInput::make('customer_last_name')
                                    ->label('Last Name')
                                    ->required($isPayAgency)
                                    ->default(fn () => auth()->user()?->last_name),

                                Forms\Components\TextInput::make('customer_email')
                                    ->label('Email')
                                    ->email()
                                    ->required($isPayAgency)
                                    ->default(fn () => auth()->user()?->email),

                                Forms\Components\TextInput::make('phone_number')
                                    ->label('Phone Number')
                                    ->placeholder('7654233212')
                                    ->tel()
                                    ->required($isPayAgency),
                            ]),
                    ]),

                Forms\Components\Section::make('Billing Address')
                    ->description('Billing address associated with your card.')
                    ->icon('heroicon-o-map-pin')
                    ->visible($isPayAgency)
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->label('Address')
                            ->placeholder('64 Hertingfordbury Rd')
                            ->required($isPayAgency)
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('city')
                                    ->label('City')
                                    ->placeholder('Newport')
                                    ->required($isPayAgency),

                                Forms\Components\TextInput::make('state')
                                    ->label('State / County')
                                    ->placeholder('GB')
                                    ->required($isPayAgency),

                                Forms\Components\TextInput::make('zip')
                                    ->label('ZIP / Postcode')
                                    ->placeholder('TF10 8DF')
                                    ->required($isPayAgency),

                                Forms\Components\TextInput::make('country')
                                    ->label('Country Code')
                                    ->placeholder('GB')
                                    ->helperText('ISO 2-letter code, e.g. GB, US')
                                    ->maxLength(3)
                                    ->required($isPayAgency),
                            ]),
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
