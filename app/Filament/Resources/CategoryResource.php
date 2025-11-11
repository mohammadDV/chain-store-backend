<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use Domain\Product\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
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

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?int $navigationSort = 11;

    public static function getNavigationLabel(): string
    {
        return __('site.categories');
    }

    public static function getModelLabel(): string
    {
        return __('site.category');
    }

    public static function getPluralModelLabel(): string
    {
        return __('site.categories');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('site.category_information'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('title')
                                    ->label(__('site.title'))
                                    ->required()
                                    ->maxLength(255),
                                Select::make('status')
                                    ->label(__('site.status'))
                                    ->options([
                                        1 => __('site.Active'),
                                        0 => __('site.Inactive'),
                                    ])
                                    ->default(0)
                                    ->required(),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Select::make('brand_id')
                                    ->label(__('site.brand'))
                                    ->relationship('brand', 'title')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('parent_id')
                                    ->label(__('site.parent_category'))
                                    ->relationship('parent', 'title')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder(__('site.select_parent_category'))
                                    ->nullable()
                                    ->default(null)
                                    ->helperText(__('site.select_parent_category')),
                            ]),
                        TextInput::make('priority')
                            ->label(__('site.priority'))
                            ->numeric()
                            ->default(0),
                        RichEditor::make('description')
                            ->label(__('site.description'))
                            ->nullable()
                            ->columnSpanFull()
                            ->fileAttachmentsDisk('s3')
                            ->fileAttachmentsDirectory('categories/descriptions')
                            ->fileAttachmentsVisibility('public'),
                    ])->columns(1),
                Section::make(__('site.media'))
                    ->schema([
                        FileUpload::make('image')
                            ->label(__('site.category_image'))
                            ->placeholder(__('site.upload_category_image'))
                            ->image()
                            ->imageEditor()
                            ->disk('s3')
                            ->directory('categories/images')
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
                ImageColumn::make('image')
                    ->label(__('site.category_image'))
                    ->disk('s3')
                    ->visibility('public')
                    ->extraImgAttributes(['loading' => 'lazy'])
                    ->circular()
                    ->size(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title')
                    ->label(__('site.title'))
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('brand.title')
                    ->label(__('site.brand'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('parent.title')
                    ->label(__('site.parent_category'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(50),
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
                TextColumn::make('description')
                    ->label(__('site.description'))
                    ->html()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('products_count')
                    ->label(__('site.products'))
                    ->counts('products')
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
                SelectFilter::make('brand_id')
                    ->label(__('site.brand'))
                    ->relationship('brand', 'title'),
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'view' => Pages\ViewCategory::route('/{record}'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
