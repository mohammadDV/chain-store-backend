<?php

namespace Domain\Page\Models;

use Core\Support\FrontendCacheInvalidator;
use Domain\Page\Services\PageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'image',
        'content',
        'is_active',
        'show_in_menu',
        'sort_order',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_in_menu' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::updating(function (Page $page) {
            if ($page->isDirty('slug')) {
                $original = $page->getOriginal('slug');
                if (is_string($original) && $original !== '') {
                    app(PageService::class)->clearCache($original);
                    app(FrontendCacheInvalidator::class)->revalidate([
                        PageService::cacheTagForSlug($original),
                    ]);
                }
            }
        });

        static::saved(function (Page $page) {
            app(PageService::class)->clearCache($page->slug);
            app(FrontendCacheInvalidator::class)->revalidate([
                PageService::NAV_CACHE_TAG,
                PageService::cacheTagForSlug($page->slug),
            ]);
        });

        static::deleted(function (Page $page) {
            app(PageService::class)->clearCache($page->slug);
            app(FrontendCacheInvalidator::class)->revalidate([
                PageService::NAV_CACHE_TAG,
                PageService::cacheTagForSlug($page->slug),
            ]);
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
