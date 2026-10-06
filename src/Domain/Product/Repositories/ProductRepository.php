<?php

namespace Domain\Product\Repositories;

use Application\Api\Product\Requests\SearchProductRequest;
use Application\Api\Product\Resources\CategoryResource;
use Application\Api\Product\Resources\ProductBoxResource;
use Application\Api\Product\Resources\ProductFavoriteResource;
use Application\Api\Product\Resources\ProductResource;
use Core\Http\Requests\TableRequest;
use Core\Http\traits\GlobalFunc;
use Domain\Product\Models\Category;
use Domain\Product\Models\Favorite;
use Domain\Product\Models\Product;
use Domain\Product\Repositories\Contracts\IProductRepository;
use Domain\Review\Models\Review;
use Domain\User\Services\TelegramNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Class ProductRepository.
 */
class ProductRepository implements IProductRepository
{
    use GlobalFunc;

    public function __construct(protected TelegramNotificationService $service)
    {
        //
    }

    /**
     * Get the product.
     */
    public function show(Product $product): array
    {
        $product = Product::query()
            ->with([
                'categories.parentRecursive',
                'files',
                'attributes',
                'color',
                'brand',
                'sizes.stock',
            ])
            ->where('id', $product->id)
            ->active()
            ->first();

        if (! $product) {
            abort(404);
        }

        $reviews = $this->getReviewsByRate($product->id);

        $relatedProducts = is_array($product->related_products)
            ? $product->related_products
            : [];
        $relatedProducts = Product::query()
            ->active()
            ->whereIn('url', ! empty($relatedProducts) ? $relatedProducts : [])
            ->get()
            ->map(fn ($product) => new ProductResource($product));

        return [
            'product' => new ProductResource($product),
            'reviews' => $reviews,
            'related_products' => $relatedProducts,
        ];

    }

    /**
     * Favorite the product.
     */
    public function favorite(Product $product): JsonResponse
    {
        $favorite = Favorite::query()
            ->where('product_id', $product->id)
            ->where('user_id', Auth::user()->id)
            ->first();

        $active = 0;

        if ($favorite) {
            $favorite->delete();
        } else {
            $favorite = Favorite::create([
                'product_id' => $product->id,
                'user_id' => Auth::user()->id,
            ]);
            $active = 1;
        }

        return response()->json([
            'status' => 1,
            'message' => __('site.The operation has been successfully'),
            'favorite' => $active,
        ], Response::HTTP_OK);
    }

    /**
     * Get favorite products.
     */
    public function getFavoriteProducts(TableRequest $request): LengthAwarePaginator
    {
        $search = $request->get('query');
        $products = Product::query()
            ->select('id', 'title', 'slug', 'amount', 'discount', 'rate', 'order_count', 'view_count', 'image')
            ->whereHas('favorites', function ($query) {
                $query->where('favorites.user_id', Auth::user()->id);
            })
            ->when(! empty($search), function ($query) use ($search) {
                return $query->where('title', 'like', '%'.$search.'%');
            })
            ->active()
            ->orderBy($request->get('column', 'id'), $request->get('sort', 'desc'))
            ->paginate($request->get('count', 25));

        return $products->through(fn ($product) => new ProductFavoriteResource($product));
    }

    /**
     * Get featured products by type with configurable limits.
     */
    public function getFeaturedProducts(TableRequest $request): Collection
    {
        $column = $request->get('column', 'id');
        $brand = $request->get('brand');

        $isRandom = false;
        match ($column) {
            'rate' => $column = 'rate',
            'order' => $column = 'order_count',
            'view' => $column = 'view_count',
            'discount' => $column = 'discount',
            'reviews' => $column = 'reviews_count',
            'amount' => $column = 'amount',
            'vip' => $column = 'vip',
            'random' => $isRandom = true,
            default => $column = 'id',
        };

        $cacheKey = 'products:featured:'.md5(json_encode([
            'column' => $column,
            'brand' => $brand,
            'random' => $isRandom,
            'sort' => $request->get('sort', 'desc'),
        ]));

        // Random featured lists stay short-lived so results still rotate.
        $ttl = $isRandom ? 60 : 300;

        $payload = Cache::remember($cacheKey, $ttl, function () use ($column, $brand, $isRandom, $request) {
            return Product::query()
                ->select('id', 'title', 'slug', 'amount', 'discount', 'rate', 'order_count', 'view_count', 'image')
                ->withCount('reviews')
                ->when(! empty($brand), function ($query) use ($brand) {
                    $query->where('brand_id', $brand);
                })
                ->active()
                ->when($isRandom, function ($query) {
                    $query->inRandomOrder();
                }, function ($query) use ($column, $request) {
                    $query->orderBy($column, $request->get('sort', 'desc'));
                })
                ->limit(config('product.limit'))
                ->get()
                ->map(fn ($product) => (new ProductBoxResource($product))->resolve())
                ->all();
        });

        return collect($payload);
    }

    /**
     * Get similar products.
     */
    public function similarProducts(Product $product)
    {

        $categories = Category::query()
            ->whereIn('id', $product->categories->pluck('id'))
            ->pluck('id')
            ->toArray();

        $similarProducts = Product::query()
            ->active()
            ->with(['categories'])
            ->where(function ($query) use ($categories) {
                $query->whereHas('categories', function ($query) use ($categories) {
                    $query->whereIn('categories.id', $categories)
                        ->orWhereIn('categories.parent_id', $categories);
                });
            })
            ->where('id', '!=', $product->id)
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return $similarProducts->map(fn ($product) => new ProductBoxResource($product));
    }

    /**
     * Search suggestions with filters and pagination.
     */
    public function searchSuggestions(TableRequest $request)
    {

        $search = $request->get('query');
        $cacheKey = 'product_search_suggestions_'.md5((string) $search);

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($request, $search) {
            $categories = Category::query()
                ->with('parentRecursive')
                ->where(function ($query) use ($search) {
                    $query->where('title', 'like', '%'.$search.'%');
                })
                ->where('status', 1)
                ->limit(10)
                ->get();

            $queryProduct = Product::query()
                ->active()
                ->where(function ($query) use ($search) {
                    $query->where('title', 'like', '%'.$search.'%');
                });

            $products = $queryProduct->orderBy($request->get('column', 'id'), $request->get('sort', 'desc'))
                ->limit(5)
                ->get();

            return [
                'products' => $products->map(fn ($product) => (new ProductBoxResource($product))->resolve())->all(),
                'categories' => $categories->map(fn ($category) => (new CategoryResource($category))->resolve())->all(),
            ];
        });

    }

    /**
     * Search products with filters and pagination.
     */
    public function search(SearchProductRequest $request): LengthAwarePaginator
    {

        $search = $request->get('query');
        $categories = $request->get('categories');
        $brands = $request->get('brands');
        $colors = $request->get('colors');
        $startAmount = $request->get('start_amount');
        $endAmount = $request->get('end_amount');

        $column = $request->get('column', 'id');

        match ($column) {
            'rate' => $column = 'rate',
            'order' => $column = 'order_count',
            'view' => $column = 'view_count',
            'discount' => $column = 'discount',
            'reviews' => $column = 'reviews_count',
            'amount' => $column = 'amount',
            default => $column = 'id',
        };

        $cacheKey = 'product_search_'.md5(json_encode([
            'query' => $search,
            'category' => $categories,
            'brand' => $brands,
            'color' => $colors,
            'start_amount' => $startAmount,
            'end_amount' => $endAmount,
            'column' => $column,
            'sort' => $request->get('sort', 'desc'),
            'count' => $request->get('count', 25),
            'page' => $request->input('page', 1),
        ]));

        $cached = Cache::remember($cacheKey, now()->addMinutes(5), function () use (
            $request,
            $search,
            $categories,
            $brands,
            $colors,
            $startAmount,
            $endAmount,
            $column,
        ) {
            $query = Product::query()
                ->select('id', 'title', 'slug', 'amount', 'discount', 'rate', 'order_count', 'view_count', 'image')
                ->withCount('reviews')
                ->active();

            // Title-only LIKE — avoid scanning description/details under load.
            if (! empty($search)) {
                $query->where('title', 'like', '%'.$search.'%');
            }

            if (! empty($startAmount)) {
                $query->where('amount', '>=', $startAmount);
            }

            if (! empty($endAmount)) {
                $query->where('amount', '<=', $endAmount);
            }

            if (! empty($brands)) {
                $query->whereIn('brand_id', $brands);
            }

            if (! empty($colors)) {
                $query->whereHas('color', function ($q) use ($colors) {
                    $q->whereIn('id', $colors);
                });
            }

            if (! empty($categories)) {
                $parents = Category::query()
                    ->whereIn('parent_id', $categories)
                    ->pluck('id')
                    ->toArray();

                $categories = array_unique(array_merge($categories, $parents));

                $query->whereHas('categories', function ($q) use ($categories) {
                    $q->whereIn('categories.id', $categories)
                        ->orWhereIn('categories.parent_id', $categories);
                });
            }

            $products = $query->orderBy($column, $request->get('sort', 'desc'))
                ->paginate($request->get('count', 25));

            return [
                'data' => $products->getCollection()
                    ->map(fn ($product) => (new ProductBoxResource($product))->resolve())
                    ->values()
                    ->all(),
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'path' => $products->path(),
            ];
        });

        return new LengthAwarePaginator(
            $cached['data'],
            $cached['total'],
            $cached['per_page'],
            $cached['current_page'],
            ['path' => $cached['path'] ?? $request->url()]
        );
    }

    /**
     * Get reviews grouped by rate with counts for products
     *
     * @param  int|null  $productId  Optional product ID to filter by specific product
     */
    public function getReviewsByRate(?int $productId = null): Collection
    {
        $query = Review::query()
            ->select('rate', DB::raw('COUNT(*) as count'))
            ->where('active', 1) // Only approved reviews
            ->where('status', Review::APPROVED); // Only approved reviews

        if ($productId) {
            $query->where('product_id', $productId);
        }

        $titles = [
            1 => __('site.very_bad'),
            2 => __('site.bad'),
            3 => __('site.average'),
            4 => __('site.good'),
            5 => __('site.excellent'),
        ];

        // Calculate total reviews once (matching the same filters as the grouped query)
        $totalReviews = Review::query()
            ->where('active', 1)
            ->where('status', Review::APPROVED)
            ->when($productId ?? false, function ($query) use ($productId) {
                return $query->where('product_id', $productId);
            })
            ->count();

        return $query->groupBy('rate')
            ->orderBy('rate', 'desc')
            ->get()
            ->map(function ($item) use ($titles, $totalReviews) {
                return [
                    'title' => $titles[$item->rate],
                    'rate' => $item->rate,
                    'count' => $item->count,
                    'percentage' => $totalReviews > 0 ? round(($item->count / $totalReviews) * 100, 2) : 0,
                ];
            });
    }
}
