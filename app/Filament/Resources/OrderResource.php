<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers\OrderProductsRelationManager;
use Domain\Notification\Services\NotificationService;
use Domain\Product\Models\Order;
use Domain\User\Models\User;
use Domain\Wallet\Models\Wallet;
use Domain\Wallet\Models\WalletTransaction;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?int $navigationSort = 12;

    public static function getNavigationLabel(): string
    {
        return __('site.orders');
    }

    public static function getModelLabel(): string
    {
        return __('site.order');
    }

    public static function getPluralModelLabel(): string
    {
        return __('site.orders');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('site.order_information'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('code')
                                    ->label(__('site.order_code'))
                                    ->disabled()
                                    ->dehydrated(false),
                                Select::make('user_id')
                                    ->label(__('site.user'))
                                    ->relationship('user', 'nickname', fn ($query) => $query->whereNotNull('nickname'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('product_count')
                                    ->label(__('site.product_count'))
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('total_amount')
                                    ->label(__('site.total_amount'))
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->prefix('$'),
                                TextInput::make('discount_amount')
                                    ->label(__('site.discount_amount'))
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->prefix('$'),
                                TextInput::make('amount')
                                    ->label(__('site.amount'))
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->prefix('$'),
                            ]),
                        // Select::make('status')
                        //     ->label(__('site.status'))
                        //     ->options([
                        //         Order::PENDING => __('site.pending'),
                        //         Order::PAID => __('site.paid'),
                        //         Order::CANCELLED => __('site.cancelled'),
                        //         Order::SHIPPED => __('site.shipped'),
                        //         Order::DELIVERED => __('site.delivered'),
                        //         Order::RETURNED => __('site.returned'),
                        //         Order::REFUNDED => __('site.refunded'),
                        //         Order::FAILED => __('site.failed'),
                        //         Order::EXPIRED => __('site.expired'),
                        //     ])
                        //     ->required()
                        //     ->native(false),
                        Textarea::make('description')
                            ->label(__('site.description'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make(__('site.shipping_information'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('fullname')
                                    ->label(__('site.fullname'))
                                    ->maxLength(255),
                                TextInput::make('postal_code')
                                    ->label(__('site.postal_code'))
                                    ->maxLength(255),
                            ]),
                        Textarea::make('address')
                            ->label(__('site.address'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make(__('site.additional_information'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('active')
                                    ->label(__('site.active'))
                                    ->default(true),
                                Toggle::make('vip')
                                    ->label(__('site.vip'))
                                    ->default(false),
                            ]),
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
                TextColumn::make('user.nickname')
                    ->label(__('site.user'))
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => route('filament.admin.resources.users.view', ['record' => $record->user_id]))
                    ->openUrlInNewTab(),
                TextColumn::make('product_count')
                    ->label(__('site.product_count'))
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('total_amount')
                    ->label(__('site.total_amount'))
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('discount_amount')
                    ->label(__('site.discount_amount'))
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('amount')
                    ->label(__('site.amount'))
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('site.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Order::PAID => 'success',
                        Order::DELIVERED => 'success',
                        Order::SHIPPED => 'info',
                        Order::PENDING => 'warning',
                        Order::CANCELLED => 'danger',
                        Order::RETURNED => 'warning',
                        Order::REFUNDED => 'gray',
                        Order::FAILED => 'danger',
                        Order::EXPIRED => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Order::PENDING => __('site.pending'),
                        Order::PAID => __('site.paid'),
                        Order::CANCELLED => __('site.cancelled'),
                        Order::SHIPPED => __('site.shipped'),
                        Order::DELIVERED => __('site.delivered'),
                        Order::RETURNED => __('site.returned'),
                        Order::REFUNDED => __('site.refunded'),
                        Order::FAILED => __('site.failed'),
                        Order::EXPIRED => __('site.expired'),
                        default => $state,
                    }),
                TextColumn::make('fullname')
                    ->label(__('site.fullname'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('vip')
                    ->label(__('site.vip'))
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('active')
                    ->label(__('site.active'))
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray')
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
                        Order::PENDING => __('site.pending'),
                        Order::PAID => __('site.paid'),
                        Order::CANCELLED => __('site.cancelled'),
                        Order::SHIPPED => __('site.shipped'),
                        Order::DELIVERED => __('site.delivered'),
                        Order::RETURNED => __('site.returned'),
                        Order::REFUNDED => __('site.refunded'),
                        Order::FAILED => __('site.failed'),
                        Order::EXPIRED => __('site.expired'),
                    ]),
                SelectFilter::make('user_id')
                    ->label(__('site.user'))
                    ->relationship('user', 'nickname', fn ($query) => $query->whereNotNull('nickname'))
                    ->searchable()
                    ->preload(),
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
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('change_status')
                    ->label(__('site.change_status'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->form([
                        Select::make('status')
                            ->label(__('site.status'))
                            ->options([
                                Order::PENDING => __('site.pending'),
                                Order::PAID => __('site.paid'),
                                Order::CANCELLED => __('site.cancelled'),
                                Order::SHIPPED => __('site.shipped'),
                                Order::DELIVERED => __('site.delivered'),
                                Order::RETURNED => __('site.returned'),
                                Order::REFUNDED => __('site.refunded'),
                                Order::FAILED => __('site.failed'),
                                Order::EXPIRED => __('site.expired'),
                            ])
                            ->default(fn ($record) => $record->status)
                            ->required()
                            ->native(false),
                    ])
                    ->action(function ($record, array $data) {

                        $exists = DB::table('order_product')
                            ->where('order_id', $record->id)
                            ->where('status', 'refunded')
                            ->exists();

                        if ($exists) {
                            Notification::make()
                                ->title(__('site.error'))
                                ->body(__('site.one_of_the_products_already_refunded'))
                                ->danger()
                                ->send();
                            return;
                        }

                        if ($record->status == 'refunded') {
                            Notification::make()
                                ->title(__('site.error'))
                                ->body(__('site.product_already_refunded'))
                                ->danger()
                                ->send();
                            return;
                        }

                        $record->update(['status' => $data['status']]);

                        if ($data['status'] == 'refunded') {

                            DB::table('order_product')
                                ->where('order_id', $record->id)
                                ->update(['status' => 'refunded']);

                            $wallet = Wallet::query()
                                ->where('user_id', $record->user_id)
                                ->first();

                            $amount = (float) $record->total_amount;
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
                            ], $record->user);

                        }
                        Notification::make()
                            ->title(__('site.status_updated_successfully'))
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            OrderProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with(['user:id,nickname,first_name,last_name', 'products:id,title,code,image,url,status']);
    }
}