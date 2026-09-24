<?php

namespace Domain\Product\Services;

use Domain\Product\Models\Order;
use Domain\Product\Models\OrderProduct;
use Domain\Product\Models\Size;
use Domain\Product\Models\Stock;
use InvalidArgumentException;
use RuntimeException;

class StockService
{
    public function ensureForSize(Size $size, int $quantity = 0): Stock
    {
        return Stock::query()->firstOrCreate(
            ['size_id' => $size->id],
            [
                'quantity' => max(0, $quantity),
                'reserved' => 0,
            ]
        );
    }

    public function setQuantity(int $sizeId, int $quantity): Stock
    {
        $quantity = max(0, $quantity);

        $stock = Stock::query()->firstOrCreate(
            ['size_id' => $sizeId],
            [
                'quantity' => $quantity,
                'reserved' => 0,
            ]
        );

        if (! $stock->wasRecentlyCreated) {
            $stock->update(['quantity' => $quantity]);
        }

        return $stock->refresh();
    }

    /**
     * Available quantity for placing an order.
     * Soft-reserves pending orders when $withPendingReservation is true.
     * Locks the stock row — must be called inside a DB transaction.
     */
    public function availableForOrder(int $sizeId, bool $withPendingReservation): int
    {
        $stock = Stock::query()
            ->where('size_id', $sizeId)
            ->lockForUpdate()
            ->first();

        if (! $stock) {
            return 0;
        }

        $pendingCount = 0;
        if ($withPendingReservation) {
            $pendingCount = (int) OrderProduct::query()
                ->whereHas('order', function ($query) {
                    $query->where('status', Order::PENDING)
                        ->where('active', 1);
                })
                ->where('size_id', $sizeId)
                ->sum('count');
        }

        return max(0, $stock->quantity - $pendingCount);
    }

    /**
     * Hard-decrement stock after payment. Locks the row — call inside a transaction.
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function decrementForOrder(int $sizeId, int $count): void
    {
        if ($count < 1) {
            throw new InvalidArgumentException('Decrement count must be at least 1.');
        }

        $stock = Stock::query()
            ->where('size_id', $sizeId)
            ->lockForUpdate()
            ->first();

        if (! $stock) {
            throw new RuntimeException("Stock not found for size_id {$sizeId}.");
        }

        if ($stock->quantity < $count) {
            throw new RuntimeException("Insufficient stock for size_id {$sizeId}.");
        }

        $stock->decrement('quantity', $count);
    }
}
