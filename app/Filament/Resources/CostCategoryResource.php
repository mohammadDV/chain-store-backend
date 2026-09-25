<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourceAuthorization;
use App\Filament\Resources\CostCategoryResource\Pages\CreateCostCategory;
use App\Filament\Resources\CostCategoryResource\Pages\EditCostCategory;
use App\Filament\Resources\CostCategoryResource\Pages\ListCostCategories;
use App\Filament\Resources\CostCategoryResource\Pages\ViewCostCategory;
use Domain\Cost\Models\CostCategory;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CostCategoryResource extends Resource
{
    use ChecksResourceAuthorization;

    protected static ?string $model = CostCategory::class;

    protected static function permissionPrefix(): string
    {
        return 'cost_categories';
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static string|\UnitEnum|null $navigationGroup = 'Cost';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('site.cost_categories');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('site.Cost Management');
    }

    public static function getModelLabel(): string
    {
        return __('site.cost_category');
    }

    public static function getPluralModelLabel(): string
    {
        return __('site.cost_categories');
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
                                Toggle::make('status')
                                    ->label(__('site.status'))
                                    ->default(0),
                            ]),
                        Select::make('parent_id')
                            ->label(__('site.parent_category'))
                            ->relationship('parent', 'title')
                            ->searchable()
                            ->preload()
                            ->placeholder(__('site.select_parent_category'))
                            ->nullable()
                            ->default(0)
                            ->helperText(__('site.select_parent_category')),
                        Textarea::make('description')
                            ->label(__('site.description'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
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
                TextColumn::make('title')
                    ->label(__('site.title'))
                    ->searchable()
                    ->sortable()
                    ->limit(50),
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
                TextColumn::make('description')
                    ->label(__('site.description'))
                    ->limit(50)
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
                SelectFilter::make('parent_id')
                    ->label(__('site.parent_category'))
                    ->relationship('parent', 'title')
                    ->searchable()
                    ->preload(),
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
            'index' => ListCostCategories::route('/'),
            'create' => CreateCostCategory::route('/create'),
            'view' => ViewCostCategory::route('/{record}'),
            'edit' => EditCostCategory::route('/{record}/edit'),
        ];
    }
}
