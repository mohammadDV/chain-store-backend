<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class FilesRelationManager extends RelationManager
{
    protected static string $relationship = 'files';

    protected static ?string $recordTitleAttribute = 'path';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('site.files');
    }

    public static function getModelLabel(): ?string
    {
        return __('site.file');
    }

    public static function getPluralModelLabel(): ?string
    {
        return __('site.files');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('path')
                    ->label(__('site.file'))
                    ->disk('s3')
                    ->directory('products/files')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->required(),
                Select::make('type')
                    ->label(__('site.type'))
                    ->options([
                        'image' => __('site.image'),
                        'video' => __('site.video'),
                        'document' => __('site.document'),
                    ])
                    ->default('image')
                    ->required(),
                Toggle::make('status')
                    ->label(__('site.status'))
                    ->default(true),
                TextInput::make('priority')
                    ->label(__('site.priority'))
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('path')
                    ->label(__('site.file'))
                    ->searchable()
                    ->limit(30),
                TextColumn::make('type')
                    ->label(__('site.type'))
                    ->badge(),
                IconColumn::make('status')
                    ->label(__('site.status'))
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('priority')
                    ->label(__('site.priority'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('site.created_at'))
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
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
            ]);
    }
}

