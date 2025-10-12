<?php

namespace Domain\Review\Models;

use Domain\Product\Models\Like;
use Domain\Product\Models\Product;
use Domain\Product\Models\Service;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    /** @use HasFactory<\Database\Factories\ReviewFactory> */
    use HasFactory;

    const PENDING = 'pending';
    const APPROVED = 'approved';
    const CANCELLED = 'cancelled';

    protected $guarded = [];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable', 'likeable_type', 'likeable_id');
    }
}
