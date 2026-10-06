<?php

namespace Domain\Product\Services;

use Domain\Brand\Models\Brand;
use Domain\Product\Models\Category;
use Illuminate\Support\Facades\Cache;

/**
 * Redis-backed caches for public category trees (mega-menu / SSR).
 */
class CategoryTreeCacheService
{
    public const TTL_SECONDS = 3600;

    public static function activeKey(?int $brandId): string
    {
        return 'categories:active:'.($brandId ?? 'all');
    }

    public static function allKey(?int $brandId): string
    {
        return 'categories:all:'.($brandId ?? 'all');
    }

    public static function childrenKey(int $categoryId): string
    {
        return 'categories:children:'.$categoryId;
    }

    public function clear(): void
    {
        Cache::forget(self::activeKey(null));
        Cache::forget(self::allKey(null));

        Brand::query()->pluck('id')->each(function ($id) {
            Cache::forget(self::activeKey((int) $id));
            Cache::forget(self::allKey((int) $id));
        });

        Category::query()->pluck('id')->each(function ($id) {
            Cache::forget(self::childrenKey((int) $id));
        });
    }
}
