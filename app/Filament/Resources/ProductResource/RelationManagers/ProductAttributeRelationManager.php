<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProductAttributeRelationManager extends RelationManager
{
    protected static string $relationship = 'attributes';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('site.product_attributes');
    }

    public static function getModelLabel(): ?string
    {
        return __('site.product_attribute');
    }

    public static function getPluralModelLabel(): ?string
    {
        return __('site.product_attributes');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->label(__('site.title'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('value')
                    ->label(__('site.value'))
                    ->required()
                    ->maxLength(255),
                // TextInput::make('priority')
                //     ->label(__('site.priority'))
                //     ->numeric()
                //     ->default(0),
                // Toggle::make('status')
                //     ->label(__('site.status'))
                //     ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('site.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('value')
                    ->label(__('site.value'))
                    ->searchable()
                    ->sortable(),
                // TextColumn::make('priority')
                //     ->label(__('site.priority'))
                //     ->sortable(),
                // IconColumn::make('status')
                //     ->label(__('site.status'))
                //     ->boolean()
                //     ->trueColor('success')
                //     ->falseColor('danger'),
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
            ->defaultSort('id', 'desc');
    }
}