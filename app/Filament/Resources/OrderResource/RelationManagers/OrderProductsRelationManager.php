<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Domain\Notification\Services\NotificationService;
use Domain\Product\Models\Product;
use Domain\Wallet\Models\Wallet;
use Domain\Wallet\Models\WalletTransaction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                Select::make('pivot.status')
                    ->label(__('site.status'))
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
                    ])
                    ->required()
                    ->native(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
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
                            $color = \Domain\Product\Models\Color::find($record->pivot->color_id);
                            return $color?->title ?? '-';
                        }
                        return '-';
                    })
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pivot.size_id')
                    ->label(__('site.size'))
                    ->state(function ($record) {
                        if ($record->pivot->size_id) {
                            $size = \Domain\Product\Models\Size::find($record->pivot->size_id);
                            return $size?->title ?? '-';
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
                Tables\Filters\SelectFilter::make('pivot.status')
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
            ->actions([
                Tables\Actions\Action::make('change_status')
                    ->label(__('site.change_status'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('danger')
                    ->form([
                        Select::make('status')
                            ->label(__('site.status'))
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
                            ])
                            ->default(fn ($record) => $record->pivot->status)
                            ->required()
                            ->native(false),
                    ])
                    ->action(function ($record, array $data) {
                        if ($record->status == 'refunded' || $record->pivot->status == 'refunded') {
                            Notification::make()
                                ->title(__('site.error'))
                                ->body(__('site.product_already_refunded'))
                                ->danger()
                                ->send();
                            return;
                        }

                        $ownerRecord = $this->getOwnerRecord();
                        DB::table('order_product')
                            ->where('order_id', $ownerRecord->id)
                            ->where('product_id', $record->id)
                            ->update(['status' => (string) $data['status']]);

                        if ($data['status'] == 'refunded') {

                            $wallet = Wallet::query()
                                ->where('user_id', $ownerRecord->user_id)
                                ->first();
                            $amount = (float) $record->pivot->amount;
                            $description = __('site.order_product_refunded');

                            // Create transaction record
                            WalletTransaction::createTransaction(
                                wallet: $wallet,
                                amount: $amount,
                                type: WalletTransaction::DEPOSITE,
                                description: $description,
                                status: WalletTransaction::COMPLETED
                            );

                            NotificationService::create([
                                'title' => __('site.order_product_refunded_title'),
                                'content' => __('site.order_product_refunded_content', ['amount' => number_format($amount, 2), 'currency' => __('site.currency')]),
                                'id' => $record->id,
                                'type' => NotificationService::ORDER,
                            ], $ownerRecord->user);

                        }

                        $exists = DB::table('order_product')
                            ->where('order_id', $ownerRecord->id)
                            ->where('product_id', $record->id)
                            ->where('status', '!=', 'refunded')
                            ->exists();

                        if (!$exists) {
                            $ownerRecord->update(['status' => 'refunded']);
                        }

                        Notification::make()
                            ->title(__('site.status_updated_successfully'))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for order products
            ])
            ->defaultSort('products.id', 'desc');
    }
}
