<?php

namespace Domain\Seo\Models;

use Illuminate\Database\Eloquent\Model;

class SeoRedirect extends Model
{
    protected $guarded = [];

    protected $casts = [
        'status_code' => 'integer',
    ];
}
