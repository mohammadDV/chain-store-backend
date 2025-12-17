<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscountResource\Pages;
use Domain\Product\Models\Discount;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?int $navigationSort = 13;

    public static function getNavigationLabel(): string
    {
        return __('site.discounts');
    }

    public static function getModelLabel(): string
    {
        return __('site.discount');
    }

    public static function getPluralModelLabel(): string
    {
        return __('site.discounts');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('site.discount_information'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('code')
                                    ->label(__('site.code'))
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                Select::make('type')
                                    ->label(__('site.type'))
                                    ->options([
                                        Discount::TYPE_PERCENTAGE => __('site.percentage'),
                                        Discount::TYPE_FIXED => __('site.fixed'),
                                    ])
                                    ->default(Discount::TYPE_PERCENTAGE)
                                    ->required()
                                    ->native(false),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('value')
                                    ->label(__('site.value'))
                                    ->numeric()
                                    ->required()
                                    ->step(0.01)
                                    ->prefix(fn ($get) => $get('type') === Discount::TYPE_PERCENTAGE ? '%' : '$'),
                                TextInput::make('max_value')
                                    ->label(__('site.max_value'))
                                    ->numeric()
                                    ->step(0.01)
                                    ->prefix('$')
                                    ->nullable()
                                    ->visible(fn ($get) => $get('type') === Discount::TYPE_PERCENTAGE)
                                    ->helperText(__('site.max_value_help')),
                            ]),
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('expire_date')
                                    ->label(__('site.expire_date'))
                                    ->nullable()
                                    ->native(false),
                                Toggle::make('visible')
                                    ->label(__('site.visible'))
                                    ->default(false)
                                    ->helperText(__('site.visible_help')),
                            ]),
                        Toggle::make('active')
                            ->label(__('site.active'))
                            ->default(true),
                    ]),
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
                TextColumn::make('code')
                    ->label(__('site.code'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage(__('site.copied'))
                    ->copyMessageDuration(1500),
                TextColumn::make('type')
                    ->label(__('site.type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Discount::TYPE_PERCENTAGE => 'info',
                        Discount::TYPE_FIXED => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Discount::TYPE_PERCENTAGE => __('site.percentage'),
                        Discount::TYPE_FIXED => __('site.fixed'),
                        default => $state,
                    }),
                TextColumn::make('value')
                    ->label(__('site.value'))
                    ->formatStateUsing(fn ($record, $state) => $record->type === Discount::TYPE_PERCENTAGE ? $state . '%' : '$' . $state)
                    ->sortable(),
                TextColumn::make('max_value')
                    ->label(__('site.max_value'))
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('expire_date')
                    ->label(__('site.expire_date'))
                    ->date('Y/m/d')
                    ->sortable()
                    ->color(fn ($record) => $record->expire_date && $record->expire_date < now() ? 'danger' : null),
                IconColumn::make('visible')
                    ->label(__('site.visible'))
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray'),
                IconColumn::make('active')
                    ->label(__('site.active'))
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray'),
                TextColumn::make('created_at')
                    ->label(__('site.created_at'))
                    ->dateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('site.type'))
                    ->options([
                        Discount::TYPE_PERCENTAGE => __('site.percentage'),
                        Discount::TYPE_FIXED => __('site.fixed'),
                    ]),
                SelectFilter::make('visible')
                    ->label(__('site.visible'))
                    ->options([
                        1 => __('site.yes'),
                        0 => __('site.no'),
                    ]),
                SelectFilter::make('active')
                    ->label(__('site.active'))
                    ->options([
                        1 => __('site.Active'),
                        0 => __('site.Inactive'),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListDiscounts::route('/'),
            'create' => Pages\CreateDiscount::route('/create'),
            'view' => Pages\ViewDiscount::route('/{record}'),
            'edit' => Pages\EditDiscount::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}

