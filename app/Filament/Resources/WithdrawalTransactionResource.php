<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WithdrawalTransactionResource\Pages;
use Domain\Wallet\Models\WithdrawalTransaction;
use Domain\Wallet\Models\WalletTransaction;
use Domain\Notification\Services\NotificationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Morilog\Jalali\Jalalian;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\FileUpload;

class WithdrawalTransactionResource extends Resource
{
    protected static ?string $model = WithdrawalTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationGroup = 'Financial';

    protected static ?int $navigationSort = 9;

    public static function getNavigationLabel(): string
    {
        return __('site.withdrawal_transactions');
    }

    public static function getModelLabel(): string
    {
        return __('site.withdrawal_transaction');
    }

    public static function getPluralModelLabel(): string
    {
        return __('site.withdrawal_transactions');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('site.Wallet Management');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('site.withdrawal_information'))
                    ->schema([
                        Forms\Components\Select::make('wallet_id')
                            ->label(__('site.wallet'))
                            ->relationship('wallet', 'id')
                            ->disabled()
                            ->required(),
                        Forms\Components\Select::make('user_id')
                            ->label(__('site.user'))
                            ->relationship('wallet.user', 'nickname', fn ($query) => $query->whereNotNull('nickname'))
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
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label(__('site.status'))
                            ->options([
                                WithdrawalTransaction::PENDING => __('site.pending'),
                                WithdrawalTransaction::COMPLETED => __('site.completed'),
                                WithdrawalTransaction::REJECT => __('site.reject'),
                            ])
                            ->disabled()
                            ->required(),
                        Forms\Components\TextInput::make('reference')
                            ->label(__('site.reference'))
                            ->disabled()
                            ->required(),
                        Forms\Components\TextInput::make('card')
                            ->label(__('site.card'))
                            ->disabled(),
                        Forms\Components\TextInput::make('sheba')
                            ->label(__('site.sheba'))
                            ->disabled(),
                        Forms\Components\Textarea::make('description')
                            ->label(__('site.description'))
                            ->disabled()
                            ->rows(3),
                        Forms\Components\Textarea::make('reason')
                            ->label(__('site.reason'))
                            ->disabled()
                            ->rows(3),

                        FileUpload::make('image')
                        ->label(__('site.project_image'))
                        ->placeholder(__('site.upload_project_image'))
                        ->image()
                        ->imageEditor()
                        ->disk('s3')
                        ->directory('/projects/images')
                        ->visible(fn ($record) => !empty($record->image)),
                        // Forms\Components\ViewField::make('image')
                        //     ->label(__('site.image'))
                        //     ->view('filament.components.image-display')
                        //     ->visible(fn ($record) => !empty($record->image)),
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
                TextColumn::make('amount')
                    ->label(__('site.amount'))
                    ->money('IRR')
                    ->sortable(),
                TextColumn::make('currency')
                    ->label(__('site.currency'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('site.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        WithdrawalTransaction::COMPLETED => 'success',
                        WithdrawalTransaction::PENDING => 'warning',
                        WithdrawalTransaction::REJECT => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        WithdrawalTransaction::COMPLETED => __('site.completed'),
                        WithdrawalTransaction::PENDING => __('site.pending'),
                        WithdrawalTransaction::REJECT => __('site.reject'),
                        default => $state,
                    }),
                TextColumn::make('reference')
                    ->label(__('site.reference'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('card')
                    ->label(__('site.card'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),
                TextColumn::make('sheba')
                    ->label(__('site.sheba'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),
                TextColumn::make('created_at')
                    ->label(__('site.created_at'))
                    ->dateTime('Y-m-d H:i:s')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => Jalalian::fromDateTime($state)->format('Y/m/d H:i:s'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('wallet.user_id')
                    ->label(__('site.user'))
                    ->relationship('wallet.user', 'nickname', fn ($query) => $query->whereNotNull('nickname'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('site.status'))
                    ->options([
                        WithdrawalTransaction::PENDING => __('site.pending'),
                        WithdrawalTransaction::COMPLETED => __('site.completed'),
                        WithdrawalTransaction::REJECT => __('site.reject'),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Action::make('complete')
                    ->label(__('site.complete'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === WithdrawalTransaction::PENDING)
                    ->form([
                        Textarea::make('reason')
                            ->label(__('site.completion_reason'))
                            ->placeholder(__('site.completion_reason_placeholder'))
                            ->rows(3),
                        Forms\Components\FileUpload::make('image')
                            ->label(__('site.completion_image'))
                            ->image()
                            ->imageEditor()
                            ->imageCropAspectRatio('16:9')
                            ->imageResizeTargetWidth('1920')
                            ->imageResizeTargetHeight('1080')
                            ->disk('s3')
                            ->directory('/withdrawal-transactions/completion')
                            ->helperText(__('site.completion_image_help')),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading(__('site.complete_withdrawal'))
                    ->modalDescription(__('site.complete_withdrawal_description'))
                    ->modalSubmitActionLabel(__('site.confirm_complete'))
                    ->modalCancelActionLabel(__('site.cancel'))
                    ->action(function (WithdrawalTransaction $withdrawalTransaction, array $data): void {
                        try {
                            DB::beginTransaction();

                            $updateData = [
                                'status' => WithdrawalTransaction::COMPLETED,
                            ];

                            if (isset($data['reason']) && !empty($data['reason'])) {
                                $updateData['reason'] = $data['reason'];
                            }

                            if (isset($data['image'])) {
                                $updateData['image'] = $data['image'];
                            }

                            $withdrawalTransaction->update($updateData);

                            // Send notification to user
                            NotificationService::create([
                                'title' => __('site.withdrawal_completed_title'),
                                'content' => __('site.withdrawal_completed_content', ['amount' => number_format($withdrawalTransaction->amount, 2), 'currency' => __('site.currency')]),
                                'id' => $withdrawalTransaction->id,
                                'type' => NotificationService::WITHDRAWAL,
                            ], $withdrawalTransaction->wallet->user);

                            DB::commit();

                            Notification::make()
                                ->title(__('site.withdrawal_completed_successfully'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            Notification::make()
                                ->title(__('site.withdrawal_completion_failed'))
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('reject')
                    ->label(__('site.reject'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === WithdrawalTransaction::PENDING)
                    ->form([
                        Textarea::make('reason')
                            ->label(__('site.rejection_reason'))
                            ->placeholder(__('site.rejection_reason_placeholder'))
                            ->rows(3),
                        Forms\Components\FileUpload::make('image')
                            ->label(__('site.rejection_image'))
                            ->image()
                            ->imageEditor()
                            ->imageCropAspectRatio('16:9')
                            ->imageResizeTargetWidth('1920')
                            ->imageResizeTargetHeight('1080')
                            ->disk('s3')
                            ->directory('/withdrawal-transactions/rejection')
                            ->helperText(__('site.rejection_image_help')),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading(__('site.reject_withdrawal'))
                    ->modalDescription(__('site.reject_withdrawal_description'))
                    ->modalSubmitActionLabel(__('site.confirm_reject'))
                    ->modalCancelActionLabel(__('site.cancel'))
                    ->action(function (WithdrawalTransaction $withdrawalTransaction, array $data): void {
                        try {
                            DB::beginTransaction();

                            // Create refund transaction to return money to wallet
                            WalletTransaction::createTransaction(
                                wallet: $withdrawalTransaction->wallet,
                                amount: $withdrawalTransaction->amount,
                                type: WalletTransaction::REFUND,
                                description: __('site.wallet_transaction_withdrawal_refund', ['reference' => $withdrawalTransaction->reference]),
                                status: WalletTransaction::COMPLETED
                            );

                            $updateData = [
                                'status' => WithdrawalTransaction::REJECT,
                            ];

                            if (isset($data['reason']) && !empty($data['reason'])) {
                                $updateData['reason'] = $data['reason'];
                            }

                            if (isset($data['image'])) {
                                $updateData['image'] = $data['image'];
                            }

                            // Update withdrawal status
                            $withdrawalTransaction->update($updateData);

                            // Send notification to user
                            NotificationService::create([
                                'title' => __('site.withdrawal_rejected_title'),
                                'content' => __('site.withdrawal_rejected_content', ['amount' => number_format($withdrawalTransaction->amount, 2), 'currency' => __('site.currency'), 'reason' => $data['reason'] ?? 'No reason provided']),
                                'id' => $withdrawalTransaction->id,
                                'type' => NotificationService::WITHDRAWAL,
                            ], $withdrawalTransaction->wallet->user);

                            DB::commit();

                            Notification::make()
                                ->title(__('site.withdrawal_rejected_successfully'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            Notification::make()
                                ->title(__('site.withdrawal_rejection_failed'))
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                // No bulk actions - individual processing required
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWithdrawalTransactions::route('/'),
            'view' => Pages\ViewWithdrawalTransaction::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', WithdrawalTransaction::PENDING)->count();
    }
}
