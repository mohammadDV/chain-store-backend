<?php

namespace Application\Api\Product\Controllers;

use Application\Api\Product\Requests\SearchProductRequest;
use Core\Http\Controllers\Controller;
use Core\Http\Requests\TableRequest;
use Domain\Product\Models\Product;
use Domain\Product\Repositories\Contracts\IProductRepository;
use Domain\Product\Services\CartProductRefreshService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function __construct(protected IProductRepository $repository) {}

    /**
     * Get the product.
     */
    public function show(Product $product): JsonResponse
    {
        return response()->json($this->repository->show($product), Response::HTTP_OK);
    }

    /**
     * Queue a stale-product scrape when the product is added to the cart.
     * Fire-and-forget from the client; eligibility is checked here.
     */
    public function refreshOnCart(Product $product, CartProductRefreshService $cartRefresh): JsonResponse
    {
        $queued = $cartRefresh->dispatchIfEligible($product);

        return response()->json([
            'status' => 1,
            'queued' => $queued,
        ], Response::HTTP_OK);
    }

    /**
     * Favorite the product.
     */
    public function favorite(Product $product): JsonResponse
    {
        return $this->repository->favorite($product);
    }

    /**
     * Get favorite products.
     */
    public function getFavoriteProducts(TableRequest $request): JsonResponse
    {
        return response()->json($this->repository->getFavoriteProducts($request), Response::HTTP_OK);
    }

    /**
     * Get similar products.
     */
    public function similarProducts(Product $product): JsonResponse
    {
        return response()->json($this->repository->similarProducts($product), Response::HTTP_OK);
    }

    /**
     * Get featured products by type.
     */
    public function getFeaturedProducts(TableRequest $request): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'data' => $this->repository->getFeaturedProducts($request),
        ], Response::HTTP_OK);
    }

    /**
     * Search products with filters.
     */
    public function search(SearchProductRequest $request): JsonResponse
    {
        return response()->json($this->repository->search($request), Response::HTTP_OK);
    }

    /**
     * Search suggestions with filters.
     */
    public function searchSuggestions(TableRequest $request): JsonResponse
    {
        return response()->json($this->repository->searchSuggestions($request), Response::HTTP_OK);
    }
}
