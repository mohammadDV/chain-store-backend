<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourceAuthorization;
use App\Filament\Concerns\ScopesQueryByAdminBrands;
use App\Filament\Resources\CategoryResource\Pages\CreateCategory;
use App\Filament\Resources\CategoryResource\Pages\EditCategory;
use App\Filament\Resources\CategoryResource\Pages\ListCategories;
use App\Filament\Resources\CategoryResource\Pages\ViewCategory;
use Domain\Product\Models\Category;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    use ChecksResourceAuthorization;
    use ScopesQueryByAdminBrands;

    protected static ?string $model = Category::class;

    protected static function permissionPrefix(): string
    {
        return 'categories';
    }

    protected static function brandScopeStrategy(): string
    {
        return 'brands';
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?int $navigationSort = 11;

    public static function getNavigationGroup(): ?string
    {
        return __('site.product_management');
    }

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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                                Select::make('brands')
                                    ->label(__('site.brands'))
                                    ->relationship('brands', 'title')
                                    ->searchable()
                                    ->preload()
                                    ->multiple(),
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
                TagsColumn::make('brands.title')
                    ->label(__('site.brands'))
                    ->limit(3)
                    ->separator(',')
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
                SelectFilter::make('brands')
                    ->label(__('site.brands'))
                    ->relationship('brands', 'title')
                    ->multiple(),
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
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'view' => ViewCategory::route('/{record}'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }
}
