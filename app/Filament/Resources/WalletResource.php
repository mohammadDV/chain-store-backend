<?php

namespace App\Filament\Resources;

use App\Filament\Filters\UserIdFilter;
use App\Filament\Resources\WalletResource\Pages\ListWallets;
use App\Filament\Resources\WalletResource\Pages\ViewWallet;
use App\Filament\Resources\WalletResource\RelationManagers\WalletTransactionsRelationManager;
use Domain\Notification\Services\NotificationService;
use Domain\Wallet\Models\Wallet;
use Domain\Wallet\Models\WalletTransaction;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Morilog\Jalali\Jalalian;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wallet';

    protected static string|\UnitEnum|null $navigationGroup = 'Financial';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('site.wallets');
    }

    public static function getModelLabel(): string
    {
        return __('site.wallet');
    }

    public static function getPluralModelLabel(): string
    {
        return __('site.wallets');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('site.Wallet Management');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('site.wallet_information'))
                    ->schema([
                        Select::make('user_id')
                            ->label(__('site.user'))
                            ->relationship('user', 'first_name')
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->required(),
                        TextInput::make('balance')
                            ->label(__('site.balance'))
                            ->numeric()
                            ->disabled()
                            ->required(),
                        TextInput::make('currency')
                            ->label(__('site.currency'))
                            ->disabled()
                            ->required()
                            ->maxLength(3),
                        Toggle::make('status')
                            ->label(__('site.status'))
                            ->disabled()
                            ->default(true),
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
                TextColumn::make('user.nickname')
                    ->label(__('site.user'))
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => route('filament.admin.resources.users.view', ['record' => $record->user_id]))
                    ->openUrlInNewTab(),
                TextColumn::make('balance')
                    ->label(__('site.balance'))
                    ->money('IRR')
                    ->sortable(),
                TextColumn::make('currency')
                    ->label(__('site.currency'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('site.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '1' => 'success',
                        '0' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        '1' => __('site.Active'),
                        '0' => __('site.Inactive'),
                        default => $state,
                    }),
                TextColumn::make('created_at')
                    ->label(__('site.created_at'))
                    ->dateTime('Y-m-d H:i:s')
                    ->formatStateUsing(fn ($state) => Jalalian::fromDateTime($state)->format('Y/m/d H:i:s'))
                    ->sortable(),
            ])
            ->filters([
                UserIdFilter::make(),
                SelectFilter::make('status')
                    ->label(__('site.status'))
                    ->options([
                        1 => __('site.Active'),
                        0 => __('site.Inactive'),
                    ]),
                SelectFilter::make('currency')
                    ->label(__('site.currency'))
                    ->options([
                        'IRR' => 'IRR',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('adjust_balance')
                    ->label(__('site.adjust_balance'))
                    ->icon('heroicon-o-currency-dollar')
                    ->color('warning')
                    ->schema([
                        Select::make('adjustment_type')
                            ->label(__('site.adjustment_type'))
                            ->options([
                                'increase' => __('site.increase'),
                                'decrease' => __('site.decrease'),
                            ])
                            ->required()
                            ->default('increase'),
                        TextInput::make('amount')
                            ->label(__('site.amount'))
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01),
                        Textarea::make('description')
                            ->label(__('site.description'))
                            ->required()
                            ->placeholder(__('site.adjustment_description_placeholder'))
                            ->rows(3),
                        Toggle::make('send_notification')
                            ->label(__('site.send_notification_to_user'))
                            ->helperText(__('site.send_notification_to_user_help'))
                            ->default(true),
                    ])
                    ->action(function (Wallet $wallet, array $data): void {
                        $amount = (float) $data['amount'];
                        $adjustmentType = $data['adjustment_type'];
                        $description = $data['description'];
                        $sendNotification = $data['send_notification'] ?? false;

                        // Calculate the actual amount to add/subtract
                        $transactionAmount = $adjustmentType === 'increase' ? $amount : -$amount;

                        // Create transaction record
                        WalletTransaction::createTransaction(
                            wallet: $wallet,
                            amount: $transactionAmount,
                            type: $adjustmentType === 'increase' ? 'deposit' : 'withdrawal',
                            description: $description,
                            status: WalletTransaction::COMPLETED
                        );

                        // Send notification to user if requested
                        if ($sendNotification) {
                            $notificationTitle = $adjustmentType === 'increase'
                                ? __('site.wallet_balance_increased_title')
                                : __('site.wallet_balance_decreased_title');

                            $notificationContent = $adjustmentType === 'increase'
                                ? __('site.wallet_balance_increased_content', ['amount' => number_format($amount, 2), 'currency' => __('site.currency')])
                                : __('site.wallet_balance_decreased_content', ['amount' => number_format($amount, 2), 'currency' => __('site.currency')]);

                            NotificationService::create([
                                'title' => $notificationTitle,
                                'content' => $notificationContent,
                                'id' => $wallet->id,
                                'type' => NotificationService::WALLET,
                            ], $wallet->user);
                        }

                        // Refresh the wallet to get updated balance
                        $wallet->refresh();

                        Notification::make()
                            ->title(__('site.balance_adjusted_successfully'))
                            ->body(__('site.new_balance').': '.number_format($wallet->balance, 2).' '.$wallet->currency)
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('site.adjust_wallet_balance'))
                    ->modalDescription(__('site.adjust_wallet_balance_description'))
                    ->modalSubmitActionLabel(__('site.confirm_adjustment'))
                    ->modalCancelActionLabel(__('site.cancel')),
            ])
            ->toolbarActions([
                // No bulk actions - read only
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            WalletTransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWallets::route('/'),
            'view' => ViewWallet::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }
}
