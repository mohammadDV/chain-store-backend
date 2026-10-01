<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourceAuthorization;
use App\Filament\Filters\UserIdFilter;
use App\Filament\Resources\ManualOrderResource\Pages\CreateManualOrder;
use App\Filament\Resources\ManualOrderResource\Pages\EditManualOrder;
use App\Filament\Resources\ManualOrderResource\Pages\ListManualOrders;
use App\Filament\Resources\ManualOrderResource\Pages\ViewManualOrder;
use Domain\AdminAccess\AdminPermission;
use Domain\Product\Models\ManualOrder;
use Domain\Product\Services\ManualOrderPricingService;
use Domain\Setting\Services\SettingService;
use Domain\User\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ManualOrderResource extends Resource
{
    use ChecksResourceAuthorization;

    protected static ?string $model = ManualOrder::class;

    protected static function permissionPrefix(): string
    {
        return 'manual_orders';
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 13;

    public static function getNavigationLabel(): string
    {
        return __('site.manual_orders');
    }

    public static function getModelLabel(): string
    {
        return __('site.manual_order');
    }

    public static function getPluralModelLabel(): string
    {
        return __('site.manual_orders');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('site.manual_order_pricing'))
                    ->description(__('site.manual_order_pricing_help'))
                    ->visibleOn('create')
                    ->columns(1)
                    ->schema([
                        TextInput::make('calc_raw_price')
                            ->label(__('site.manual_order_raw_price'))
                            ->helperText(__('site.manual_order_raw_price_help'))
                            ->numeric()
                            ->minValue(0)
                            ->live(debounce: 400)
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, Set $set): void {
                                if ($state === null || $state === '') {
                                    return;
                                }

                                // Same path as Product amount accessor → OrderRepository productsAmount.
                                $quote = app(ManualOrderPricingService::class)->quoteFromRawPrice((float) $state);
                                $set('amount', $quote['amount']);
                            }),
                        Placeholder::make('quote_exchange_rate')
                            ->label(__('site.exchange_rate'))
                            ->content(fn (): string => number_format(
                                app(SettingService::class)->getExchangeRateWithFallback(),
                                0,
                                '.',
                                ','
                            )),
                        Placeholder::make('quote_profit_rate')
                            ->label(__('site.profit_rate'))
                            ->content(fn (): string => number_format(
                                app(SettingService::class)->getProfitRateWithFallback(),
                                2
                            ).'%'),
                    ]),
                Section::make(__('site.manual_order_product_information'))
                    ->columns(1)
                    ->schema([
                        TextInput::make('product_name')
                            ->label(__('site.product_name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('brand')
                            ->label(__('site.brand'))
                            ->maxLength(255),
                        TextInput::make('amount')
                            ->label(__('site.amount'))
                            ->helperText(__('site.manual_order_amount_help'))
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->prefix(__('site.toman'))
                            ->live(debounce: 400),
                        Placeholder::make('display_delivery_amount')
                            ->label(__('site.delivery_amount'))
                            ->content(function (Get $get): HtmlString {
                                $amount = $get('amount');
                                if ($amount === null || $amount === '') {
                                    return static::highlightedMoneyHtml('—', '#c2410c');
                                }

                                $pricing = app(ManualOrderPricingService::class);
                                $totals = $pricing->buildOrderAmounts((float) $amount);

                                return static::highlightedMoneyHtml(
                                    $pricing->formatMoney($totals['delivery_amount']),
                                    '#c2410c'
                                );
                            })
                            ->helperText(function (): string {
                                $settings = app(SettingService::class);
                                $pricing = app(ManualOrderPricingService::class);

                                return __('site.manual_order_delivery_help', [
                                    'limit' => $pricing->formatMoney($settings->getLimitDeliveryAmountWithFallback()),
                                    'fee' => $pricing->formatMoney($settings->getDeliveryAmountWithFallback()),
                                ]);
                            }),
                        Placeholder::make('display_total_amount')
                            ->label(__('site.total_amount'))
                            ->content(function (Get $get): HtmlString {
                                $amount = $get('amount');
                                if ($amount === null || $amount === '') {
                                    return static::highlightedMoneyHtml('—', '#047857');
                                }

                                $pricing = app(ManualOrderPricingService::class);
                                $totals = $pricing->buildOrderAmounts((float) $amount);

                                return static::highlightedMoneyHtml(
                                    $pricing->formatMoney($totals['total_amount']),
                                    '#047857'
                                );
                            }),
                        Placeholder::make('display_profit')
                            ->label(__('site.profit'))
                            ->content(function (Get $get): string {
                                $amount = $get('amount');
                                if ($amount === null || $amount === '') {
                                    return '—';
                                }

                                $pricing = app(ManualOrderPricingService::class);
                                $totals = $pricing->buildOrderAmounts((float) $amount);

                                return $pricing->formatMoney($totals['profit']);
                            }),
                        TextInput::make('code')
                            ->label(__('site.order_code'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn(['edit', 'view']),
                        Select::make('user_id')
                            ->label(__('site.user'))
                            ->relationship('user', 'nickname', fn ($query) => $query->whereNotNull('nickname'))
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Textarea::make('description')
                            ->label(__('site.description'))
                            ->rows(4),
                        FileUpload::make('image')
                            ->label(__('site.product_image'))
                            ->image()
                            ->imageEditor()
                            ->disk('s3')
                            ->directory('manual-orders/images')
                            ->visibility('public'),
                    ]),
                Section::make(__('site.manual_order_customer_information'))
                    ->columns(1)
                    ->schema([
                        TextInput::make('fullname')
                            ->label(__('site.fullname'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('mobile')
                            ->label(__('site.mobile'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('site.email'))
                            ->email()
                            ->maxLength(255),
                        TextInput::make('postal_code')
                            ->label(__('site.postal_code'))
                            ->maxLength(255),
                        Textarea::make('address')
                            ->label(__('site.address'))
                            ->rows(3),
                    ]),
                Section::make(__('site.manual_order_receipts'))
                    ->columns(1)
                    ->schema([
                        FileUpload::make('payment_receipt')
                            ->label(__('site.payment_receipt'))
                            ->image()
                            ->imageEditor()
                            ->disk('s3')
                            ->directory('manual-orders/receipts')
                            ->visibility('public'),
                        FileUpload::make('refund_receipt')
                            ->label(__('site.refund_receipt'))
                            ->image()
                            ->imageEditor()
                            ->disk('s3')
                            ->directory('manual-orders/receipts')
                            ->visibility('public')
                            ->visibleOn(['edit', 'view']),
                    ]),
                Section::make(__('site.additional_information'))
                    ->columns(1)
                    ->schema([
                        Toggle::make('active')
                            ->label(__('site.active'))
                            ->default(true),
                        Toggle::make('vip')
                            ->label(__('site.vip'))
                            ->default(false),
                        TextInput::make('delivery_amount')
                            ->label(__('site.delivery_amount'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn(['edit', 'view']),
                        TextInput::make('total_amount')
                            ->label(__('site.total_amount'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn(['edit', 'view']),
                        TextInput::make('status')
                            ->label(__('site.status'))
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn (?string $state): string => $state
                                ? (ManualOrder::statusOptions()[$state] ?? $state)
                                : '')
                            ->visibleOn(['edit', 'view']),
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
                TextColumn::make('code')
                    ->label(__('site.order_code'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage(__('site.copied'))
                    ->copyMessageDuration(1500),
                TextColumn::make('product_name')
                    ->label(__('site.product_name'))
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                TextColumn::make('brand')
                    ->label(__('site.brand'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('fullname')
                    ->label(__('site.fullname'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('mobile')
                    ->label(__('site.mobile'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('user.nickname')
                    ->label(__('site.user'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->url(fn (ManualOrder $record) => $record->user_id
                        ? route('filament.admin.resources.users.view', ['record' => $record->user_id])
                        : null)
                    ->openUrlInNewTab(),
                TextColumn::make('amount')
                    ->label(__('site.amount'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('delivery_amount')
                    ->label(__('site.delivery_amount'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_amount')
                    ->label(__('site.total_amount'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('site.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ManualOrder::PAID, ManualOrder::DELIVERED => 'success',
                        ManualOrder::SHIPPED => 'info',
                        ManualOrder::PENDING => 'warning',
                        ManualOrder::CANCELLED, ManualOrder::FAILED => 'danger',
                        ManualOrder::RETURNED => 'warning',
                        ManualOrder::REFUNDED, ManualOrder::EXPIRED => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ManualOrder::statusOptions()[$state] ?? $state),
                ImageColumn::make('payment_receipt')
                    ->label(__('site.payment_receipt'))
                    ->disk('s3')
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('refund_receipt')
                    ->label(__('site.refund_receipt'))
                    ->disk('s3')
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
                    ->options(ManualOrder::statusOptions()),
                UserIdFilter::make(),
                SelectFilter::make('vip')
                    ->label(__('site.vip'))
                    ->options([
                        1 => __('site.yes'),
                        0 => __('site.no'),
                    ]),
                SelectFilter::make('active')
                    ->label(__('site.active'))
                    ->options([
                        1 => __('site.Active'),
                        0 => __('site.Inactive'),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('change_status')
                    ->label(__('site.change_status'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading(__('site.confirm_status_change'))
                    ->modalDescription(__('site.confirm_status_change_description'))
                    ->visible(function (): bool {
                        $user = Auth::user();

                        return $user instanceof User
                            && $user->can(AdminPermission::MANUAL_ORDERS_CHANGE_STATUS);
                    })
                    ->schema([
                        Select::make('status')
                            ->label(__('site.status'))
                            ->options(ManualOrder::statusOptions())
                            ->default(fn (ManualOrder $record) => $record->status)
                            ->required()
                            ->native(false),
                    ])
                    ->action(function (ManualOrder $record, array $data): void {
                        $user = Auth::user();
                        if (! $user instanceof User || ! $user->can(AdminPermission::MANUAL_ORDERS_CHANGE_STATUS)) {
                            Notification::make()
                                ->title(__('site.error'))
                                ->body(__('site.unauthorized'))
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update([
                            'status' => $data['status'],
                        ]);

                        Notification::make()
                            ->title(__('site.status_updated_successfully'))
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListManualOrders::route('/'),
            'create' => CreateManualOrder::route('/create'),
            'view' => ViewManualOrder::route('/{record}'),
            'edit' => EditManualOrder::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user:id,nickname,first_name,last_name']);
    }

    /**
     * Persist money fields exactly like OrderRepository (no discount).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function preparePersistedData(array $data): array
    {
        unset($data['calc_raw_price']);

        $productsAmount = (float) ($data['amount'] ?? 0);
        $totals = app(ManualOrderPricingService::class)->buildOrderAmounts($productsAmount);

        $data['amount'] = $totals['amount'];
        $data['delivery_amount'] = $totals['delivery_amount'];
        $data['total_amount'] = $totals['total_amount'];
        $data['profit'] = $totals['profit'];
        $data['profit_rate'] = $totals['profit_rate'];
        $data['exchange_rate'] = $totals['exchange_rate'];
        $data['product_count'] = $data['product_count'] ?? 1;
        $data['active'] = isset($data['active']) ? (int) (bool) $data['active'] : 1;
        $data['vip'] = isset($data['vip']) ? (int) (bool) $data['vip'] : 0;

        if (array_key_exists('user_id', $data) && blank($data['user_id'])) {
            $data['user_id'] = null;
        }

        return $data;
    }

    protected static function highlightedMoneyHtml(string $text, string $color): HtmlString
    {
        return new HtmlString(
            '<div style="margin-top:0.25rem;font-size:1.75rem;line-height:1.25;font-weight:800;letter-spacing:-0.02em;color:'.$color.';">'
            .e($text)
            .'</div>'
        );
    }
}
