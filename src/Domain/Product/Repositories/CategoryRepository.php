<?php

namespace Domain\Product\Repositories;

use Application\Api\Product\Resources\CategoryResource;
use Core\Http\Requests\TableRequest;
use Core\Http\traits\GlobalFunc;
use Domain\Brand\Models\Brand;
use Domain\Product\Models\Category;
use Domain\Product\Repositories\Contracts\ICategoryRepository;
use Domain\Product\Services\CategoryTreeCacheService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * Class CategoryRepository.
 */
class CategoryRepository implements ICategoryRepository
{
    use GlobalFunc;

    /**
     * Get the productCategories pagination.
     */
    public function index(TableRequest $request): LengthAwarePaginator
    {
        $search = $request->get('query');
        $categories = Category::query()
            ->with(['brands', 'childrenRecursive', 'parentRecursive'])
            ->when(! empty($search), function ($query) use ($search) {
                return $query->where('title', 'like', '%'.$search.'%');
            })
            ->orderBy($request->get('column', 'id'), $request->get('sort', 'desc'))
            ->paginate($request->get('count', 25));

        return $categories->through(fn ($category) => new CategoryResource($category));
    }

    /**
     * Get the productCategories.
     */
    public function activeProductCategories(?Brand $brand = null): array
    {
        return Cache::remember(
            CategoryTreeCacheService::activeKey($brand?->id),
            CategoryTreeCacheService::TTL_SECONDS,
            function () {
                $categories = Category::query()
                    ->whereHas('products')
                    ->where('parent_id', 0)
                    ->where('status', 1)
                    ->orderBy('priority', 'desc')
                    ->get();

                return CategoryResource::collection($categories)->resolve();
            }
        );
    }

    /**
     * Get the productCategories with all nested children recursively.
     */
    public function allCategories(?Brand $brand = null): array
    {
        return Cache::remember(
            CategoryTreeCacheService::allKey($brand?->id),
            CategoryTreeCacheService::TTL_SECONDS,
            function () use ($brand) {
                $categories = Category::query()
                    ->select('id', 'title', 'slug', 'image', 'status', 'parent_id', 'priority')
                    ->with(['childrenRecursive'])
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

                return CategoryResource::collection($categories)->resolve();
            }
        );
    }

    /**
     * Get the children of a specific category.
     */
    public function getCategoryChildren(Category $category): array
    {
        return Cache::remember(
            CategoryTreeCacheService::childrenKey((int) $category->id),
            CategoryTreeCacheService::TTL_SECONDS,
            function () use ($category) {
                $categories = Category::query()
                    ->select('id', 'title', 'slug', 'image', 'status', 'parent_id', 'priority')
                    ->with(['childrenRecursive'])
                    ->where('parent_id', $category->id)
                    ->where('status', 1)
                    ->orderBy('priority', 'desc')
                    ->get();

                return CategoryResource::collection($categories)->resolve();
            }
        );
    }

    /**
     * Get the Category.
     *
     * @return CategoryResource
     */
    public function show(Category $category)
    {
        return new CategoryResource($category->load('childrenRecursive', 'parentRecursive'));
    }
}
