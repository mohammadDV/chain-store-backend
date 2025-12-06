<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BannerResource\Pages;
use Domain\Brand\Models\Banner;
use Domain\Brand\Models\Brand;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?int $navigationSort = 13;

    public static function getNavigationLabel(): string
    {
        return __('site.banners');
    }

    public static function getModelLabel(): string
    {
        return __('site.banner');
    }

    public static function getPluralModelLabel(): string
    {
        return __('site.banners');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('site.banner_information'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('title')
                                    ->label(__('site.title'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('link')
                                    ->label(__('site.link'))
                                    ->url()
                                    ->maxLength(2048),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Select::make('brand_id')
                                    ->label(__('site.brand'))
                                    ->relationship('brand', 'title')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->helperText(__('site.select_brand_optional')),
                                Select::make('status')
                                    ->label(__('site.status'))
                                    ->options([
                                        1 => __('site.Active'),
                                        0 => __('site.Inactive'),
                                    ])
                                    ->default(1)
                                    ->required(),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('priority')
                                    ->label(__('site.priority'))
                                    ->numeric()
                                    ->default(1)
                                    ->required(),
                            ]),
                    ]),
                Section::make(__('site.media'))
                    ->schema([
                        FileUpload::make('image')
                            ->label(__('site.banner_image'))
                            ->placeholder(__('site.upload_banner_image'))
                            ->image()
                            ->imageEditor()
                            ->disk('s3')
                            ->directory('banners/images')
                            ->visibility('public')
                            ->maxSize(40 * 1024)
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
                ImageColumn::make('image')
                    ->label(__('site.banner_image'))
                    ->disk('s3')
                    ->visibility('public')
                    ->extraImgAttributes(['loading' => 'lazy'])
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->size(80),
                TextColumn::make('title')
                    ->label(__('site.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('brand.title')
                    ->label(__('site.brand'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->placeholder(__('site.no_brand')),
                TextColumn::make('link')
                    ->label(__('site.link'))
                    ->url(fn ($record) => $record->link)
                    ->openUrlInNewTab()
                    ->limit(30)
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
                SelectFilter::make('brand_id')
                    ->label(__('site.brand'))
                    ->relationship('brand', 'title')
                    ->searchable()
                    ->preload(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBanners::route('/'),
            'create' => Pages\CreateBanner::route('/create'),
            'view' => Pages\ViewBanner::route('/{record}'),
            'edit' => Pages\EditBanner::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
