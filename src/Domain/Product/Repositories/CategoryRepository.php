<?php

namespace Domain\Product\Repositories;

use Application\Api\Product\Requests\ProductCategoryRequest;
use Application\Api\Product\Resources\CategoryResource;
use Application\Api\Product\Resources\FilterResource;
use Application\Api\Product\Resources\ServiceResource;
use Core\Http\Requests\TableRequest;
use Core\Http\traits\GlobalFunc;
use Domain\Brand\Models\Brand;
use Domain\Product\Models\Category;
use Domain\Product\Models\Service;
use Domain\Product\Repositories\Contracts\ICategoryRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Class CategoryRepository.
 */
class CategoryRepository implements ICategoryRepository
{
    use GlobalFunc;

    /**
     * Get the productCategories pagination.
     * @param TableRequest $request
     * @return LengthAwarePaginator
     */
    public function index(TableRequest $request) :LengthAwarePaginator
    {
        $search = $request->get('query');
        return Category::query()
            ->when(Auth::user()->level != 3, function ($query) {
                return $query->where('user_id', Auth::user()->id);
            })
            ->when(!empty($search), function ($query) use ($search) {
                return $query->where('title', 'like', '%' . $search . '%');
            })
            ->orderBy($request->get('column', 'id'), $request->get('sort', 'desc'))
            ->paginate($request->get('count', 25));
    }

    /**
     * Get the productCategories.
     * @param Brand $brand
     * @return Collection
     */
    public function activeProductCategories(Brand $brand)
    {
        $categories = Category::query()
            ->select('id', 'title', 'image')
            ->where('brand_id', $brand->id)
            ->where('parent_id', 0)
            ->where('status', 1)
            ->orderBy('priority', 'desc')
            ->get();


        return CategoryResource::collection($categories);
    }

    /**
     * Get the productCategories with all nested children recursively.
     * @param Brand $brand
     * @return Collection
     */
    public function allCategories(Brand $brand)
    {
        $categories = Category::query()
            ->select('id', 'title', 'image', 'status', 'parent_id', 'priority')
            ->with('childrenRecursive') // Load all nested children recursively
            ->where('brand_id', $brand->id)
            ->where('parent_id', 0)
            ->where('status', 1)
            ->orderBy('priority', 'desc')
            ->get();

        return CategoryResource::collection($categories);
    }

    /**
     * Get the children of a specific category.
     * @param Brand $brand
     * @return Collection|AnonymousResourceCollection
     */
    public function getCategoryChildren(Category $category)
    {
        $categories = Category::query()
            ->select('id', 'title', 'image', 'status', 'parent_id', 'priority')
            ->with('childrenRecursive')
            ->where('parent_id', $category->id)
            ->where('status', 1)
            ->orderBy('priority', 'desc')
            ->get();

        return CategoryResource::collection($categories);
    }

    /**
     * Get the Category.
     * @param Category $category
     * @return Category
     */
    public function show(Category $category) :Category
    {
        return Category::query()
                ->where('id', $category->id)
                ->first();
    }
}
