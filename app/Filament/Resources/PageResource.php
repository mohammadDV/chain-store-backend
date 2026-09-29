<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourceAuthorization;
use App\Filament\Resources\PageResource\Pages\CreatePage;
use App\Filament\Resources\PageResource\Pages\EditPage;
use App\Filament\Resources\PageResource\Pages\ListPages;
use Domain\Page\Models\Page;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PageResource extends Resource
{
    use ChecksResourceAuthorization;

    protected static ?string $model = Page::class;

    protected static function permissionPrefix(): string
    {
        return 'pages';
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?int $navigationSort = 14;

    public static function getNavigationLabel(): string
    {
        return __('site.pages');
    }

    public static function getModelLabel(): string
    {
        return __('site.page');
    }

    public static function getPluralModelLabel(): string
    {
        return __('site.pages');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('site.Content Management');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('site.page_information'))
                    ->schema([
                        TextInput::make('title')
                            ->label(__('site.title'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('slug')
                            ->label(__('site.slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText(__('site.page_slug_help'))
                            ->columnSpanFull(),
                        FileUpload::make('image')
                            ->label(__('site.image'))
                            ->image()
                            ->imageEditor()
                            ->disk('s3')
                            ->directory('pages/images')
                            ->visibility('public')
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label(__('site.active'))
                            ->default(true)
                            ->required()
                            ->columnSpanFull(),
                        Toggle::make('show_in_menu')
                            ->label(__('site.show_in_menu'))
                            ->helperText(__('site.show_in_menu_help'))
                            ->default(false)
                            ->columnSpanFull(),
                        TextInput::make('sort_order')
                            ->label(__('site.sort_order'))
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
                Section::make(__('site.seo'))
                    ->icon('heroicon-o-magnifying-glass')
                    ->schema([
                        TextInput::make('meta_title')
                            ->label(__('site.meta_title'))
                            ->maxLength(255)
                            ->helperText(__('site.meta_title_help'))
                            ->columnSpanFull(),
                        Textarea::make('meta_description')
                            ->label(__('site.meta_description'))
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText(__('site.meta_description_help'))
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->collapsible(),
                Section::make(__('site.content'))
                    ->schema([
                        RichEditor::make('content')
                            ->label(__('site.content'))
                            ->fileAttachmentsDisk('s3')
                            ->fileAttachmentsDirectory('pages/content')
                            ->fileAttachmentsVisibility('public')
                            ->toolbarButtons([
                                'attachFiles',
                                'blockquote',
                                'bold',
                                'bulletList',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'undo',
                            ])
                            ->extraInputAttributes(['style' => 'min-height: 28rem'])
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('id')
                    ->label(__('site.table_id'))
                    ->sortable(),
                TextColumn::make('title')
                    ->label(__('site.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('site.slug'))
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('site.active'))
                    ->boolean(),
                IconColumn::make('show_in_menu')
                    ->label(__('site.show_in_menu'))
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label(__('site.sort_order'))
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('site.updated_at'))
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('site.active')),
                TernaryFilter::make('show_in_menu')
                    ->label(__('site.show_in_menu')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
