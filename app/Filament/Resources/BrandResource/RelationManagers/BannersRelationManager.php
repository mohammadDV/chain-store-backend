<?php

namespace App\Filament\Resources\BrandResource\RelationManagers;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BannersRelationManager extends RelationManager
{
    protected static string $relationship = 'banners';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('site.banners');
    }

    public static function getModelLabel(): ?string
    {
        return __('site.banner');
    }

    public static function getPluralModelLabel(): ?string
    {
        return __('site.banners');
    }

    public function form(Form $form): Form
    {
        return $form
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
                FileUpload::make('image')
                    ->label(__('site.banner_image'))
                    ->placeholder(__('site.upload_banner_image'))
                    ->image()
                    ->imageEditor()
                    ->disk('s3')
                    ->directory('brands/banners')
                    ->visibility('public')
                    ->maxSize(2048)
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
                            ->default(1),
                    ]),
            ]);
    }

    public function table(Table $table): Table
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
                    ->size(60),
                TextColumn::make('title')
                    ->label(__('site.title'))
                    ->searchable()
                    ->sortable(),
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
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('priority', 'desc');
    }
}


