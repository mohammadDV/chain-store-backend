<?php

namespace Application\Api\Product\Controllers;

use Application\Api\Product\Requests\CategoryRequest;
use Application\Api\Product\Resources\CategoryWithParentsResource;
use Core\Http\Controllers\Controller;
use Core\Http\Requests\TableRequest;
use Domain\Brand\Models\Brand;
use Domain\Product\Models\Category;
use Domain\Product\Repositories\Contracts\ICategoryRepository;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;


class CategoryController extends Controller
{

    /**
     * @param ICategoryRepository $repository
     */
    public function __construct(protected ICategoryRepository $repository)
    {

    }

    /**
     * Get all of ProductCategories with pagination
     * @param TableRequest $request
     * @return JsonResponse
     */
    public function index(TableRequest $request): JsonResponse
    {
        return response()->json($this->repository->index($request), Response::HTTP_OK);
    }

    /**
     * Get all of ProductCategories
     * @param Brand $brand
     * @return JsonResponse
     */
    public function activeProductCategories(?Brand $brand = null): JsonResponse
    {
        return response()->json($this->repository->activeProductCategories($brand), Response::HTTP_OK);
    }

    /**
     * Get all of ProductCategories
     * @param Brand|null $brand
     * @return JsonResponse
     */
    public function allCategories(?Brand $brand = null): JsonResponse
    {
        return response()->json($this->repository->allCategories($brand), Response::HTTP_OK);
    }

    /**
     * Get the children of a specific category.
     * @param Category $category
     * @return JsonResponse
     */
    public function getCategoryChildren(Category $category) :JsonResponse
    {
        return response()->json($this->repository->getCategoryChildren($category), Response::HTTP_OK);
    }

    /**
     * Get the Category.
     * @param Category $category
     * @return JsonResponse
     */
    public function show(Category $category) :JsonResponse
    {
        return response()->json($this->repository->show($category), Response::HTTP_OK);
    }
}