<?php

namespace Domain\Brand\Models;

use Database\Factories\BrandFactory;
use Domain\Product\Models\Color;
use Domain\Product\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $logo
 * @property string|null $domain
 * @property string|null $description
 * @property int $status
 * @property int $priority
 * @property int $has_stock_management
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Product> $products
 * @property-read Collection<int, Banner> $banners
 * @property-read Collection<int, Color> $colors
 */
class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory(): BrandFactory
    {
        return BrandFactory::new();
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the banners that belong to the brand.
     *
     * @return HasMany<Banner, $this>
     */
    public function banners(): HasMany
    {
        return $this->hasMany(Banner::class);
    }

    /**
     * Get the colors that belong to the brand.
     *
     * @return BelongsToMany<Color, $this>
     */
    public function colors(): BelongsToMany
    {
        return $this->belongsToMany(Color::class);
    }
}
