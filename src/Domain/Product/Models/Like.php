<?php

namespace Domain\Product\Models;

use Database\Factories\LikeFactory;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    /** @use HasFactory<LikeFactory> */
    use HasFactory;

    protected $guarded = [];

    public function likeable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
