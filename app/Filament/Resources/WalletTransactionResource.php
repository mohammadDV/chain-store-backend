<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletTransactionResource\Pages;
use Domain\Wallet\Models\WalletTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Morilog\Jalali\Jalalian;

class WalletTransactionResource extends Resource
{
    protected static ?string $model = WalletTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'Financial';

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('site.wallet_transactions');
    }

    public static function getModelLabel(): string
    {
        return __('site.wallet_transaction');
    }

    public static function getPluralModelLabel(): string
    {
        return __('site.wallet_transactions');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('site.Wallet Management');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('site.transaction_information'))
                    ->schema([
                        Forms\Components\Select::make('wallet_id')
                            ->label(__('site.wallet'))
                            ->relationship('wallet', 'id')
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->required(),
                        Forms\Components\Select::make('user_id')
                            ->label(__('site.user'))
                            ->relationship('user', 'first_name')
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->required(),
                        Forms\Components\TextInput::make('type')
                            ->label(__('site.type'))
                            ->disabled()
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->label(__('site.amount'))
                            ->numeric()
                            ->disabled()
                            ->required(),
                        Forms\Components\TextInput::make('currency')
                            ->label(__('site.currency'))
                            ->disabled()
                            ->required()
                            ->maxLength(3),
                        Forms\Components\TextInput::make('status')
                            ->label(__('site.status'))
                            ->disabled()
                            ->required(),
                        Forms\Components\TextInput::make('reference')
                            ->label(__('site.reference'))
                            ->disabled()
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label(__('site.description'))
                            ->disabled()
                            ->rows(3),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('site.id'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('wallet.user.nickname')
                    ->label(__('site.user'))
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => route('filament.admin.resources.users.view', ['record' => $record->wallet->user_id]))
                    ->openUrlInNewTab(),
                TextColumn::make('type')
                    ->label(__('site.type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'deposit' => 'success',
                        'withdrawal' => 'warning',
                        'transfer' => 'info',
                        'purchase' => 'danger',
                        'refund' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'deposit' => __('site.deposit'),
                        'withdrawal' => __('site.withdrawal'),
                        'transfer' => __('site.transfer'),
                        'purchase' => __('site.purchase'),
                        'refund' => __('site.refund'),
                        default => $state,
                    }),
                TextColumn::make('amount')
                    ->label(__('site.amount'))
                    ->money('IRR')
                    ->color(fn ($record) => $record->amount >= 0 ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('currency')
                    ->label(__('site.currency'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('site.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'completed' => __('site.completed'),
                        'pending' => __('site.pending'),
                        'failed' => __('site.failed'),
                        default => $state,
                    }),
                TextColumn::make('reference')
                    ->label(__('site.reference'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('created_at')
                    ->label(__('site.created_at'))
                    ->dateTime('Y-m-d H:i:s')
                    ->formatStateUsing(fn ($state) => Jalalian::fromDateTime($state)->format('Y/m/d H:i:s'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('wallet.user_id')
                    ->label(__('site.user'))
                    ->relationship('wallet.user', 'nickname', fn ($query) => $query->whereNotNull('nickname'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('site.type'))
                    ->options([
                        'deposit' => __('site.deposit'),
                        'withdrawal' => __('site.withdrawal'),
                        'transfer' => __('site.transfer'),
                        'purchase' => __('site.purchase'),
                        'refund' => __('site.refund'),
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('site.status'))
                    ->options([
                        'completed' => __('site.completed'),
                        'pending' => __('site.pending'),
                        'failed' => __('site.failed'),
                    ]),
                Tables\Filters\SelectFilter::make('currency')
                    ->label(__('site.currency'))
                    ->options([
                        'IRR' => 'IRR',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions - read only
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\WalletTransactionResource\RelationManagers\WalletRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWalletTransactions::route('/'),
            'view' => Pages\ViewWalletTransaction::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
