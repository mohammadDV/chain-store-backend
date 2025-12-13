<?php

namespace Domain\Product\Models;

use Domain\Brand\Models\Brand;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryEndpoint extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryEndpointFactory> */
    use HasFactory;

    protected $guarded = [];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
