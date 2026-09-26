<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\UserResource;
use Domain\Product\Enums\OrderLedgerType;
use Domain\Product\Models\Order;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class ManageOrderLedgers extends ManageRelatedRecords
{
    protected static string $resource = OrderResource::class;

    protected static string $relationship = 'ledgers';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    public function getTitle(): string|Htmlable
    {
        /** @var Order $order */
        $order = $this->getRecord();

        return __('site.order_ledger').' — '.$order->code;
    }

    public function getHeading(): string|Htmlable
    {
        return $this->getTitle();
    }

    public static function getNavigationLabel(): string
    {
        return __('site.order_ledger');
    }

    public function getBreadcrumb(): string
    {
        return __('site.order_ledger');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('step')
                    ->label('#')
                    ->rowIndex(),
                TextColumn::make('created_at')
                    ->label(__('site.created_at'))
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('site.type'))
                    ->badge()
                    ->formatStateUsing(fn (OrderLedgerType|string $state): string => __('site.order_ledger_type_'.(is_string($state) ? $state : $state->value)))
                    ->color(fn (OrderLedgerType|string $state): string => match (is_string($state) ? $state : $state->value) {
                        'created' => 'info',
                        'paid' => 'success',
                        'expired' => 'warning',
                        'status_changed' => 'gray',
                        'refunded', 'line_refunded' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('from_status')
                    ->label(__('site.from_status'))
                    ->formatStateUsing(fn (?string $state): string => $state ? __('site.'.$state) : '—'),
                TextColumn::make('to_status')
                    ->label(__('site.to_status'))
                    ->formatStateUsing(fn (?string $state): string => $state ? __('site.'.$state) : '—'),
                TextColumn::make('user')
                    ->label(__('site.user'))
                    ->state(function ($record): string {
                        if (! $record->user) {
                            return __('site.system');
                        }

                        return $record->user->nickname
                            ?: trim(($record->user->first_name ?? '').' '.($record->user->last_name ?? ''))
                            ?: ('#'.$record->user_id);
                    })
                    ->url(fn ($record): ?string => $record->user_id
                        ? UserResource::getUrl('view', ['record' => $record->user_id])
                        : null)
                    ->color(fn ($record): string => $record->user_id ? 'primary' : 'gray')
                    ->openUrlInNewTab(),
                TextColumn::make('source')
                    ->label(__('site.source'))
                    ->formatStateUsing(fn (string $state): string => __('site.order_ledger_source_'.$state)),
                TextColumn::make('message')
                    ->label(__('site.ledger_message'))
                    ->wrap()
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'asc')
            ->modifyQueryUsing(fn ($query) => $query->with(['user:id,nickname,first_name,last_name']))
            ->striped()
            ->paginated([25, 50, 100])
            ->emptyStateHeading(__('site.order_ledger_empty'))
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_order')
                ->label(__('site.order'))
                ->icon('heroicon-o-eye')
                ->url(fn (): string => OrderResource::getUrl('view', ['record' => $this->getRecord()]))
                ->color('gray'),
            Action::make('back_to_orders')
                ->label(__('site.orders'))
                ->icon('heroicon-o-arrow-right')
                ->url(OrderResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}
