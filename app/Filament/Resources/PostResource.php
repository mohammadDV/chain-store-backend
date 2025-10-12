<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use Domain\Post\Models\Post;
use Domain\User\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Morilog\Jalali\Jalalian;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 13;

    public static function getNavigationLabel(): string
    {
        return __('site.posts');
    }

    public static function getModelLabel(): string
    {
        return __('site.post');
    }

    public static function getPluralModelLabel(): string
    {
        return __('site.posts');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('site.post_information'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('title')
                                    ->label(__('site.title'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('pre_title')
                                    ->label(__('site.pre_title'))
                                    ->maxLength(255),
                            ]),
                        Textarea::make('summary')
                            ->label(__('site.summary'))
                            ->required()
                            ->rows(3)
                            ->maxLength(255),
                        RichEditor::make('content')
                            ->label(__('site.content'))
                            ->required()
                            ->fileAttachmentsDisk('s3')
                            ->fileAttachmentsDirectory('posts/content')
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
                        Grid::make(2)
                            ->schema([
                                Select::make('type')
                                    ->label(__('site.type'))
                                    ->options([
                                        0 => __('site.normal'),
                                        1 => __('site.video'),
                                    ])
                                    ->default(0)
                                    ->reactive()
                                    ->required(),
                                FileUpload::make('video')
                                    ->label(__('site.video'))
                                    ->disk('s3')
                                    ->directory('posts/videos')
                                    ->visibility('public')
                                    ->acceptedFileTypes(['video/mp4', 'video/avi', 'video/mov', 'video/quicktime', 'video/wmv', 'video/flv', 'video/x-msvideo'])
                                    ->maxSize(150 * 1024) // 100MB
                                    ->visible(fn (callable $get) => $get('type') == 1)
                                    ->required(fn (callable $get) => $get('type') == 1),
                            ]),
                    ]),

                Section::make(__('site.media'))
                    ->schema([
                        FileUpload::make('image')
                            ->label(__('site.post_image'))
                            ->placeholder(__('site.upload_post_image'))
                            ->image()
                            ->imageEditor()
                            ->disk('s3')
                            ->directory('posts/images')
                            ->visibility('public')
                            ->required()
                    ])->columns(2),

                Section::make(__('site.settings'))
                    ->schema([
                        Select::make('status')
                            ->label(__('site.status'))
                            ->options([
                                0 => __('site.Inactive'),
                                1 => __('site.Active'),
                            ])
                            ->default(0)
                            ->required(),
                        Toggle::make('special')
                            ->label(__('site.special'))
                            ->default(false)
                            ->helperText(__('site.special_post_help')),
                        Hidden::make('view')
                            ->default(0),
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
                ImageColumn::make('image')
                    ->label(__('site.image'))
                    ->disk('s3')
                    ->visibility('public')
                    ->extraImgAttributes(['loading' => 'lazy'])
                    ->url(fn ($record) => $record->image ? $record->image : null)
                    ->circular()
                    ->size(40),
                TextColumn::make('title')
                    ->label(__('site.title'))
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('pre_title')
                    ->label(__('site.pre_title'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(30),
                TextColumn::make('type')
                    ->label(__('site.type'))
                    ->badge()
                    ->color(fn (int $state): string => match ($state) {
                        0 => 'primary',
                        1 => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        0 => __('site.normal'),
                        1 => __('site.video'),
                        default => $state,
                    }),
                TextColumn::make('status')
                    ->label(__('site.status'))
                    ->badge()
                    ->color(fn (int $state): string => match ($state) {
                        0 => 'danger',
                        1 => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        0 => __('site.Inactive'),
                        1 => __('site.Active'),
                        default => $state,
                    }),
                TextColumn::make('view')
                    ->label(__('site.views'))
                    ->sortable()
                    ->badge()
                    ->color('info'),
                IconColumn::make('special')
                    ->label(__('site.special'))
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                TextColumn::make('user.nickname')
                    ->label(__('site.author'))
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->user ? route('filament.admin.resources.users.edit', $record->user) : null)
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->icon('heroicon-o-user')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(__('site.click_to_view_user_details')),
                TextColumn::make('created_at')
                    ->label(__('site.created_at'))
                    ->dateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => $state ? Jalalian::fromDateTime($state)->format('Y/m/d H:i') : null),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('site.type'))
                    ->options([
                        0 => __('site.normal'),
                        1 => __('site.video'),
                    ]),
                SelectFilter::make('status')
                    ->label(__('site.status'))
                    ->options([
                        0 => __('site.Inactive'),
                        1 => __('site.Active'),
                    ]),
                TernaryFilter::make('special')
                    ->label(__('site.special')),
                SelectFilter::make('user_id')
                    ->label(__('site.author'))
                    ->options(User::all()->mapWithKeys(function ($user) {
                        return [$user->id => $user->getFilamentName()];
                    }))
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'view' => Pages\ViewPost::route('/{record}'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
