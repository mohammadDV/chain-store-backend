<?php

namespace Domain\Product\Repositories;

use Application\Api\Product\Resources\ProductResource;
use Core\Http\Requests\TableRequest;
use Core\Http\traits\GlobalFunc;
use Domain\Product\Models\Product;
use Domain\Product\Repositories\Contracts\IProductRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Application\Api\Product\Requests\SearchProductRequest;
use Application\Api\Product\Resources\CategoryResource;
use Application\Api\Product\Resources\ProductBoxResource;
use Domain\Product\Models\Category;
use Domain\Product\Models\Favorite;
use Domain\Review\Models\Review;
use Domain\User\Services\TelegramNotificationService;
use Illuminate\Support\Collection;
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
     * @param Product $product
     * @return array
     */
    public function show(Product $product) :array
    {
        $product = Product::query()
                ->with([
                    'category.parentRecursive',
                    'files',
                    'color',
                    'brand',
                    'sizes'
                ])
                ->where('id', $product->id)
                ->first();



        $reviews = $this->getReviewsByRate($product->id);

        return [
            'product' => new ProductResource($product),
            'reviews' => $reviews,
        ];

    }


    /**
     * Favorite the product.
     * @param Product $product
     * @return JsonResponse
     */
    public function favorite(Product $product) :JsonResponse
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
     * @param TableRequest $request
     * @return LengthAwarePaginator
     */
    public function getFavoriteProducts(TableRequest $request): LengthAwarePaginator
    {
        $search = $request->get('query');
        $products = Product::query()
            ->select('id', 'title', 'amount', 'discount', 'rate', 'order_count', 'view_count', 'image')
            ->whereHas('favorites', function ($query) {
                $query->where('favorites.user_id', Auth::user()->id);
            })
            ->when(!empty($search), function ($query) use ($search) {
                return $query->where('title', 'like', '%' . $search . '%');
            })
            ->orderBy($request->get('column', 'id'), $request->get('sort', 'desc'))
            ->paginate($request->get('count', 25));

        return $products->through(fn ($product) => new ProductBoxResource($product));
    }

        /**
     * Get featured products by type with configurable limits.
     * @param TableRequest $request
     * @return Collection
     */
    public function getFeaturedProducts(TableRequest $request): Collection
    {
        $column = $request->get('column', 'id');
        $brand = $request->get('brand');

        match ($column) {
            'rate' => $column = 'rate',
            'order' => $column = 'order_count',
            'view' => $column = 'view_count',
            'discount' => $column = 'discount',
            'reviews' => $column = 'reviews_count',
            'amount' => $column = 'amount',
            default => $column = 'id',
        };

        // var_dump($column);  exit;

        $products = Product::query()
            ->select('id', 'title', 'amount', 'discount', 'rate', 'order_count', 'view_count', 'image')
            ->withCount('reviews')
            ->when(!empty($brand), function ($query) use ($brand) {
                $query->where('brand_id', $brand);
            })
            ->where('active', 1)
            ->where('status', Product::COMPLETED)
            ->orderBy($column, $request->get('sort', 'desc'))
            ->limit(config('product.limit'))
            ->get()
            ->map(fn ($product) => new ProductBoxResource($product));

        return $products;
    }

    /**
     * Get similar products.
     * @param Product $product
     */
    public function similarProducts(Product $product)
    {

        $categories = $product?->category
            ?->parent
            ?->allChildren()
            ?->pluck('id')
            ?->toArray();

        $similarProducts = Product::query()
            ->with(['category'])
            ->where('active', 1)
            ->where('status', Product::COMPLETED)
            ->where(function ($query) use ($categories) {
                $query->whereIn('category_id', $categories);
            })
            ->where('id', '!=', $product->id)
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return $similarProducts->map(fn ($product) => new ProductBoxResource($product));
    }

    /**
     * Search suggestions with filters and pagination.
     * @param TableRequest $request
     */
    public function searchSuggestions(TableRequest $request)
    {

        $search = $request->get('query');

        $categories = Category::query()
            ->with('parentRecursive')
            ->where(function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%');
            })
            ->where('status', 1)
            ->limit(10)
            ->get();

        $queryProduct = Product::query()
            ->where('active', 1)
            ->where('status', Product::COMPLETED)
            ->where(function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('details', 'like', '%' . $search . '%');
            });

        $products = $queryProduct->orderBy($request->get('column', 'id'), $request->get('sort', 'desc'))
            ->limit(5)
            ->get();


        return [
            'products' => $products->map(fn ($product) => new ProductBoxResource($product)),
            'categories' => $categories->map(fn ($category) => new CategoryResource($category)),
        ];

    }

    /**
     * Search products with filters and pagination.
     * @param SearchProductRequest $request
     * @return LengthAwarePaginator
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

        // Generate a unique cache key based on all search parameters
        $cacheKey = 'product_search_' . md5(json_encode([
            'query' => $search,
            'category' => $categories,
            'brand' => $brands,
            'color' => $colors,
            'start_amount' => $startAmount,
            'end_amount' => $endAmount,
            'column' => $column,
            'page' => $request->input('page', 1),
        ]));

        // Try to get results from cache first
        // return cache()->remember($cacheKey, now()->addMinutes(5), function () use ($request, $today) {
            $query = Product::query()
                ->select('id', 'title', 'amount', 'discount', 'rate', 'order_count', 'view_count', 'image')
                ->withCount('reviews')
                ->where('active', 1)
                ->where('status', Product::COMPLETED);

            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->where('title','like', '%' . $search . '%')
                        ->orWhere('description','like', '%' . $search . '%')
                        ->orWhere('details','like', '%' . $search . '%');
                });
            }

            // start amount
            if (!empty($startAmount)) {
                $query->where('amount', '>=', $startAmount);
            }

            // end amount
            if (!empty($endAmount)) {
                $query->where('amount', '<=', $endAmount);
            }

            // brand
            if (!empty($brands)) {
                $query->whereIn('brand_id', $brands);
            }

            // color
            if (!empty($colors)) {
                $query->whereHas('color', function ($q) use ($colors) {
                    $q->whereIn('id', $colors);
                });
            }

            // category
            if (!empty($categories)) {
                $query->whereHas('category', function ($q) use ($categories) {
                    $q->whereIn('id', $categories)
                      ->orWhereIn('parent_id', $categories);
                });
            }

            $products = $query->orderBy($column, $request->get('sort', 'desc'))
                ->paginate($request->get('count', 25));

            return $products->through(fn ($product) => new ProductBoxResource($product));
        // });
    }

    /**
     * Get reviews grouped by rate with counts for products
     * @param int|null $productId Optional product ID to filter by specific product
     * @return Collection
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
            5 => __('site.excellent')
        ];

        return $query->groupBy('rate')
            ->orderBy('rate', 'desc')
            ->get()
            ->map(function ($item) use ($titles) {
                return [
                    'title' => $titles[$item->rate],
                    'rate' => $item->rate,
                    'count' => $item->count,
                    'percentage' => 0 // Will be calculated below
                ];
            })
            ->map(function ($item, $index) use ($productId) {
                // Calculate percentage based on total reviews
                $totalReviews = Review::query()
                    ->where('status', 1)
                    ->when($productId ?? false, function ($query) use ($productId) {
                        return $query->where('product_id', $productId);
                    })
                    ->count();

                $item['percentage'] = $totalReviews > 0 ? round(($item['count'] / $totalReviews) * 100, 2) : 0;
                return $item;
            });
    }
}
