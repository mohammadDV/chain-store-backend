<?php

namespace App\Filament\Resources\WalletResource\Pages;

use App\Filament\Resources\WalletResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Domain\Wallet\Models\WalletTransaction;
use Domain\Notification\Services\NotificationService;

class ViewWallet extends ViewRecord
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('adjust_balance')
                ->label(__('site.adjust_balance'))
                ->icon('heroicon-o-currency-dollar')
                ->color('warning')
                ->form([
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
                ->action(function (array $data): void {
                    $wallet = $this->getRecord();
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
                            ? __('site.wallet_balance_increased_content', ['amount' => number_format($amount, 2), 'currency' => $wallet->currency])
                            : __('site.wallet_balance_decreased_content', ['amount' => number_format($amount, 2), 'currency' => $wallet->currency]);

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
                        ->body(__('site.new_balance') . ': ' . number_format($wallet->balance, 2) . ' ' . $wallet->currency)
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading(__('site.adjust_wallet_balance'))
                ->modalDescription(__('site.adjust_wallet_balance_description'))
                ->modalSubmitActionLabel(__('site.confirm_adjustment'))
                ->modalCancelActionLabel(__('site.cancel')),
        ];
    }
}
