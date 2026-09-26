<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Domain\AdminAccess\AdminPermission;
use Domain\AdminAccess\Services\AdminAccessService;
use Domain\Product\Enums\OrderLedgerSource;
use Domain\Product\Exceptions\OrderAlreadyRefundedException;
use Domain\Product\Models\Color;
use Domain\Product\Models\Order;
use Domain\Product\Models\Size;
use Domain\Product\Services\OrderStatusService;
use Domain\User\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class OrderProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('site.products');
    }

    public static function getModelLabel(): ?string
    {
        return __('site.product');
    }

    public static function getPluralModelLabel(): ?string
    {
        return __('site.products');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('pivot.count')
                    ->label(__('site.count'))
                    ->numeric()
                    ->required()
                    ->minValue(1),
                TextInput::make('pivot.amount')
                    ->label(__('site.amount'))
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->prefix('$'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->modifyQueryUsing(function (Builder $query): Builder {
                $user = Auth::user();
                if (! $user instanceof User) {
                    return $query->whereRaw('1 = 0');
                }

                return app(AdminAccessService::class)->scopeBrandQuery($query, $user, 'brand_id');
            })
            ->columns([
                ImageColumn::make('image')
                    ->label(__('site.image'))
                    ->disk('s3')
                    ->visibility('public')
                    ->size(60)
                    ->extraImgAttributes(['loading' => 'lazy']),
                TextColumn::make('id')
                    ->label(__('site.table_id'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('title')
                    ->label(__('site.title'))
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->wrap(),
                TextColumn::make('code')
                    ->label(__('site.code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('brand.title')
                    ->label(__('site.brand'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('categories.title')
                    ->label(__('site.category'))
                    ->badge()
                    ->separator(',')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pivot.count')
                    ->label(__('site.count'))
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('pivot.amount')
                    ->label(__('site.unit_price'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('total_price')
                    ->label(__('site.total_price'))
                    ->state(function ($record) {
                        return $record->pivot->count * $record->pivot->amount;
                    })
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('pivot.color_id')
                    ->label(__('site.color'))
                    ->state(function ($record) {
                        if ($record->pivot->color_id) {
                            $color = Color::find($record->pivot->color_id);

                            return $color !== null ? $color->title : '-';
                        }

                        return '-';
                    })
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pivot.size_id')
                    ->label(__('site.size'))
                    ->state(function ($record) {
                        if ($record->pivot->size_id) {
                            $size = Size::find($record->pivot->size_id);

                            return $size !== null ? $size->title : '-';
                        }

                        return '-';
                    })
                    ->searchable(),
                TextColumn::make('pivot.status')
                    ->label(__('site.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'delivered' => 'success',
                        'shipped' => 'info',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        'returned' => 'warning',
                        'refunded' => 'gray',
                        'failed' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => __('site.pending'),
                        'expired' => __('site.expired'),
                        'paid' => __('site.paid'),
                        'cancelled' => __('site.cancelled'),
                        'shipped' => __('site.shipped'),
                        'delivered' => __('site.delivered'),
                        'returned' => __('site.returned'),
                        'refunded' => __('site.refunded'),
                        'failed' => __('site.failed'),
                        default => $state,
                    }),
                TextColumn::make('url')
                    ->label(__('site.product_url'))
                    ->url(fn ($record) => $record->url ?: null)
                    ->openUrlInNewTab()
                    ->limit(30)
                    ->copyable()
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('success'),
                TextColumn::make('view_product')
                    ->label(__('site.view_product'))
                    ->state(__('site.view'))
                    ->url(fn ($record) => route('filament.admin.resources.products.view', ['record' => $record->id]))
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('primary'),
            ])
            ->filters([
                SelectFilter::make('pivot.status')
                    ->label(__('site.order_product_status'))
                    ->options([
                        'pending' => __('site.pending'),
                        'expired' => __('site.expired'),
                        'paid' => __('site.paid'),
                        'cancelled' => __('site.cancelled'),
                        'shipped' => __('site.shipped'),
                        'delivered' => __('site.delivered'),
                        'returned' => __('site.returned'),
                        'refunded' => __('site.refunded'),
                        'failed' => __('site.failed'),
                    ]),
            ])
            ->headerActions([
                // No create action for order products
            ])
            ->recordActions([
                Action::make('change_status')
                    ->label(__('site.change_status'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('danger')
                    ->hidden(function ($record): bool {
                        $owner = $this->getOwnerRecord();

                        return ($owner instanceof Order && $owner->status === Order::REFUNDED)
                            || $record->pivot->status === Order::REFUNDED;
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('site.confirm_status_change'))
                    ->modalDescription(__('site.confirm_status_change_description'))
                    ->visible(function (): bool {
                        $user = Auth::user();

                        return $user instanceof User
                            && ($user->can(AdminPermission::ORDERS_CHANGE_STATUS) || $user->can(AdminPermission::ORDERS_REFUND));
                    })
                    ->schema([
                        Select::make('status')
                            ->label(__('site.status'))
                            ->options(function (): array {
                                $options = [
                                    'pending' => __('site.pending'),
                                    'expired' => __('site.expired'),
                                    'paid' => __('site.paid'),
                                    'cancelled' => __('site.cancelled'),
                                    'shipped' => __('site.shipped'),
                                    'delivered' => __('site.delivered'),
                                    'returned' => __('site.returned'),
                                    'failed' => __('site.failed'),
                                ];

                                $user = Auth::user();
                                if ($user instanceof User && $user->can(AdminPermission::ORDERS_REFUND)) {
                                    $options['refunded'] = __('site.refunded');
                                }

                                return $options;
                            })
                            ->default(fn ($record) => $record->pivot->status)
                            ->required()
                            ->native(false),
                        Textarea::make('message')
                            ->label(__('site.ledger_message'))
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->action(function ($record, array $data) {
                        $user = Auth::user();
                        if (! $user instanceof User) {
                            return;
                        }

                        if ($data['status'] === 'refunded') {
                            if (! $user->can(AdminPermission::ORDERS_REFUND)) {
                                Notification::make()
                                    ->title(__('site.error'))
                                    ->body(__('site.unauthorized'))
                                    ->danger()
                                    ->send();

                                return;
                            }
                        } elseif (! $user->can(AdminPermission::ORDERS_CHANGE_STATUS)) {
                            Notification::make()
                                ->title(__('site.error'))
                                ->body(__('site.unauthorized'))
                                ->danger()
                                ->send();

                            return;
                        }

                        $ownerRecord = $this->getOwnerRecord();
                        assert($ownerRecord instanceof Order);

                        $orderProductId = (int) ($record->pivot->id ?? 0);
                        if ($orderProductId < 1) {
                            Notification::make()
                                ->title(__('site.error'))
                                ->body(__('site.Order not found'))
                                ->danger()
                                ->send();

                            return;
                        }

                        try {
                            app(OrderStatusService::class)->transitionLine(
                                order: $ownerRecord,
                                orderProductId: $orderProductId,
                                toStatus: (string) $data['status'],
                                source: OrderLedgerSource::Admin,
                                actor: $user,
                                message: $data['message'] ?? null,
                            );
                        } catch (OrderAlreadyRefundedException|\InvalidArgumentException $e) {
                            Notification::make()
                                ->title(__('site.error'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title(__('site.status_updated_successfully'))
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                // No bulk actions for order products
            ])
            ->defaultSort('products.id', 'desc');
    }
}
