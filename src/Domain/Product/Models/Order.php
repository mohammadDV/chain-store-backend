<?php

namespace Domain\Product\Models;

use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    const PENDING = "pending";
    const COMPLETED = "completed";
    const CANCELLED = "cancelled";
    const SHIPPED = "shipped";
    const DELIVERED = "delivered";
    const RETURNED = "returned";
    const REFUNDED = "refunded";
    const FAILED = "failed";

    protected $guarded = [];

    protected $casts = [
        'active' => 'integer',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_product', 'order_id', 'product_id')->withPivot('count', 'amount', 'status', 'color_id', 'size_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }
}
