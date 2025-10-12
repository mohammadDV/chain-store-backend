<?php

namespace Domain\Product\Repositories\Contracts;

use Application\Api\Product\Requests\ProductRequest;
use Application\Api\Product\Requests\SearchProductRequest;
use Core\Http\Requests\TableRequest;
use Domain\Product\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface IProductRepository.
 */
interface IProductRepository
{
    /**
     * Get the products pagination.
     * @param TableRequest $request
     * @return LengthAwarePaginator
     */
    public function index(TableRequest $request) :LengthAwarePaginator;

    /**
     * Edit the product.
     * @param Product $product
     * @return Product
     */
    public function edit(Product $product) :Product;

    /**
     * Get the product.
     * @param Product $product
     * @return array
     */
    public function show(Product $product) :array;

    /**
     * Store the product.
     * @param ProductRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function store(ProductRequest $request) :JsonResponse;

    /**
     * Update the product.
     * @param ProductRequest $request
     * @param Product $product
     * @return JsonResponse
     * @throws \Exception
     */
    public function update(ProductRequest $request, Product $product) :JsonResponse;

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
     * @return array{sender: Collection, passenger: Collection}
     */
    public function getFeaturedProducts(): array;

    /**
     * Get weekends.
     * @return array
     */
    public function getWeekends(): array;

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
