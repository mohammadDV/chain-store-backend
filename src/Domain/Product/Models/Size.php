<?php

namespace Domain\Product\Models;

use Database\Factories\SizeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class);
    }

    protected static function newFactory(): SizeFactory
    {
        return SizeFactory::new();
    }
}
