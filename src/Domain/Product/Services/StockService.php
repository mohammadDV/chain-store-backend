<?php

namespace Domain\Product\Services;

use Domain\Product\Enums\InventoryTransactionSource;
use Domain\Product\Enums\InventoryTransactionType;
use Domain\Product\Models\InventoryTransaction;
use Domain\Product\Models\Size;
use Domain\Product\Models\Stock;
use Illuminate\Support\Facades\DB;
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

    public function setQuantity(
        int $sizeId,
        int $quantity,
        string $source = InventoryTransactionSource::System,
        ?int $userId = null,
        ?string $description = null,
    ): Stock {
        return $this->applyAbsoluteQuantity(
            $sizeId,
            max(0, $quantity),
            InventoryTransactionType::Adjust,
            $source,
            $userId,
            $description,
        );
    }

    public function adjust(
        int $sizeId,
        int $quantityChange,
        string $source = InventoryTransactionSource::System,
        ?int $userId = null,
        ?string $description = null,
    ): Stock {
        return $this->applyDelta(
            $sizeId,
            $quantityChange,
            InventoryTransactionType::Adjust,
            $source,
            $userId,
            $description,
            allowNegativeResult: false,
        );
    }

    public function purchase(
        int $sizeId,
        int $quantity,
        string $source = InventoryTransactionSource::System,
        ?int $userId = null,
        ?string $description = null,
    ): Stock {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Purchase quantity must be at least 1.');
        }

        return $this->applyDelta(
            $sizeId,
            $quantity,
            InventoryTransactionType::Purchase,
            $source,
            $userId,
            $description,
            allowNegativeResult: false,
        );
    }

    public function returnStock(
        int $sizeId,
        int $quantity,
        string $source = InventoryTransactionSource::System,
        ?int $userId = null,
        ?string $description = null,
    ): Stock {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Return quantity must be at least 1.');
        }

        return $this->applyDelta(
            $sizeId,
            $quantity,
            InventoryTransactionType::Return,
            $source,
            $userId,
            $description,
            allowNegativeResult: false,
        );
    }

    public function reserve(
        int $sizeId,
        int $quantity,
        string $source = InventoryTransactionSource::System,
        ?int $userId = null,
        ?string $description = null,
    ): Stock {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Reserve quantity must be at least 1.');
        }

        return $this->applyDelta(
            $sizeId,
            -$quantity,
            InventoryTransactionType::Reserve,
            $source,
            $userId,
            $description,
            allowNegativeResult: false,
        );
    }

    public function release(
        int $sizeId,
        int $quantity,
        string $source = InventoryTransactionSource::System,
        ?int $userId = null,
        ?string $description = null,
    ): Stock {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Release quantity must be at least 1.');
        }

        return $this->applyDelta(
            $sizeId,
            $quantity,
            InventoryTransactionType::Release,
            $source,
            $userId,
            $description,
            allowNegativeResult: false,
        );
    }

    /**
     * Apply a signed quantity delta with an explicit ledger type (e.g. admin tools).
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function applyManualChange(
        int $sizeId,
        int $quantityChange,
        InventoryTransactionType $type,
        string $source = InventoryTransactionSource::Admin,
        ?int $userId = null,
        ?string $description = null,
    ): InventoryTransaction {
        if ($quantityChange === 0) {
            throw new InvalidArgumentException('Quantity change must not be zero.');
        }

        return DB::transaction(function () use (
            $sizeId,
            $quantityChange,
            $type,
            $source,
            $userId,
            $description,
        ) {
            [$size, $stock] = $this->lockSizeAndStock($sizeId);
            $previous = (int) $stock->quantity;
            $resulting = $previous + $quantityChange;

            if ($resulting < 0) {
                throw new RuntimeException("Insufficient stock for size_id {$sizeId}.");
            }

            $stock->update(['quantity' => $resulting]);

            return InventoryTransaction::query()->create([
                'product_id' => $size->product_id,
                'size_id' => $size->id,
                'type' => $type,
                'source' => $source,
                'user_id' => $userId,
                'quantity_change' => $quantityChange,
                'previous_quantity' => $previous,
                'resulting_quantity' => $resulting,
                'description' => $description,
            ]);
        });
    }

    /**
     * Available quantity for placing an order.
     * When $withPendingReservation is true, uses stocks.reserved (soft hold).
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

        if ($withPendingReservation) {
            return max(0, (int) $stock->quantity - (int) $stock->reserved);
        }

        return max(0, (int) $stock->quantity);
    }

    /**
     * Soft-reserve units for a pending order. Locks the row — call inside a transaction.
     */
    public function reserveForOrder(int $sizeId, int $count): void
    {
        if ($count < 1) {
            throw new InvalidArgumentException('Reserve count must be at least 1.');
        }

        [$size, $stock] = $this->lockSizeAndStock($sizeId);
        $available = max(0, (int) $stock->quantity - (int) $stock->reserved);

        if ($available < $count) {
            throw new RuntimeException("Insufficient stock for size_id {$sizeId}.");
        }

        $stock->update(['reserved' => (int) $stock->reserved + $count]);
    }

    /**
     * Release soft reservation (expire / replace cart). Locks the row — call inside a transaction.
     */
    public function releaseForOrder(int $sizeId, int $count): void
    {
        if ($count < 1) {
            return;
        }

        [, $stock] = $this->lockSizeAndStock($sizeId);
        $stock->update([
            'reserved' => max(0, (int) $stock->reserved - $count),
        ]);
    }

    /**
     * Hard-decrement stock after payment and release the matching reservation.
     * Locks the row — safe inside an outer transaction.
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function decrementForOrder(
        int $sizeId,
        int $count,
        ?int $userId = null,
        ?string $description = null,
        bool $releaseReservation = true,
    ): void {
        if ($count < 1) {
            throw new InvalidArgumentException('Decrement count must be at least 1.');
        }

        DB::transaction(function () use ($sizeId, $count, $userId, $description, $releaseReservation) {
            [$size, $stock] = $this->lockSizeAndStock($sizeId);
            $previous = (int) $stock->quantity;
            $resulting = $previous - $count;

            if ($resulting < 0) {
                throw new RuntimeException("Insufficient stock for size_id {$sizeId}.");
            }

            $reserved = (int) $stock->reserved;
            if ($releaseReservation) {
                $reserved = max(0, $reserved - $count);
            }

            $stock->update([
                'quantity' => $resulting,
                'reserved' => $reserved,
            ]);

            InventoryTransaction::query()->create([
                'product_id' => $size->product_id,
                'size_id' => $size->id,
                'type' => InventoryTransactionType::Sale,
                'source' => InventoryTransactionSource::Order,
                'user_id' => $userId,
                'quantity_change' => -$count,
                'previous_quantity' => $previous,
                'resulting_quantity' => $resulting,
                'description' => $description,
            ]);
        });
    }

    private function applyAbsoluteQuantity(
        int $sizeId,
        int $resultingQuantity,
        InventoryTransactionType $type,
        string $source,
        ?int $userId,
        ?string $description,
    ): Stock {
        return DB::transaction(function () use ($sizeId, $resultingQuantity, $type, $source, $userId, $description) {
            [$size, $stock] = $this->lockSizeAndStock($sizeId);
            $previous = (int) $stock->quantity;

            return $this->commitQuantityChange(
                $size,
                $stock,
                $previous,
                $resultingQuantity,
                $type,
                $source,
                $userId,
                $description,
            );
        });
    }

    private function applyDelta(
        int $sizeId,
        int $quantityChange,
        InventoryTransactionType $type,
        string $source,
        ?int $userId,
        ?string $description,
        bool $allowNegativeResult,
    ): Stock {
        return DB::transaction(function () use (
            $sizeId,
            $quantityChange,
            $type,
            $source,
            $userId,
            $description,
            $allowNegativeResult,
        ) {
            [$size, $stock] = $this->lockSizeAndStock($sizeId);
            $previous = (int) $stock->quantity;
            $resulting = $previous + $quantityChange;

            if (! $allowNegativeResult && $resulting < 0) {
                throw new RuntimeException("Insufficient stock for size_id {$sizeId}.");
            }

            return $this->commitQuantityChange(
                $size,
                $stock,
                $previous,
                max(0, $resulting),
                $type,
                $source,
                $userId,
                $description,
            );
        });
    }

    /**
     * @return array{0: Size, 1: Stock}
     */
    private function lockSizeAndStock(int $sizeId): array
    {
        $size = Size::query()
            ->whereKey($sizeId)
            ->lockForUpdate()
            ->first();

        if (! $size) {
            throw new RuntimeException("Size not found for size_id {$sizeId}.");
        }

        $stock = Stock::query()
            ->where('size_id', $sizeId)
            ->lockForUpdate()
            ->first();

        if (! $stock) {
            Stock::query()->create([
                'size_id' => $sizeId,
                'quantity' => 0,
                'reserved' => 0,
            ]);

            $stock = Stock::query()
                ->where('size_id', $sizeId)
                ->lockForUpdate()
                ->firstOrFail();
        }

        return [$size, $stock];
    }

    private function commitQuantityChange(
        Size $size,
        Stock $stock,
        int $previousQuantity,
        int $resultingQuantity,
        InventoryTransactionType $type,
        string $source,
        ?int $userId,
        ?string $description,
    ): Stock {
        $quantityChange = $resultingQuantity - $previousQuantity;

        if ($quantityChange === 0) {
            return $stock;
        }

        $stock->update(['quantity' => $resultingQuantity]);

        InventoryTransaction::query()->create([
            'product_id' => $size->product_id,
            'size_id' => $size->id,
            'type' => $type,
            'source' => $source,
            'user_id' => $userId,
            'quantity_change' => $quantityChange,
            'previous_quantity' => $previousQuantity,
            'resulting_quantity' => $resultingQuantity,
            'description' => $description,
        ]);

        return $stock->refresh();
    }
}
