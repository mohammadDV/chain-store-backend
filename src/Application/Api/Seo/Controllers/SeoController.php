<?php

namespace Application\Api\Seo\Controllers;

use Core\Http\Controllers\Controller;
use Domain\Brand\Models\Brand;
use Domain\Post\Models\Post;
use Domain\Product\Models\Category;
use Domain\Product\Models\Product;
use Domain\Seo\Models\SeoRedirect;
use Domain\Setting\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function settings(SettingService $settingService): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'data' => $settingService->getSeoSettings(),
        ], Response::HTTP_OK);
    }

    public function sitemap(): JsonResponse
    {
        $products = Product::query()
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
            ]);

        $categories = Category::query()
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
            ]);

        $posts = Post::query()
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
            ]);

        $brands = Brand::query()
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
            ]);

        return response()->json([
            'status' => 1,
            'data' => [
                'products' => $products,
                'categories' => $categories,
                'posts' => $posts,
                'brands' => $brands,
            ],
        ], Response::HTTP_OK);
    }

    public function redirects(): JsonResponse
    {
        $redirects = SeoRedirect::query()
            ->orderBy('id')
            ->get(['from_path', 'to_path', 'status_code'])
            ->map(fn (SeoRedirect $redirect) => [
                'from_path' => $redirect->from_path,
                'to_path' => $redirect->to_path,
                'status_code' => $redirect->status_code,
            ]);

        return response()->json([
            'status' => 1,
            'data' => $redirects,
        ], Response::HTTP_OK);
    }
}
