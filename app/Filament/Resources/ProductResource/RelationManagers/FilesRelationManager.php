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
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class FilesRelationManager extends RelationManager
{
    protected static string $relationship = 'files';

    protected static ?string $recordTitleAttribute = 'id';

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
                Select::make('type')
                    ->label(__('site.type'))
                    ->options([
                        'image' => __('site.image'),
                        'video' => __('site.video'),
                        'document' => __('site.document'),
                    ])
                    ->default('image')
                    ->required()
                    ->reactive(),
                FileUpload::make('path')
                    ->label(__('site.file'))
                    ->disk('s3')
                    ->directory('products/files')
                    ->visibility('public')
                    ->image(fn (callable $get) => $get('type') === 'image')
                    ->imageEditor(fn (callable $get) => $get('type') === 'image')
                    ->acceptedFileTypes(fn (callable $get) => match ($get('type')) {
                        'image' => ['image/*'],
                        'video' => ['video/*'],
                        'document' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                        default => null,
                    })
                    ->preserveFilenames()
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
                ImageColumn::make('path')
                    ->label(__('site.file'))
                    ->disk('s3')
                    ->visibility('public')
                    ->extraImgAttributes(['loading' => 'lazy'])
                    ->size(60)
                    ->circular(false)
                    ,
                ViewColumn::make('path')
                    ->label(__('site.file_path'))
                    ->view('filament.components.image-with-popup')
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('site.type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'image' => 'success',
                        'video' => 'warning',
                        'document' => 'info',
                        default => 'gray',
                    }),
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
            ]);
    }
}
