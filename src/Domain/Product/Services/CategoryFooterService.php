<?php

namespace Domain\Product\Services;

use Core\Support\FrontendCacheInvalidator;
use Domain\Product\Models\Category;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CategoryFooterService
{
    public const CACHE_KEY = 'categories:footer';

    public const CACHE_TAG = 'categories-footer';

    public const CACHE_TTL_SECONDS = 86400;

    /** Footer grid keeps pages + trust columns; remaining slots for category roots. */
    public const MAX_CATEGORY_COLUMNS = 3;

    public const MAX_LINKS_PER_COLUMN = 10;

    /**
     * Lightweight footer category columns (cached 24h).
     *
     * Single select of active categories only — no products/brands/recursive eager load.
     * Children titles are unique within each column (highest priority wins).
     *
     * @return list<array{
     *     title: string,
     *     slug: string|null,
     *     links: list<array{title: string, slug: string|null}>
     * }>
     */
    public function getColumns(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
            return $this->buildColumns($this->fetchActiveCategories());
        });
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @return list<array{title: string, slug: string|null, links: list<array{title: string, slug: string|null}>}>
     */
    public function buildColumns(Collection $categories): array
    {
        $roots = $categories
            ->filter(fn ($category) => (int) $category->parent_id === 0)
            ->sortByDesc(fn ($category) => (int) $category->priority)
            ->values()
            ->take(self::MAX_CATEGORY_COLUMNS);

        $childrenByParent = $categories
            ->filter(fn ($category) => (int) $category->parent_id !== 0)
            ->groupBy(fn ($category) => (int) $category->parent_id);

        $columns = [];

        foreach ($roots as $root) {
            /** @var Collection<int, Category> $children */
            $children = ($childrenByParent->get((int) $root->id) ?? collect())
                ->sortByDesc(fn ($category) => (int) $category->priority)
                ->values();

            $columns[] = [
                'title' => $root->title,
                'slug' => $root->slug,
                'links' => $this->uniqueTitleLinks($children),
            ];
        }

        return $columns;
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);

        // Avoid N HTTP calls while seeding/migrating in CLI.
        if (app()->runningInConsole()) {
            return;
        }

        app(FrontendCacheInvalidator::class)->revalidate([self::CACHE_TAG]);
    }

    /**
     * @return EloquentCollection<int, Category>
     */
    private function fetchActiveCategories(): EloquentCollection
    {
        return Category::query()
            ->select(['id', 'title', 'slug', 'parent_id', 'priority'])
            ->where('status', 1)
            ->orderByDesc('priority')
            ->get();
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @return list<array{title: string, slug: string|null}>
     */
    private function uniqueTitleLinks(Collection $categories): array
    {
        $links = [];
        $seenTitles = [];

        foreach ($categories as $category) {
            $key = mb_strtolower(trim($category->title));
            if ($key === '' || isset($seenTitles[$key])) {
                continue;
            }

            $seenTitles[$key] = true;
            $links[] = [
                'title' => $category->title,
                'slug' => $category->slug,
            ];

            if (count($links) >= self::MAX_LINKS_PER_COLUMN) {
                break;
            }
        }

        return $links;
    }
}
