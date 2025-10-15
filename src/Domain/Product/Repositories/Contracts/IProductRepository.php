<?php

namespace Domain\Product\Repositories\Contracts;

use Application\Api\Product\Requests\SearchProductRequest;
use Core\Http\Requests\TableRequest;
use Domain\Product\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface IProductRepository.
 */
interface IProductRepository
{

    /**
     * Get the product.
     * @param Product $product
     * @return array
     */
    public function show(Product $product) :array;

    /**
     * Favorite the product.
     * @param Product $product
     * @return JsonResponse
     */
    public function favorite(Product $product) :JsonResponse;

    /**
     * Get favorite products.
     * @param TableRequest $request
     * @return LengthAwarePaginator
     */
    public function getFavoriteProducts(TableRequest $request): LengthAwarePaginator;

    /**
     * Get similar products.
     * @param Product $product
     */
    public function similarProducts(Product $product);

    /**
     * Get featured products by type with configurable limits.
     * @param TableRequest $request
     * @return Collection
     */
    public function getFeaturedProducts(TableRequest $request): Collection;

    /**
     * Search products with filters and pagination.
     * @param SearchProductRequest $request
     * @return LengthAwarePaginator
     */
    public function search(SearchProductRequest $request): LengthAwarePaginator;

    /**
     * Search suggestions with filters and pagination.
     * @param TableRequest $request
     */
    public function searchSuggestions(TableRequest $request);
}