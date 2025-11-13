<?php

namespace App\Filament\Resources\WalletTransactionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Morilog\Jalali\Jalalian;

class WalletRelationManager extends RelationManager
{
    protected static string $relationship = 'wallet';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label(__('site.user'))
                    ->relationship('user', 'first_name')
                    ->searchable()
                    ->preload()
                    ->disabled()
                    ->required(),
                Forms\Components\TextInput::make('balance')
                    ->label(__('site.balance'))
                    ->numeric()
                    ->disabled()
                    ->required(),
                Forms\Components\TextInput::make('currency')
                    ->label(__('site.currency'))
                    ->disabled()
                    ->required(),
                Forms\Components\Toggle::make('status')
                    ->label(__('site.status'))
                    ->disabled()
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
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
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        '1' => __('site.Active'),
                        '0' => __('site.Inactive'),
                    }),
                TextColumn::make('created_at')
                    ->label(__('site.created_at'))
                    ->dateTime('Y-m-d H:i:s')
                    ->formatStateUsing(fn ($state) => Jalalian::fromDateTime($state)->format('Y/m/d H:i:s'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('site.status'))
                    ->options([
                        1 => __('site.Active'),
                        0 => __('site.Inactive'),
                    ]),
                Tables\Filters\SelectFilter::make('currency')
                    ->label(__('site.currency'))
                    ->options([
                        'IRR' => 'IRR',
                    ]),
            ])
            ->headerActions([
                // No create action - read only
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions - read only
            ])
            ->defaultSort('created_at', 'desc');
    }
}