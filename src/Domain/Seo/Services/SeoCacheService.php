<?php

namespace Domain\Seo\Services;

use Domain\Brand\Models\Brand;
use Domain\Post\Models\Post;
use Domain\Product\Models\Category;
use Domain\Product\Models\Product;
use Domain\Seo\Models\SeoRedirect;
use Illuminate\Support\Facades\Cache;

class SeoCacheService
{
    public const SITEMAP_CACHE_KEY = 'seo:sitemap';

    public const REDIRECTS_CACHE_KEY = 'seo:redirects';

    public const SITEMAP_TTL_SECONDS = 3600;

    public const REDIRECTS_TTL_SECONDS = 3600;

    /**
     * @return array{products: list<array<string, mixed>>, categories: list<array<string, mixed>>, posts: list<array<string, mixed>>, brands: list<array<string, mixed>>}
     */
    public function getSitemap(): array
    {
        return Cache::remember(self::SITEMAP_CACHE_KEY, self::SITEMAP_TTL_SECONDS, function () {
            return [
                'products' => Product::query()
                    ->active()
                    ->whereNotNull('slug')
                    ->where('slug', '!=', '')
                    ->orderBy('id')
                    ->get(['slug', 'updated_at'])
                    ->map(fn (Product $product) => [
                        'type' => 'product',
                        'path' => '/product/'.$product->slug,
                        'slug' => $product->slug,
                        'updated_at' => optional($product->updated_at)?->toAtomString(),
                    ])
                    ->all(),
                'categories' => Category::query()
                    ->where('status', 1)
                    ->whereNotNull('slug')
                    ->where('slug', '!=', '')
                    ->orderBy('id')
                    ->get(['slug', 'updated_at'])
                    ->map(fn (Category $category) => [
                        'type' => 'category',
                        'path' => '/shop/'.$category->slug,
                        'slug' => $category->slug,
                        'updated_at' => optional($category->updated_at)?->toAtomString(),
                    ])
                    ->all(),
                'posts' => Post::query()
                    ->where('status', 1)
                    ->whereNotNull('slug')
                    ->where('slug', '!=', '')
                    ->orderBy('id')
                    ->get(['slug', 'updated_at'])
                    ->map(fn (Post $post) => [
                        'type' => 'post',
                        'path' => '/post/'.$post->slug,
                        'slug' => $post->slug,
                        'updated_at' => optional($post->updated_at)?->toAtomString(),
                    ])
                    ->all(),
                'brands' => Brand::query()
                    ->where('status', 1)
                    ->whereNotNull('slug')
                    ->where('slug', '!=', '')
                    ->orderBy('id')
                    ->get(['slug', 'updated_at'])
                    ->map(fn (Brand $brand) => [
                        'type' => 'brand',
                        'path' => '/brand/'.$brand->slug,
                        'slug' => $brand->slug,
                        'updated_at' => optional($brand->updated_at)?->toAtomString(),
                    ])
                    ->all(),
            ];
        });
    }

    /**
     * @return list<array{from_path: string, to_path: string, status_code: int}>
     */
    public function getRedirects(): array
    {
        return Cache::remember(self::REDIRECTS_CACHE_KEY, self::REDIRECTS_TTL_SECONDS, function () {
            return SeoRedirect::query()
                ->orderBy('id')
                ->get(['from_path', 'to_path', 'status_code'])
                ->map(fn (SeoRedirect $redirect) => [
                    'from_path' => $redirect->from_path,
                    'to_path' => $redirect->to_path,
                    'status_code' => $redirect->status_code,
                ])
                ->all();
        });
    }

    public function clearSitemap(): void
    {
        Cache::forget(self::SITEMAP_CACHE_KEY);
    }

    public function clearRedirects(): void
    {
        Cache::forget(self::REDIRECTS_CACHE_KEY);
    }

    public function clearAll(): void
    {
        $this->clearSitemap();
        $this->clearRedirects();
    }
}
