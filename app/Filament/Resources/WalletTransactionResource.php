<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourceAuthorization;
use App\Filament\Filters\UserIdFilter;
use App\Filament\Resources\WalletTransactionResource\Pages\ListWalletTransactions;
use App\Filament\Resources\WalletTransactionResource\Pages\ViewWalletTransaction;
use App\Filament\Resources\WalletTransactionResource\RelationManagers\WalletRelationManager;
use Domain\Wallet\Models\WalletTransaction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Morilog\Jalali\Jalalian;

class WalletTransactionResource extends Resource
{
    use ChecksResourceAuthorization;

    protected static ?string $model = WalletTransaction::class;

    protected static function permissionPrefix(): string
    {
        return 'wallet_transactions';
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string|\UnitEnum|null $navigationGroup = 'Financial';

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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('site.transaction_information'))
                    ->schema([
                        TextInput::make('wallet_id')
                            ->label(__('site.wallet'))
                            ->disabled()
                            ->required(),
                        TextInput::make('user_id')
                            ->label(__('site.user'))
                            ->formatStateUsing(function ($state, $record): string {
                                $user = $record?->user;

                                if (! $user) {
                                    return filled($state) ? (string) $state : '-';
                                }

                                $name = trim((string) ($user->nickname ?: $user->first_name ?: ''));

                                return $name !== '' ? $name.' (#'.$user->id.')' : '#'.$user->id;
                            })
                            ->disabled(),
                        TextInput::make('type')
                            ->label(__('site.type'))
                            ->disabled()
                            ->required(),
                        TextInput::make('amount')
                            ->label(__('site.amount'))
                            ->numeric()
                            ->disabled()
                            ->required(),
                        TextInput::make('currency')
                            ->label(__('site.currency'))
                            ->disabled()
                            ->required()
                            ->maxLength(3),
                        TextInput::make('status')
                            ->label(__('site.status'))
                            ->disabled()
                            ->required(),
                        TextInput::make('reference')
                            ->label(__('site.reference'))
                            ->disabled()
                            ->required(),
                        Textarea::make('description')
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
                UserIdFilter::make(fn (Builder $query, int $userId): Builder => $query->whereHas(
                    'wallet',
                    fn (Builder $walletQuery) => $walletQuery->where('user_id', $userId)
                )),
                SelectFilter::make('type')
                    ->label(__('site.type'))
                    ->options([
                        'deposit' => __('site.deposit'),
                        'withdrawal' => __('site.withdrawal'),
                        'transfer' => __('site.transfer'),
                        'purchase' => __('site.purchase'),
                        'refund' => __('site.refund'),
                    ]),
                SelectFilter::make('status')
                    ->label(__('site.status'))
                    ->options([
                        'completed' => __('site.completed'),
                        'pending' => __('site.pending'),
                        'failed' => __('site.failed'),
                    ]),
                SelectFilter::make('currency')
                    ->label(__('site.currency'))
                    ->options([
                        'IRR' => 'IRR',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                // No bulk actions - read only
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            WalletRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWalletTransactions::route('/'),
            'view' => ViewWalletTransaction::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }
}
