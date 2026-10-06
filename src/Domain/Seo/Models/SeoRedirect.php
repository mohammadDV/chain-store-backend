<?php

namespace Domain\Seo\Models;

use Domain\Seo\Services\SeoCacheService;
use Illuminate\Database\Eloquent\Model;

class SeoRedirect extends Model
{
    protected $guarded = [];

    protected $casts = [
        'status_code' => 'integer',
    ];

    protected static function booted(): void
    {
        $clear = static function (): void {
            app(SeoCacheService::class)->clearRedirects();
        };

        static::saved($clear);
        static::deleted($clear);
    }
}
