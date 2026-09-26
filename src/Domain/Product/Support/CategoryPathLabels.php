<?php

namespace Domain\Product\Support;

use Domain\Product\Models\Category;
use Illuminate\Support\Collection;

/**
 * Builds full category breadcrumb labels from a single flat query.
 * Memoized per request via once() to avoid N+1 and repeated loads.
 */
final class CategoryPathLabels
{
    public const SEPARATOR = ' › ';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return once(static function (): array {
            /** @var Collection<int, Category> $categories */
            $categories = Category::query()
                ->orderBy('title')
                ->get(['id', 'title', 'parent_id']);

            $byId = $categories->keyBy('id');
            $labels = [];

            foreach ($categories as $category) {
                $labels[(int) $category->id] = self::buildPath($category, $byId);
            }

            return $labels;
        });
    }

    public static function forId(int $id): string
    {
        return self::all()[$id] ?? (string) $id;
    }

    public static function for(Category $category): string
    {
        return self::all()[(int) $category->id] ?? $category->title;
    }

    /**
     * Options map suitable for Filament Select::options().
     *
     * @return array<int, string>
     */
    public static function options(): array
    {
        return self::all();
    }

    /**
     * @param  Collection<int, Category>  $byId
     */
    private static function buildPath(Category $category, Collection $byId): string
    {
        $parts = [];
        $current = $category;
        $guard = 0;

        while ($current !== null && $guard < 50) {
            array_unshift($parts, $current->title);

            $parentId = (int) ($current->parent_id ?? 0);
            if ($parentId === 0) {
                break;
            }

            $current = $byId->get($parentId);
            $guard++;
        }

        return implode(self::SEPARATOR, $parts);
    }
}
