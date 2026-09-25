<?php

namespace App\Filament\Resources\WalletTransactionResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Morilog\Jalali\Jalalian;

class WalletRelationManager extends RelationManager
{
    protected static string $relationship = 'wallet';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                TextInput::make('balance')
                    ->label(__('site.balance'))
                    ->numeric()
                    ->disabled()
                    ->required(),
                TextInput::make('currency')
                    ->label(__('site.currency'))
                    ->disabled()
                    ->required(),
                Toggle::make('status')
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
