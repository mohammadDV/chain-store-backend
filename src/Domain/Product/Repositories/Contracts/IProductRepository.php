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
     */
    public function show(Product $product): array;

    /**
     * Favorite the product.
     */
    public function favorite(Product $product): JsonResponse;

    /**
     * Get favorite products.
     */
    public function getFavoriteProducts(TableRequest $request): LengthAwarePaginator;

    /**
     * Get similar products.
     */
    public function similarProducts(Product $product);

    /**
     * Get featured products by type with configurable limits.
     */
    public function getFeaturedProducts(TableRequest $request): Collection;

    /**
     * Search products with filters and pagination.
     */
    public function search(SearchProductRequest $request): LengthAwarePaginator;

    /**
     * Search suggestions with filters and pagination.
     */
    public function searchSuggestions(TableRequest $request);
}
