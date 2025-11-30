<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Filament\Resources\BrandResource\RelationManagers\BannersRelationManager;
use Domain\Brand\Models\Brand;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?int $navigationSort = 12;

    public static function getNavigationLabel(): string
    {
        return __('site.brands');
    }

    public static function getModelLabel(): string
    {
        return __('site.brand');
    }

    public static function getPluralModelLabel(): string
    {
        return __('site.brands');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('site.brand_information'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('title')
                                    ->label(__('site.title'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('slug')
                                    ->label(__('site.slug'))
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                            ]),
                        Textarea::make('description')
                            ->label(__('site.description'))
                            ->maxLength(2048)
                            ->rows(4)
                            ->columnSpanFull(),
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->label(__('site.status'))
                                    ->options([
                                        1 => __('site.Active'),
                                        0 => __('site.Inactive'),
                                    ])
                                    ->default(1)
                                    ->required(),
                                TextInput::make('priority')
                                    ->label(__('site.priority'))
                                    ->numeric()
                                    ->default(0),
                            ]),
                        Select::make('colors')
                            ->label(__('site.colors'))
                            ->relationship('colors', 'title')
                            ->searchable()
                            ->multiple()
                            ->preload()
                            ->helperText(__('site.select_brand_colors'))
                            ->columnSpanFull(),
                    ]),
                Section::make(__('site.media'))
                    ->schema([
                        FileUpload::make('logo')
                            ->label(__('site.brand_logo'))
                            ->placeholder(__('site.upload_brand_logo'))
                            ->image()
                            ->imageEditor()
                            ->disk('s3')
                            ->directory('brands/logos')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->columnSpanFull(),
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
                ImageColumn::make('logo')
                    ->label(__('site.brand_logo'))
                    ->disk('s3')
                    ->visibility('public')
                    ->extraImgAttributes(['loading' => 'lazy'])
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->circular()
                    ->size(40),
                TextColumn::make('title')
                    ->label(__('site.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('site.slug'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                TextColumn::make('colors_count')
                    ->label(__('site.colors'))
                    ->counts('colors')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('banners_count')
                    ->label(__('site.banners'))
                    ->counts('banners')
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
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('priority', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            BannersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'view' => Pages\ViewBrand::route('/{record}'),
            'edit' => Pages\EditBrand::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
