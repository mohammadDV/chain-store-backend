<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\FilesRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\ProductAttributeRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\SizesRelationManager;
use Domain\Brand\Models\Brand;
use Domain\Product\Models\Category as ProductCategory;
use Domain\Product\Models\Color;
use Domain\Product\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\View;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 11;

    public static function getNavigationLabel(): string
    {
        return __('site.products');
    }

    public static function getModelLabel(): string
    {
        return __('site.product');
    }

    public static function getPluralModelLabel(): string
    {
        return __('site.products');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('site.product_information'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('title')
                                    ->label(__('site.title'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('code')
                                    ->label(__('site.code'))
                                    ->maxLength(255),
                            ]),
                        RichEditor::make('description')
                            ->label(__('site.description'))
                            ->fileAttachmentsDisk('s3')
                            ->fileAttachmentsDirectory('products/description')
                            ->fileAttachmentsVisibility('public')
                            ->toolbarButtons([
                                'attachFiles',
                                'blockquote',
                                'bold',
                                'bulletList',
                                'codeBlock',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'undo',
                            ])
                            ->columnSpanFull(),
                        RichEditor::make('details')
                            ->label(__('site.details'))
                            ->fileAttachmentsDisk('s3')
                            ->fileAttachmentsDirectory('products/details')
                            ->fileAttachmentsVisibility('public')
                            ->toolbarButtons([
                                'attachFiles',
                                'blockquote',
                                'bold',
                                'bulletList',
                                'codeBlock',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'undo',
                            ])
                            ->columnSpanFull(),
                        TextInput::make('url')
                            ->label(__('site.url'))
                            ->columnSpanFull()
                            ->maxLength(2048)
                            ->url(),
                    ]),
                Section::make(__('site.product_relations'))
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('brand_id')
                                ->label(__('site.brand'))
                                ->relationship('brand', 'title')
                                ->searchable()
                                ->required(),
                            Select::make('categories')
                                ->label(__('site.category'))
                                ->relationship('categories', 'title')
                                ->multiple()
                                ->searchable()
                                ->required(),
                            Select::make('color_id')
                                ->label(__('site.color'))
                                ->relationship('color', 'title')
                                ->required(),
                            Hidden::make('user_id')
                                ->default(fn (): ?int => Auth::id())
                                ->required(),
                        ]),
                ]),
                Section::make(__('site.pricing_inventory'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('amount')
                                    ->label(__('site.amount'))
                                    // ->numeric()
                                    ->required()
                                    ->minValue(0),
                                TextInput::make('discount')
                                    ->label(__('site.discount'))
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(99),
                                TextInput::make('stock')
                                    ->label(__('site.stock'))
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                                TextInput::make('points')
                                    ->label(__('site.points'))
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                                TextInput::make('rate')
                                    ->label(__('site.rate'))
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                                TextInput::make('priority')
                                    ->label(__('site.priority'))
                                    ->numeric()
                                    ->default(0),
                            ]),
                        Grid::make(3)
                            ->schema([
                                Toggle::make('vip')
                                    ->label(__('site.vip'))
                                    ->default(false),
                                Toggle::make('active')
                                    ->label(__('site.active'))
                                    ->default(false),
                                Select::make('status')
                                    ->label(__('site.status'))
                                    ->options([
                                        Product::PENDING => __('site.pending'),
                                        Product::COMPLETED => __('site.completed'),
                                        Product::REJECT => __('site.reject'),
                                    ])
                                    ->default(Product::PENDING)
                                    ->required(),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('order_count')
                                    ->label(__('site.order_count'))
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('view_count')
                                    ->label(__('site.view_count'))
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ]),
                Section::make(__('site.media'))
                    ->schema([
                        Hidden::make('image')
                            ->dehydrated(),
                        Select::make('image_source')
                            ->label(__('site.image_source'))
                            ->options([
                                'upload' => __('site.upload_file'),
                                'url' => __('site.enter_url'),
                            ])
                            ->default('upload')
                            ->live()
                            ->required()
                            ->dehydrated(false),
                        FileUpload::make('image_upload')
                            ->label(__('site.product_image'))
                            ->placeholder(__('site.upload_product_image'))
                            ->image()
                            ->imageEditor()
                            ->disk('s3')
                            ->directory('products/images')
                            ->visibility('public')
                            ->visible(fn (callable $get) => ($get('image_source') ?? 'upload') === 'upload')
                            ->required(fn (callable $get) => ($get('image_source') ?? 'upload') === 'upload')
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $imagePath = is_array($state) ? reset($state) : $state;
                                    $set('image', $imagePath);
                                }
                            })
                            ->dehydrated(false),
                        TextInput::make('image_url')
                            ->label(__('site.image_url'))
                            ->placeholder('https://example.com/image.jpg')
                            ->url()
                            ->maxLength(2048)
                            ->live()
                            ->visible(fn (callable $get) => ($get('image_source') ?? 'upload') === 'url')
                            ->required(fn (callable $get) => ($get('image_source') ?? 'upload') === 'url')
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $set('image', $state);
                                }
                            })
                            ->dehydrated(false),
                        View::make('filament.components.image-url-preview')
                            ->visible(fn (callable $get) => ($get('image_source') ?? 'upload') === 'url')
                            ->dehydrated(false),
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
                ViewColumn::make('image')
                    ->label(__('site.image'))
                    ->view('filament.components.image-with-popup')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title')
                    ->label(__('site.title'))
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                TextColumn::make('brand.title')
                    ->label(__('site.brand'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('categories.title')
                    ->label(__('site.category'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->badge()
                    ->separator(',')
                    ->searchable(),
                TextColumn::make('color.title')
                    ->label(__('site.color'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.nickname')
                    ->label(__('site.user'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('amount')
                    ->label(__('site.amount'))
                    // ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => number_format($record->getRawOriginal('amount') ?? 0)),
                TextColumn::make('discount')
                    ->label(__('site.discount'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('stock')
                    ->label(__('site.stock'))
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('order_count')
                    ->label(__('site.order_count'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('view_count')
                    ->label(__('site.view_count'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label(__('site.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Product::COMPLETED => 'success',
                        Product::PENDING => 'warning',
                        Product::REJECT => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Product::COMPLETED => __('site.completed'),
                        Product::PENDING => __('site.pending'),
                        Product::REJECT => __('site.reject'),
                        default => $state,
                    }),
                IconColumn::make('vip')
                    ->label(__('site.vip'))
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                IconColumn::make('active')
                    ->label(__('site.active'))
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray'),
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
                SelectFilter::make('brand_id')
                    ->label(__('site.brand'))
                    ->options(fn () => Brand::query()->pluck('title', 'id')->all())
                    ->searchable(),
                SelectFilter::make('categories')
                    ->label(__('site.category'))
                    ->relationship('categories', 'title')
                    ->searchable(),
                SelectFilter::make('color_id')
                    ->label(__('site.color'))
                    ->options(fn () => Color::query()->pluck('title', 'id')->all())
                    ->searchable(),
                SelectFilter::make('status')
                    ->label(__('site.status'))
                    ->options([
                        Product::PENDING => __('site.pending'),
                        Product::COMPLETED => __('site.completed'),
                        Product::REJECT => __('site.reject'),
                    ]),
                TernaryFilter::make('vip')
                    ->label(__('site.vip')),
                TernaryFilter::make('active')
                    ->label(__('site.active')),
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
            FilesRelationManager::class,
            SizesRelationManager::class,
            ProductAttributeRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
