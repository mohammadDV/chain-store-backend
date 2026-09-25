<?php

namespace Domain\Product\Models;

use Database\Factories\SizeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $title
 * @property string|null $code
 * @property int $status
 * @property int $priority
 * @property int $product_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 * @property-read Stock|null $stock
 */
class Size extends Model
{
    /** @use HasFactory<SizeFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * Always eager-load stock with sizes to avoid N+1 when SizeResource
     * (or admin tables) read quantity.
     *
     * @var list<string>
     */
    protected $with = ['stock'];

    protected static function booted(): void
    {
        static::created(function (Size $size) {
            if (! $size->stock()->exists()) {
                $size->stock()->create([
                    'quantity' => 0,
                    'reserved' => 0,
                ]);
            }
        });
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasOne<Stock, $this>
     */
    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class);
    }

    protected static function newFactory(): SizeFactory
    {
        return SizeFactory::new();
    }
}
