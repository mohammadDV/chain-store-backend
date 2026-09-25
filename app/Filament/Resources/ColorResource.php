<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourceAuthorization;
use App\Filament\Concerns\ScopesQueryByAdminBrands;
use App\Filament\Resources\ColorResource\Pages\CreateColor;
use App\Filament\Resources\ColorResource\Pages\EditColor;
use App\Filament\Resources\ColorResource\Pages\ListColors;
use App\Filament\Resources\ColorResource\Pages\ViewColor;
use Domain\Product\Models\Color;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ColorResource extends Resource
{
    use ChecksResourceAuthorization;
    use ScopesQueryByAdminBrands;

    protected static ?string $model = Color::class;

    protected static function permissionPrefix(): string
    {
        return 'colors';
    }

    protected static function brandScopeStrategy(): string
    {
        return 'brands';
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-swatch';

    protected static ?int $navigationSort = 12;

    public static function getNavigationGroup(): ?string
    {
        return __('site.product_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('site.colors');
    }

    public static function getModelLabel(): string
    {
        return __('site.color');
    }

    public static function getPluralModelLabel(): string
    {
        return __('site.colors');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('site.color_information'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('title')
                                    ->label(__('site.title'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('code')
                                    ->label(__('site.code'))
                                    ->required()
                                    ->maxLength(7)
                                    ->placeholder('#000000')
                                    ->helperText(__('site.color_code_help'))
                                    ->regex('/^#[0-9A-Fa-f]{6}$/')
                                    ->validationMessages([
                                        'regex' => __('site.invalid_color_code'),
                                    ]),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->label(__('site.status'))
                                    ->options([
                                        1 => __('site.Active'),
                                        0 => __('site.Inactive'),
                                    ])
                                    ->default(0)
                                    ->required(),
                                TextInput::make('priority')
                                    ->label(__('site.priority'))
                                    ->numeric()
                                    ->default(0),
                            ]),
                    ])->columns(1),
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
                TextColumn::make('code')
                    ->label(__('site.code'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => strtoupper($state))
                    ->badge()
                    ->color(fn (string $state): string => $state)
                    ->copyable()
                    ->copyMessage(__('site.copied')),
                TextColumn::make('status')
                    ->label(__('site.status'))
                    ->badge()
                    ->color(fn (int $state): string => match ($state) {
                        1 => 'success',
                        0 => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (int $state): string => $state === 1 ? __('site.Active') : __('site.Inactive')),
                TextColumn::make('priority')
                    ->label(__('site.priority'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TagsColumn::make('brands.title')
                    ->label(__('site.brands'))
                    ->limit(3)
                    ->separator(',')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('product_count')
                    ->label(__('site.products'))
                    ->counts('product')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('site.created_at'))
                    ->dateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('site.status'))
                    ->options([
                        1 => __('site.Active'),
                        0 => __('site.Inactive'),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('id', 'desc');
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
            'index' => ListColors::route('/'),
            'create' => CreateColor::route('/create'),
            'view' => ViewColor::route('/{record}'),
            'edit' => EditColor::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }
}
