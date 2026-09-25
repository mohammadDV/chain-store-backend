<?php

namespace Domain\Product\Models;

use Database\Factories\InventoryTransactionFactory;
use Domain\Product\Enums\InventoryTransactionType;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    /** @use HasFactory<InventoryTransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'size_id',
        'type',
        'source',
        'user_id',
        'quantity_change',
        'previous_quantity',
        'resulting_quantity',
        'description',
    ];

    protected $casts = [
        'type' => InventoryTransactionType::class,
        'quantity_change' => 'integer',
        'previous_quantity' => 'integer',
        'resulting_quantity' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): InventoryTransactionFactory
    {
        return InventoryTransactionFactory::new();
    }
}
