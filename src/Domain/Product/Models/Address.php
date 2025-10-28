<?php

namespace Domain\Product\Models;

use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    /** @use HasFactory<\Database\Factories\AddressFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_default' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
