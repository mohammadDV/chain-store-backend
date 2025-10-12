<?php

namespace Application\Api\Product\Controllers;

use Application\Api\Product\Requests\CategoryRequest;
use Application\Api\Product\Resources\CategoryWithParentsResource;
use Core\Http\Controllers\Controller;
use Core\Http\Requests\TableRequest;
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
     * @return JsonResponse
     */
    public function activeProductCategories(TableRequest $request): JsonResponse
    {
        return response()->json($this->repository->activeProductCategories($request), Response::HTTP_OK);
    }

    /**
     * Get all of ProductCategories
     * @return JsonResponse
     */
    public function allCategories(): JsonResponse
    {
        return response()->json($this->repository->allCategories(), Response::HTTP_OK);
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
     * Get all parent categories.
     * @return JsonResponse
     */
    public function getParentCategories() :JsonResponse
    {
        return response()->json($this->repository->getParentCategories(), Response::HTTP_OK);
    }

    /**
     * Get the Category.
     * @param Category $category
     * @return JsonResponse
     */
    public function show(Category $category) :JsonResponse
    {
        return response()->json(new CategoryWithParentsResource($this->repository->show($category)), Response::HTTP_OK);
    }

    /**
     * Get the filters associated with a specific category.
     * @param TableRequest $request
     * @param Category $category
     * @return JsonResponse
     */
    public function getCategoryFilters(TableRequest $request, Category $category): JsonResponse
    {
        return response()->json($this->repository->getCategoryFilters($request, $category), Response::HTTP_OK);
    }

    /**
     * Get the services associated with a specific category.
     * @param TableRequest $request
     * @param Category $category
     * @return JsonResponse
     */
    public function getCategoryServices(TableRequest $request, Category $category): JsonResponse
    {
        return response()->json($this->repository->getCategoryServices($request, $category), Response::HTTP_OK);
    }
}