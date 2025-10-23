<?php

namespace Domain\Product\Models;

use Domain\Brand\Models\Brand;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    /** @use HasFactory<\Database\Factories\ColorFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'status' => 'integer',
        'priority' => 'integer',
    ];

    /**
     * Get the product that owns the file.
     */
    public function product()
    {
        return $this->hasMany(Product::class);
    }

    public function brands()
    {
        return $this->belongsToMany(Brand::class, 'brand_color', 'color_id', 'brand_id')->withPivot('priority', 'status');
    }
}