<?php

namespace App;

use Domain\Product\Models\Product;
use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
