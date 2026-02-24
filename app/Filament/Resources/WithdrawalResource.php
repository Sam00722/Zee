<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WithdrawalResource\Pages;
use App\Models\Withdrawal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WithdrawalResource extends Resource
{
    protected static ?string $model = Withdrawal::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-circle';

    protected static ?string $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('brand_id')->relationship('brand', 'name')->required(),
                Forms\Components\Select::make('payment_method_account_id')->relationship('paymentMethodAccount', 'name')->required(),
                Forms\Components\TextInput::make('amount')->numeric()->required(),
                Forms\Components\Select::make('status')->options(['pending' => 'Pending', 'completed' => 'Completed', 'denied' => 'Denied'])->required(),
                Forms\Components\Textarea::make('notes'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('brand.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('paymentMethodAccount.name')->label('Gateway')->sortable(),
                Tables\Columns\TextColumn::make('user_id')->label('User')->formatStateUsing(fn ($state, $record) => $record->user?->getFilamentName())->sortable(),
                Tables\Columns\TextColumn::make('amount')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('paid_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('brand')->relationship('brand', 'name'),
                Tables\Filters\SelectFilter::make('status')->options(['pending' => 'Pending', 'completed' => 'Completed', 'denied' => 'Denied']),
            ])
            ->actions([Tables\Actions\ViewAction::make()])
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
            'view' => Pages\ViewWithdrawal::route('/{record}'),
        ];
    }
}
