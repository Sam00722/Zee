<?php

namespace App\Filament\Brand\Resources;

use App\Filament\Brand\Resources\WithdrawalResource\Pages;
use App\Models\Withdrawal;
use App\Services\WithdrawalService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WithdrawalResource extends Resource
{
    protected static ?string $model = Withdrawal::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-circle';

    protected static ?string $navigationGroup = 'Transactions';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        $brand = auth()->user()->brands()->first();

        return $form
            ->schema([
                Forms\Components\Section::make('New Withdrawal Request')
                    ->description('Select a payment gateway and enter the amount you wish to withdraw.')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('payment_method_account_id')
                                    ->label('Payment Gateway')
                                    ->options(
                                        $brand
                                            ? $brand->withdrawalGateways()->get()->mapWithKeys(fn ($pma) => [$pma->id => $pma->name])
                                            : []
                                    )
                                    ->required()
                                    ->searchable()
                                    ->native(false)
                                    ->placeholder('Choose a gateway...')
                                    ->helperText(fn () => $brand && $brand->withdrawalGateways()->count() === 0
                                        ? 'No withdrawal gateways assigned yet. Contact the Super Admin to attach one to your brand.'
                                        : null),

                                Forms\Components\TextInput::make('amount')
                                    ->label('Amount')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0.01)
                                    ->prefix('$')
                                    ->placeholder('0.00')
                                    ->inputMode('decimal')
                                    ->helperText('Minimum withdrawal: $0.01'),
                            ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes (optional)')
                            ->placeholder('Add any reference or additional information for this withdrawal...')
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
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Withdrawal')
                    ->modalDescription('Approve this withdrawal? For Runa Payment, the claim link will appear in a popup so you can copy it.')
                    ->visible(fn (Withdrawal $record) => $record->status === Withdrawal::STATUS_PENDING)
                    ->action(function (Withdrawal $record) {
                        $result = app(WithdrawalService::class)->approve($record);
                        if ($result['success']) {
                            if (! empty($result['redemption_url'] ?? null)) {
                                $page = \Livewire\Livewire::current();
                                if ($page && method_exists($page, 'showRedemptionUrl')) {
                                    $page->showRedemptionUrl($result['redemption_url']);
                                }
                            }
                            Notification::make()
                                ->title($result['message'])
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title($result['message'])
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('deny')
                    ->label('Deny')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Deny Withdrawal')
                    ->modalDescription('Deny this withdrawal?')
                    ->visible(fn (Withdrawal $record) => $record->status === Withdrawal::STATUS_PENDING)
                    ->action(function (Withdrawal $record) {
                        $record->update(['status' => Withdrawal::STATUS_DENIED]);
                        Notification::make()
                            ->title('Withdrawal denied.')
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
            'index' => Pages\ListWithdrawals::route('/'),
            'create' => Pages\CreateWithdrawal::route('/create'),
        ];
    }

    public static function canCreate(): bool
    {
        $brand = auth()->user()->brands()->first();
        return $brand && $brand->can_withdraw;
    }
}
