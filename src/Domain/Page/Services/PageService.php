<?php

namespace Domain\Page\Services;

use Domain\Page\Models\Page;
use Illuminate\Support\Facades\Cache;

class PageService
{
    public const NAV_CACHE_TAG = 'pages-nav';

    public static function cacheTagForSlug(string $slug): string
    {
        return 'page-'.$slug;
    }

    private static function cacheKeyForSlug(string $slug): string
    {
        return 'page:'.$slug;
    }

    private const NAV_CACHE_KEY = 'pages:navigation';

    /**
     * Active pages for footer/header navigation.
     *
     * @return list<array{slug: string, title: string, show_in_menu: bool, sort_order: int}>
     */
    public function getNavigation(): array
    {
        return Cache::rememberForever(self::NAV_CACHE_KEY, function () {
            return Page::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['slug', 'title', 'show_in_menu', 'sort_order'])
                ->map(fn (Page $page) => [
                    'slug' => $page->slug,
                    'title' => $page->title,
                    'show_in_menu' => (bool) $page->show_in_menu,
                    'sort_order' => (int) $page->sort_order,
                ])
                ->all();
        });
    }

    /**
     * Full active page payload by slug, or null when missing/inactive.
     *
     * @return array{
     *     slug: string,
     *     title: string,
     *     image: string|null,
     *     content: string|null,
     *     meta_title: string|null,
     *     meta_description: string|null
     * }|null
     */
    public function getBySlug(string $slug): ?array
    {
        return Cache::rememberForever(self::cacheKeyForSlug($slug), function () use ($slug) {
            $page = Page::query()
                ->active()
                ->where('slug', $slug)
                ->first();

            if (! $page) {
                return null;
            }

            return [
                'slug' => $page->slug,
                'title' => $page->title,
                'image' => $page->image,
                'content' => $page->content,
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
            ];
        });
    }

    public function clearCache(?string $slug = null): void
    {
        Cache::forget(self::NAV_CACHE_KEY);

        if ($slug !== null && $slug !== '') {
            Cache::forget(self::cacheKeyForSlug($slug));
        }
    }
}
