<?php

namespace App\Filament\Resources\WalletResource\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Morilog\Jalali\Jalalian;

class WalletTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'walletTransaction';

    protected static ?string $recordTitleAttribute = 'reference';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    ->required(),
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
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
                TextColumn::make('description')
                    ->label(__('site.description'))
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('site.created_at'))
                    ->dateTime('Y-m-d H:i:s')
                    ->formatStateUsing(fn ($state) => Jalalian::fromDateTime($state)->format('Y/m/d H:i:s'))
                    ->sortable(),
            ])
            ->filters([
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
            ])
            ->headerActions([
                // No create action - read only
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                // No bulk actions - read only
            ])
            ->defaultSort('created_at', 'desc');
    }
}
