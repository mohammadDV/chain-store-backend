<?php

namespace Application\Api\Product\Controllers;

use Core\Http\Controllers\Controller;
use Core\Http\Requests\TableRequest;
use Domain\Brand\Models\Brand;
use Domain\Product\Models\Category;
use Domain\Product\Repositories\Contracts\ICategoryRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    public function __construct(protected ICategoryRepository $repository) {}

    /**
     * Get all of ProductCategories with pagination
     */
    public function index(TableRequest $request): JsonResponse
    {
        return response()->json($this->repository->index($request), Response::HTTP_OK);
    }

    /**
     * Get all of ProductCategories
     */
    public function activeProductCategories(?Brand $brand = null): JsonResponse
    {
        return response()->json($this->repository->activeProductCategories($brand), Response::HTTP_OK);
    }

    /**
     * Get all of ProductCategories
     */
    public function allCategories(?Brand $brand = null): JsonResponse
    {
        return response()->json($this->repository->allCategories($brand), Response::HTTP_OK);
    }

    /**
     * Get the children of a specific category.
     */
    public function getCategoryChildren(Category $category): JsonResponse
    {
        return response()->json($this->repository->getCategoryChildren($category), Response::HTTP_OK);
    }

    /**
     * Get the Category.
     */
    public function show(Category $category): JsonResponse
    {
        return response()->json($this->repository->show($category), Response::HTTP_OK);
    }
}
