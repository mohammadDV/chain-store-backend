<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Filament\Resources\UserResource;
use Domain\Notification\Models\Notification;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Morilog\Jalali\Jalalian;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static ?int $navigationSort = 19;

    public static function getNavigationLabel(): string
    {
        return __('site.notifications');
    }

    public static function getModelLabel(): string
    {
        return __('site.notification');
    }

    public static function getPluralModelLabel(): string
    {
        return __('site.notifications');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('site.notification_details'))
                    ->schema([
                        TextInput::make('title')
                            ->label(__('site.title'))
                            ->required()
                            ->disabled()
                            ->maxLength(255),
                        Textarea::make('content')
                            ->label(__('site.content'))
                            ->required()
                            ->disabled()
                            ->rows(4),
                        Select::make('user_id')
                            ->label(__('site.user'))
                            ->relationship('user', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->getFilamentName())
                            ->disabled()
                            ->required(),
                        Toggle::make('status')
                            ->label(__('site.status'))
                            ->disabled()
                            ->default(true),
                        Toggle::make('read')
                            ->label(__('site.read_status'))
                            ->disabled()
                            ->default(false),
                        TextInput::make('model_type')
                            ->label(__('site.model_type'))
                            ->disabled(),
                        TextInput::make('model_id')
                            ->label(__('site.model_id'))
                            ->disabled()
                            ->numeric(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('site.table_id'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('title')
                    ->label(__('site.title'))
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('content')
                    ->label(__('site.content'))
                    ->searchable()
                    ->limit(100)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 100) {
                            return null;
                        }
                        return $state;
                    }),
                TextColumn::make('user.nickname')
                    ->label(__('site.user'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->user?->getFilamentName())
                    ->url(fn ($record) => $record->user ? UserResource::getUrl('view', ['record' => $record->user]) : null)
                    ->color('primary')
                    ->openUrlInNewTab()
                    ->weight('medium'),
                IconColumn::make('read')
                    ->label(__('site.read_status'))
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn ($state) => $state ? __('site.read') : __('site.unread')),
                IconColumn::make('status')
                    ->label(__('site.status'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('model_type')
                    ->label(__('site.type'))
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-'),
                TextColumn::make('created_at')
                    ->label(__('site.created_at'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => $state ? Jalalian::fromDateTime($state)->format('Y-m-d H:i:s') : null)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('site.status'))
                    ->options([
                        1 => __('site.Active'),
                        0 => __('site.Inactive'),
                    ]),
                SelectFilter::make('read')
                    ->label(__('site.read_status'))
                    ->options([
                        1 => __('site.read'),
                        0 => __('site.unread'),
                    ]),
                SelectFilter::make('user')
                    ->label(__('site.user'))
                    ->relationship('user', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->getFilamentName())
                    ->searchable(),
                Filter::make('created_at')
                    ->label(__('site.created_at'))
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label(__('site.from_date')),
                        Forms\Components\DatePicker::make('created_until')
                            ->label(__('site.to_date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                // Tables\Actions\ViewAction::make()
                //     ->label(__('site.view')),
            ])
            ->bulkActions([
                // No bulk actions to prevent deletion
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
            'index' => Pages\ListNotifications::route('/'),
            'view' => Pages\ViewNotification::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
