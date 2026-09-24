<?php

namespace Domain\Brand\Models;

use Database\Factories\BrandFactory;
use Domain\Product\Models\Color;
use Domain\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory(): BrandFactory
    {
        return BrandFactory::new();
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the banners that belong to the brand.
     */
    public function banners()
    {
        return $this->hasMany(Banner::class);
    }

    /**
     * Get the colors that belong to the brand.
     */
    public function colors()
    {
        return $this->belongsToMany(Color::class);
    }
}
