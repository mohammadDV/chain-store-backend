<?php

namespace Domain\Product\Models;

use Database\Factories\ColorFactory;
use Domain\Brand\Models\Brand;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string $code
 * @property int $status
 * @property int $priority
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Product> $product
 * @property-read Collection<int, Brand> $brands
 */
class Color extends Model
{
    /** @use HasFactory<ColorFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'status' => 'integer',
        'priority' => 'integer',
    ];

    protected static function newFactory(): ColorFactory
    {
        return ColorFactory::new();
    }

    /**
     * Get the products that use this color.
     *
     * @return HasMany<Product, $this>
     */
    public function product(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return BelongsToMany<Brand, $this>
     */
    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'brand_color', 'color_id', 'brand_id')->withPivot('priority', 'status');
    }
}
