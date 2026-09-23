<?php

namespace Domain\Product\Repositories\Contracts;

use Application\Api\Product\Resources\CategoryResource;
use Core\Http\Requests\TableRequest;
use Domain\Brand\Models\Brand;
use Domain\Product\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface ICategoryRepository.
 */
interface ICategoryRepository
{
    /**
     * Get the productCategories pagination.
     */
    public function index(TableRequest $request): LengthAwarePaginator;

    /**
     * Get the productCategories.
     */
    public function activeProductCategories(?Brand $brand = null);

    /**
     * Get the productCategories.
     */
    public function allCategories(?Brand $brand = null);

    /**
     * Get the children of a specific category.
     *
     * @return Collection
     */
    public function getCategoryChildren(Category $category);

    /**
     * Get the Category.
     *
     * @return CategoryResource
     */
    public function show(Category $category);
}
