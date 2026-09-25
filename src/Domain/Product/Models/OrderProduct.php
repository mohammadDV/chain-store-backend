<?php

namespace Domain\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int|null $size_id
 * @property int $count
 * @property float|int|null $amount
 * @property string|null $status
 * @property int|null $color_id
 * @property int $order_id
 * @property int $product_id
 */
class OrderProduct extends Model
{
    protected $table = 'order_product';

    protected $guarded = [];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }
}
