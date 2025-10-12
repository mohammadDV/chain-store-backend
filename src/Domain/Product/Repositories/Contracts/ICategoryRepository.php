<?php

namespace Domain\Product\Repositories\Contracts;

use Application\Api\Product\Requests\ProductCategoryRequest;
use Core\Http\Requests\TableRequest;
use Domain\Product\Models\ProductCategory;
use Domain\Product\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface ICategoryRepository.
 */
interface ICategoryRepository
{
    /**
     * Get the productCategories pagination.
     * @param TableRequest $request
     * @return LengthAwarePaginator
     */
    public function index(TableRequest $request) :LengthAwarePaginator;

    /**
     * Get the productCategories.
     * @return Collection
     */
    public function activeProductCategories() :Collection;

    /**
     * Get the productCategories.
     */
    public function allCategories();

    /**
     * Get the children of a specific category.
     * @param Category $category
     * @return Collection
     */
    public function getCategoryChildren(Category $category);

    /**
     * Get all parent categories.
     * @return Collection
     */
    public function getParentCategories();

    /**
     * Get the Category.
     * @param Category $category
     * @return Category
     */
    public function show(Category $category);

    /**
     * Get the Category with parent hierarchy.
     * @param Category $category
     * @return Category
     */
    public function showWithParents(Category $category);

    /**
     * Get the filters associated with a specific category.
     * @param TableRequest $request
     * @param Category $category
     * @return LengthAwarePaginator
     */
    public function getCategoryFilters(TableRequest $request, Category $category) :LengthAwarePaginator;

    /**
     * Get the services associated with a specific category.
     * @param TableRequest $request
     * @param Category $category
     * @return LengthAwarePaginator
     */
    public function getCategoryServices(TableRequest $request, Category $category) :LengthAwarePaginator;

    /**
     * Store the productCategory.
     * @param ProductCategoryRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    // public function store(ProductCategoryRequest $request) :JsonResponse;

    /**
     * Update the productCategory.
     * @param ProductCategoryRequest $request
     * @param ProductCategory $productCategory
     * @return JsonResponse
     * @throws \Exception
     */
    // public function update(ProductCategoryRequest $request, ProductCategory $productCategory) :JsonResponse;

    /**
    * Delete the productCategory.
    * @param UpdatePasswordRequest $request
    * @param ProductCategory $productCategory
    * @return JsonResponse
    */
//    public function destroy(ProductCategory $productCategory) :JsonResponse;
}
