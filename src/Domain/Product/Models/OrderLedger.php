<?php

namespace Domain\Product\Models;

use Domain\Product\Enums\OrderLedgerType;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property int|null $order_product_id
 * @property OrderLedgerType $type
 * @property string $source
 * @property int|null $user_id
 * @property string|null $from_status
 * @property string|null $to_status
 * @property string|null $message
 * @property array<string, mixed>|null $meta
 */
class OrderLedger extends Model
{
    protected $fillable = [
        'order_id',
        'order_product_id',
        'type',
        'source',
        'user_id',
        'from_status',
        'to_status',
        'message',
        'meta',
    ];

    protected $casts = [
        'type' => OrderLedgerType::class,
        'meta' => 'array',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<OrderProduct, $this>
     */
    public function orderProduct(): BelongsTo
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id');
    }
}
