<?php

namespace Application\Api\Product\Controllers;

use Core\Http\Controllers\Controller;
use Core\Http\Requests\TableRequest;
use Domain\Product\Models\Product;
use Domain\Product\Repositories\Contracts\IProductRepository;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Application\Api\Product\Requests\SearchProductRequest;


class ProductController extends Controller
{

    /**
     * @param IProductRepository $repository
     */
    public function __construct(protected IProductRepository $repository)
    {

    }

    /**
     * Get the product.
     * @param Product $product
     * @return JsonResponse
     */
    public function show(Product $product) :JsonResponse
    {
        return response()->json($this->repository->show($product), Response::HTTP_OK);
    }

    /**
     * Favorite the product.
     * @param Product $product
     * @return JsonResponse
     */
    public function favorite(Product $product) :JsonResponse
    {
        return $this->repository->favorite($product);
    }

    /**
     * Get favorite products.
     * @param TableRequest $request
     * @return JsonResponse
     */
    public function getFavoriteProducts(TableRequest $request): JsonResponse
    {
        return response()->json($this->repository->getFavoriteProducts($request), Response::HTTP_OK);
    }

    /**
     * Get similar products.
     * @param Product $product
     * @return JsonResponse
     */
    public function similarProducts(Product $product): JsonResponse
    {
        return response()->json($this->repository->similarProducts($product), Response::HTTP_OK);
    }

    /**
     * Get featured products by type.
     * @param TableRequest $request
     * @return JsonResponse
     */
    public function getFeaturedProducts(TableRequest $request): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'data' => $this->repository->getFeaturedProducts($request)
        ], Response::HTTP_OK);
    }

    /**
     * Search products with filters.
     * @param SearchProductRequest $request
     * @return JsonResponse
     */
    public function search(SearchProductRequest $request): JsonResponse
    {
        return response()->json($this->repository->search($request), Response::HTTP_OK);
    }

    /**
     * Search suggestions with filters.
     * @param TableRequest     $request
     * @return JsonResponse
     */
    public function searchSuggestions(TableRequest $request): JsonResponse
    {
        return response()->json($this->repository->searchSuggestions($request), Response::HTTP_OK);
    }
}