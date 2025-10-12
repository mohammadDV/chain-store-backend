<?php

namespace Domain\Product\Repositories;

use Application\Api\Product\Requests\ProductCategoryRequest;
use Application\Api\Product\Resources\CategoryResource;
use Application\Api\Product\Resources\FilterResource;
use Application\Api\Product\Resources\ServiceResource;
use Core\Http\Requests\TableRequest;
use Core\Http\traits\GlobalFunc;
use Domain\Product\Models\Category;
use Domain\Product\Models\Service;
use Domain\Product\Repositories\Contracts\ICategoryRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
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
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function allCategories() {
        $categories = Category::query()
            ->select('id', 'title', 'image', 'status')
            ->with('children')
            ->where('parent_id', 0)
            ->where('status', 1)
            ->orderBy('priority', 'desc')
            ->get();

        return CategoryResource::collection($categories);
    }

    /**
     * Get the children of a specific category.
     * @param Category $category
     * @return Collection
     */
    public function getCategoryChildren(Category $category): Collection
    {
        return Category::query()
            ->select('id', 'title', 'image', 'status')
            ->where('parent_id', $category->id)
            ->where('status', 1)
            ->orderBy('priority', 'desc')
            ->get();
    }

    /**
     * Get all parent categories.
     * @return Collection
     */
    public function getParentCategories(): Collection
    {
        return Category::query()
            ->select('id', 'title', 'image', 'status')
            ->where('parent_id', 0)
            ->where('status', 1)
            ->orderBy('priority', 'desc')
            ->get();
    }

    /**
     * Get the productCategories.
     * @return Collection
     */
    public function activeProductCategories() :Collection
    {
        return Category::query()
            ->select('id', 'title', 'image')
            ->where('parent_id', 0)
            ->where('status', 1)
            ->orderBy('priority', 'desc')
            ->limit(config('product.category_limit'))
            ->get();
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

    /**
     * Get the Category with parent hierarchy.
     * @param Category $category
     * @return Category
     */
    public function showWithParents(Category $category) :Category
    {
        return Category::query()
                ->with(['parent' => function ($query) {
                    $query->select('id', 'title', 'image', 'status', 'parent_id');
                }])
                ->where('id', $category->id)
                ->first();
    }

    /**
     * Get the filters associated with a specific category.
     * @param TableRequest $request
     * @param Category $category
     * @return LengthAwarePaginator
     */
    public function getCategoryFilters(TableRequest $request, Category $category) :LengthAwarePaginator
    {
        $search = $request->get('query');
        $filters = $category->filters()
            ->when(!empty($search), function ($query) use ($search) {
                return $query->where('title', 'like', '%' . $search . '%');
            })
            ->where('status', 1)
            ->orderBy($request->get('column', 'priority'), $request->get('sort', 'desc'))
            ->paginate($request->get('count', 25));

        return $filters->through(fn ($filter) => new FilterResource($filter));

    }

    /**
     * Get the services associated with a specific category.
     * @param TableRequest $request
     * @param Category $category
     * @return LengthAwarePaginator
     */
    public function getCategoryServices(TableRequest $request, Category $category) :LengthAwarePaginator
    {

        if (empty($category->services()->count())) {
            $category = Category::find($category->parent_id);
        }


        $search = $request->get('query');
        $services = $category->services()
            ->when(!empty($search), function ($query) use ($search) {
                return $query->where('title', 'like', '%' . $search . '%');
            })
            ->where('status', 1)
            ->orderBy($request->get('column', 'id'), $request->get('sort', 'desc'))
            ->paginate($request->get('count', 25));

        return $services->through(fn ($service) => new ServiceResource($service));
    }

    /**
     * Store the productCategory.
     * @param ProductCategoryRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function store(ProductCategoryRequest $request) :JsonResponse
    {
        $this->checkLevelAccess();

        $productCategory = Category::create([
            'title' => $request->input('title'),
            'status' => $request->input('status'),
            'user_id' => Auth::user()->id,
        ]);

        if ($productCategory) {
            return response()->json([
                'status' => 1,
                'message' => __('site.The operation has been successfully')
            ], Response::HTTP_CREATED);
        }

        throw new \Exception();
    }

    /**
     * Update the productCategory.
     * @param ProductCategoryRequest $request
     * @param Category $productCategory
     * @return JsonResponse
     * @throws \Exception
     */
    public function update(ProductCategoryRequest $request, Category $productCategory) :JsonResponse
    {
        $this->checkLevelAccess(Auth::user()->id == $productCategory->user_id);

        $productCategory = $productCategory->update([
            'title' => $request->input('title'),
            'status' => $request->input('status'),
            'user_id' => Auth::user()->id,
        ]);

        if ($productCategory) {
            return response()->json([
                'status' => 1,
                'message' => __('site.The operation has been successfully')
            ], Response::HTTP_OK);
        }

        throw new \Exception();
    }

    /**
    * Delete the productCategory.
    * @param UpdatePasswordRequest $request
    * @param Category $productCategory
    * @return JsonResponse
    */
   public function destroy(Category $productCategory) :JsonResponse
   {
        $this->checkLevelAccess(Auth::user()->id == $productCategory->user_id);

        $productCategory->delete();

        if ($productCategory) {
            return response()->json([
                'status' => 1,
                'message' => __('site.The operation has been successfully')
            ], Response::HTTP_OK);
        }

        throw new \Exception();
   }
}
