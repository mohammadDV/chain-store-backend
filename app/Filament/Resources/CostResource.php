<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CostResource\Pages;
use Domain\Cost\Models\Cost;
use Domain\Cost\Models\CostCategory;
use Domain\User\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CostResource extends Resource
{
    protected static ?string $model = Cost::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Cost';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('site.costs');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('site.Cost Management');
    }

    public static function getModelLabel(): string
    {
        return __('site.cost');
    }

    public static function getPluralModelLabel(): string
    {
        return __('site.costs');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('site.cost_information'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('amount')
                                    ->label(__('site.amount'))
                                    ->numeric()
                                    ->required()
                                    ->prefix('$'),
                                Select::make('status')
                                    ->label(__('site.status'))
                                    ->options([
                                        Cost::PENDING => __('site.pending'),
                                        Cost::PAID => __('site.paid'),
                                    ])
                                    ->required()
                                    ->native(false),
                                Select::make('user_id')
                                    ->label(__('site.user'))
                                    ->relationship('user', 'nickname', fn ($query) => $query->whereNotNull('nickname'))
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('category_id')
                                    ->label(__('site.category'))
                                    ->relationship('category', 'title')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),
                        Textarea::make('description')
                            ->label(__('site.description'))
                            ->rows(3)
                            ->columnSpanFull(),
                        FileUpload::make('image')
                            ->label(__('site.image'))
                            ->image()
                            ->imageEditor()
                            ->disk('s3')
                            ->directory('costs/images')
                            ->visibility('public')
                            ->maxSize(2048)
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
                TextColumn::make('user.nickname')
                    ->label(__('site.user'))
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => route('filament.admin.resources.users.view', ['record' => $record->user_id]))
                    ->openUrlInNewTab(),
                TextColumn::make('category.title')
                    ->label(__('site.category'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label(__('site.amount'))
                    ->money('IRR')
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('site.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Cost::PAID => 'success',
                        Cost::PENDING => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Cost::PENDING => __('site.pending'),
                        Cost::PAID => __('site.paid'),
                        default => $state,
                    }),
                ImageColumn::make('image')
                    ->label(__('site.image'))
                    ->disk('s3')
                    ->visibility('public')
                    ->extraImgAttributes(['loading' => 'lazy'])
                    ->circular()
                    ->size(40)
                    ->toggleable(isToggledHiddenByDefault: true),
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
                        Cost::PENDING => __('site.pending'),
                        Cost::PAID => __('site.paid'),
                    ]),
                SelectFilter::make('user_id')
                    ->label(__('site.user'))
                    ->relationship('user', 'nickname', fn ($query) => $query->whereNotNull('nickname'))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('category_id')
                    ->label(__('site.category'))
                    ->relationship('category', 'title')
                    ->searchable()
                    ->preload(),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label(__('site.created_from')),
                        DatePicker::make('created_until')
                            ->label(__('site.created_until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('total_amount')
                    ->label(fn ($livewire) => __('site.total_amount') . ': ' . number_format($livewire->getFilteredTableQuery()->sum('amount'), 0) . ' ' . __('site.currency'))
                    ->icon('heroicon-o-calculator')
                    ->color('success')
                    ->disabled()
                    ->extraAttributes(['class' => 'cursor-default']),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCosts::route('/'),
            'create' => Pages\CreateCost::route('/create'),
            'view' => Pages\ViewCost::route('/{record}'),
            'edit' => Pages\EditCost::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}