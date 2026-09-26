<?php

namespace Domain\Product\Services;

use Domain\Product\Enums\OrderLedgerType;
use Domain\Product\Models\Order;
use Domain\Product\Models\OrderLedger;

class OrderLedgerService
{
    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function record(
        Order $order,
        OrderLedgerType $type,
        string $source,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?int $userId = null,
        ?string $message = null,
        ?array $meta = null,
        ?int $orderProductId = null,
    ): OrderLedger {
        return OrderLedger::query()->create([
            'order_id' => $order->id,
            'order_product_id' => $orderProductId,
            'type' => $type,
            'source' => $source,
            'user_id' => $userId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'message' => $message,
            'meta' => $meta,
        ]);
    }
}
