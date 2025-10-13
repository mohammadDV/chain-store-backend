<?php

namespace Domain\Product\Repositories\Contracts;

use Application\Api\Product\Requests\ProductCategoryRequest;
use Application\Api\Product\Resources\CategoryResource;
use Core\Http\Requests\TableRequest;
use Domain\Brand\Models\Brand;
use Domain\Product\Models\ProductCategory;
use Domain\Product\Models\Category;
use Google\Service\ChromeUXReport\Bin;
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
     * @param Brand $brand
     */
    public function activeProductCategories(Brand $brand);

    /**
     * Get the productCategories.
     * @param Brand $brand
     */
    public function allCategories(Brand $brand);

    /**
     * Get the children of a specific category.
     * @param Category $category
     * @return Collection
     */
    public function getCategoryChildren(Category $category);

    /**
     * Get the Category.
     * @param Category $category
     * @return Category
     */
    public function show(Category $category);

}
