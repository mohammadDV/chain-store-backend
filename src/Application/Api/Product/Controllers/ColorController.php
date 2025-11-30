<?php

namespace Application\Api\Product\Controllers;

use Core\Http\Controllers\Controller;
use Core\Http\Requests\TableRequest;
use Domain\Brand\Models\Brand;
use Domain\Product\Models\Color;
use Domain\Product\Repositories\Contracts\IColorRepository;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;


class ColorController extends Controller
{

    /**
     * @param IColorRepository $repository
     */
    public function __construct(protected IColorRepository $repository)
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
    public function activeColors(?Brand $brand = null): JsonResponse
    {
        return response()->json($this->repository->activeColors($brand), Response::HTTP_OK);
    }

    /**
     * Get the Color.
     * @param Color $color
     * @return JsonResponse
     */
    public function show(Color $color) :JsonResponse
    {
        return response()->json($this->repository->show($color), Response::HTTP_OK);
    }
}
