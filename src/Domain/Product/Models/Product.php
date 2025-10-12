<?php

namespace Domain\Product\Models;

use Domain\Product\Models\Category;
use Domain\Product\Models\File;
use Domain\Review\Models\Review;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    const PENDING = "pending";
    const COMPLETED = "completed";
    const REJECT = "reject";

    protected $guarded = [];

    protected $casts = [
        'status' => 'integer',
        'vip' => 'boolean',
        'priority' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable', 'likeable_type', 'likeable_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function color()
    {
        return $this->belongsTo(Color::class);
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
