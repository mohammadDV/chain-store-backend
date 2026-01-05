<?php

namespace Domain\Product\Repositories;

use Application\Api\Brand\Resources\BrandResource;
use Application\Api\Product\Resources\CategoryResource;
use Core\Http\Requests\TableRequest;
use Core\Http\traits\GlobalFunc;
use Domain\Brand\Models\Brand;
use Domain\Product\Models\Category;
use Domain\Product\Models\Product;
use Domain\Product\Repositories\Contracts\ICategoryRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
        $categories = Category::query()
            ->with(['brand', 'childrenRecursive', 'parentRecursive'])
            ->when(!empty($search), function ($query) use ($search) {
                return $query->where('title', 'like', '%' . $search . '%');
            })
            ->orderBy($request->get('column', 'id'), $request->get('sort', 'desc'))
            ->paginate($request->get('count', 25));

        return $categories->through(fn ($category) => new CategoryResource($category));
    }

    /**
     * Get the productCategories.
     * @param Brand|null $brand
     * @return Collection
     */
    public function activeProductCategories(?Brand $brand = null)
    {
        $categories = Category::query()
            // ->select('id', 'title', 'image')
            // ->when($brand->id, function ($query) use ($brand) {
            //     return $query->whereHas('products');
                // return $query->whereHas('products', function ($subquery) use ($brand) {
                    // $query->where('brand_id', $brand->id);
                    // return $subquery->where('products.is_failed', 0);
                        // ->where('status', Product::COMPLETED)
                        // ->where('is_failed', 0);
                // });
                // $query->whereHas('brands', function ($query) use ($brand) {
                //     $query->where('brand_id', $brand->id)
                //         ->where('brand_category.status', 1);
                // });
            // })
            ->whereHas('products')
            ->where('parent_id', 0)
            ->where('status', 1)
            ->orderBy('priority', 'desc')
            ->get();


        return CategoryResource::collection($categories);
    }

    /**
     * Get the productCategories with all nested children recursively.
     * @param Brand|null $brand
     * @return Collection
     */
    public function allCategories(?Brand $brand = null)
    {
        $categories = Category::query()
            ->select('id', 'title', 'image', 'status', 'parent_id', 'priority')
            ->with(['childrenRecursive']) // Load all nested children recursively
            ->when($brand, function ($query) use ($brand) {
                $query->whereHas('brands', function ($query) use ($brand) {
                    $query->where('brand_id', $brand->id)
                        ->where('brand_category.status', 1);
                });
            })
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
            ->with(['childrenRecursive'])
            ->where('parent_id', $category->id)
            ->where('status', 1)
            ->orderBy('priority', 'desc')
            ->get();

        return CategoryResource::collection($categories);
    }

    /**
     * Get the Category.
     * @param Category $category
     * @return CategoryResource
     */
    public function show(Category $category)
    {
        return new CategoryResource($category->load('childrenRecursive', 'parentRecursive'));
    }
}
