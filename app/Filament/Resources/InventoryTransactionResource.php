<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\ViewAction;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Select;
use App\Filament\Resources\InventoryTransactionResource\Pages\ListInventoryTransactions;
use App\Filament\Resources\InventoryTransactionResource\Pages\CreateInventoryTransaction;
use App\Filament\Resources\InventoryTransactionResource\Pages\ViewInventoryTransaction;
use App\Filament\Resources\InventoryTransactionResource\Pages;
use Domain\Product\Enums\InventoryTransactionSource;
use Domain\Product\Enums\InventoryTransactionType;
use Domain\Product\Models\InventoryTransaction;
use Domain\Product\Models\Product;
use Domain\Product\Models\Size;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Morilog\Jalali\Jalalian;

class InventoryTransactionResource extends Resource
{
    protected static ?string $model = InventoryTransaction::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 12;

    public static function getNavigationGroup(): ?string
    {
        return __('site.product_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('site.inventory_transactions');
    }

    public static function getModelLabel(): string
    {
        return __('site.inventory_transaction');
    }

    public static function getPluralModelLabel(): string
    {
        return __('site.inventory_transactions');
    }

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            InventoryTransactionType::Adjust->value => __('site.inventory_type_adjust'),
            InventoryTransactionType::Sale->value => __('site.inventory_type_sale'),
            InventoryTransactionType::Return->value => __('site.inventory_type_return'),
            InventoryTransactionType::Purchase->value => __('site.inventory_type_purchase'),
            InventoryTransactionType::Reserve->value => __('site.inventory_type_reserve'),
            InventoryTransactionType::Release->value => __('site.inventory_type_release'),
        ];
    }

    /**
     * Fixed stock direction for a type, or null when admin may choose either way.
     */
    public static function forcedDirectionForType(?string $type): ?string
    {
        return match ($type) {
            InventoryTransactionType::Purchase->value,
            InventoryTransactionType::Return->value,
            InventoryTransactionType::Release->value => 'increase',
            InventoryTransactionType::Sale->value,
            InventoryTransactionType::Reserve->value => 'decrease',
            default => null,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function sourceOptions(): array
    {
        return [
            InventoryTransactionSource::System => __('site.inventory_source_system'),
            InventoryTransactionSource::Admin => __('site.inventory_source_admin'),
            InventoryTransactionSource::Scraper => __('site.inventory_source_scraper'),
            InventoryTransactionSource::Order => __('site.inventory_source_order'),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('site.inventory_transaction_information'))
                    ->schema([
                        TextInput::make('product_id')
                            ->label(__('site.product_id'))
                            ->disabled(),
                        TextInput::make('size_id')
                            ->label(__('site.size_id'))
                            ->disabled(),
                        TextInput::make('type')
                            ->label(__('site.type'))
                            ->disabled(),
                        TextInput::make('source')
                            ->label(__('site.source'))
                            ->disabled(),
                        TextInput::make('quantity_change')
                            ->label(__('site.quantity_change'))
                            ->disabled(),
                        TextInput::make('previous_quantity')
                            ->label(__('site.previous_quantity'))
                            ->disabled(),
                        TextInput::make('resulting_quantity')
                            ->label(__('site.resulting_quantity'))
                            ->disabled(),
                        Textarea::make('description')
                            ->label(__('site.description'))
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'product:id,title,code',
                'size' => fn ($q) => $q->without(['stock'])->select('id', 'title', 'code', 'product_id'),
                'user:id,nickname',
            ]))
            ->columns([
                TextColumn::make('id')
                    ->label(__('site.id'))
                    ->sortable(),
                TextColumn::make('product.code')
                    ->label(__('site.product_code'))
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('product.title')
                    ->label(__('site.product'))
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->product?->title)
                    ->url(fn ($record) => $record->product_id
                        ? ProductResource::getUrl('edit', ['record' => $record->product_id])
                        : null)
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('size.title')
                    ->label(__('site.size'))
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('type')
                    ->label(__('site.type'))
                    ->badge()
                    ->formatStateUsing(fn (InventoryTransactionType|string $state): string => static::typeOptions()[
                        $state instanceof InventoryTransactionType ? $state->value : $state
                    ] ?? (string) ($state instanceof InventoryTransactionType ? $state->value : $state))
                    ->color(fn (InventoryTransactionType|string $state): string => match (
                        $state instanceof InventoryTransactionType ? $state->value : $state
                    ) {
                        'sale' => 'danger',
                        'return', 'purchase', 'release' => 'success',
                        'reserve' => 'warning',
                        'adjust' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('source')
                    ->label(__('site.source'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => static::sourceOptions()[$state] ?? $state)
                    ->toggleable(),
                TextColumn::make('quantity_change')
                    ->label(__('site.quantity_change'))
                    ->sortable()
                    ->color(fn ($record) => $record->quantity_change >= 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => ($state > 0 ? '+' : '').$state),
                TextColumn::make('previous_quantity')
                    ->label(__('site.previous_quantity'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('resulting_quantity')
                    ->label(__('site.resulting_quantity'))
                    ->sortable(),
                TextColumn::make('user.nickname')
                    ->label(__('site.user'))
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('site.created_at'))
                    ->sortable()
                    ->formatStateUsing(fn ($state) => Jalalian::fromDateTime($state)->format('Y/m/d H:i')),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('site.type'))
                    ->options(static::typeOptions()),
                SelectFilter::make('source')
                    ->label(__('site.source'))
                    ->options(static::sourceOptions()),
                Filter::make('product_id')
                    ->label(__('site.product_id'))
                    ->schema([
                        TextInput::make('value')
                            ->label(__('site.product_id'))
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return filled($value)
                            ? $query->where('product_id', (int) $value)
                            : $query;
                    }),
                Filter::make('product_code')
                    ->label(__('site.product_code'))
                    ->schema([
                        TextInput::make('value')
                            ->label(__('site.product_code')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $code = trim((string) ($data['value'] ?? ''));
                        if ($code === '') {
                            return $query;
                        }

                        $productId = Product::query()
                            ->where('code', $code)
                            ->value('id');

                        return $productId
                            ? $query->where('product_id', $productId)
                            : $query->whereRaw('0 = 1');
                    }),
                Filter::make('size_id')
                    ->label(__('site.size_id'))
                    ->schema([
                        TextInput::make('value')
                            ->label(__('site.size_id'))
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return filled($value)
                            ? $query->where('size_id', (int) $value)
                            : $query;
                    }),
                SelectFilter::make('user_id')
                    ->label(__('site.user'))
                    ->relationship('user', 'nickname', fn ($query) => $query->whereNotNull('nickname'))
                    ->searchable()
                    ->preload(false),
                Filter::make('created_at')
                    ->label(__('site.created_at'))
                    ->schema([
                        DatePicker::make('from')
                            ->label(__('site.from_date')),
                        DatePicker::make('until')
                            ->label(__('site.to_date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date)
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([])
            ->defaultSort('id', 'desc')
            ->deferLoading()
            ->paginated([25, 50, 100]);
    }

    /**
     * Schema used by the create / adjust page (not persisted directly).
     *
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    public static function adjustFormSchema(): array
    {
        return [
            Section::make(__('site.adjust_inventory'))
                ->description(__('site.adjust_inventory_description'))
                ->schema([
                    TextInput::make('product_ref')
                        ->label(__('site.product_ref'))
                        ->helperText(__('site.product_ref_help'))
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            $set('product_id', null);
                            $set('size_id', null);
                            $set('product_label', null);

                            $ref = trim((string) $state);
                            if ($ref === '') {
                                return;
                            }

                            $product = Product::query()
                                ->select('id', 'title', 'code')
                                ->when(
                                    ctype_digit($ref),
                                    fn (Builder $q) => $q->where('id', (int) $ref),
                                    fn (Builder $q) => $q->where('code', $ref),
                                )
                                ->first();

                            if (! $product) {
                                return;
                            }

                            $set('product_id', $product->id);
                            $set('product_label', "#{$product->id} — {$product->code} — {$product->title}");
                        }),
                    Hidden::make('product_id')
                        ->required(),
                    Placeholder::make('product_label')
                        ->label(__('site.product'))
                        ->content(fn (Get $get): string => $get('product_label') ?: __('site.product_not_found'))
                        ->visible(fn (Get $get): bool => filled($get('product_ref'))),
                    Select::make('size_id')
                        ->label(__('site.size'))
                        ->required()
                        ->searchable()
                        ->options(function (Get $get): array {
                            $productId = $get('product_id');
                            if (! $productId) {
                                return [];
                            }

                            return Size::query()
                                ->without(['stock'])
                                ->where('product_id', $productId)
                                ->orderByDesc('priority')
                                ->get(['id', 'title', 'code'])
                                ->mapWithKeys(function (Size $size) {
                                    $label = trim((string) ($size->title ?? ''));
                                    if ($size->code) {
                                        $label = trim($label.' ('.$size->code.')');
                                    }

                                    return [$size->id => $label !== '' ? $label : '#'.$size->id];
                                })
                                ->all();
                        })
                        ->disabled(fn (Get $get): bool => blank($get('product_id'))),
                    Select::make('type')
                        ->label(__('site.type'))
                        ->options(static::typeOptions())
                        ->default(InventoryTransactionType::Adjust->value)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            $forced = static::forcedDirectionForType($state);
                            if ($forced !== null) {
                                $set('direction', $forced);
                            }
                        })
                        ->helperText(__('site.inventory_manual_type_help')),
                    Select::make('direction')
                        ->label(__('site.adjustment_type'))
                        ->options([
                            'increase' => __('site.increase'),
                            'decrease' => __('site.decrease'),
                        ])
                        ->default('increase')
                        ->required()
                        ->live()
                        ->disabled(fn (Get $get): bool => static::forcedDirectionForType($get('type')) !== null)
                        ->dehydrated()
                        ->helperText(fn (Get $get): ?string => static::forcedDirectionForType($get('type')) !== null
                            ? __('site.inventory_direction_locked_help')
                            : null),
                    TextInput::make('quantity')
                        ->label(__('site.quantity'))
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->integer(),
                    Textarea::make('description')
                        ->label(__('site.description'))
                        ->rows(3)
                        ->placeholder(__('site.inventory_adjustment_description_placeholder'))
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventoryTransactions::route('/'),
            'create' => CreateInventoryTransaction::route('/create'),
            'view' => ViewInventoryTransaction::route('/{record}'),
        ];
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
