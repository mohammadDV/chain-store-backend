<?php

namespace Application\Api\Product\Controllers;

use Core\Http\Controllers\Controller;
use Core\Http\Requests\TableRequest;
use Domain\Brand\Models\Brand;
use Domain\Product\Models\Color;
use Domain\Product\Repositories\Contracts\IColorRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ColorController extends Controller
{
    public function __construct(protected IColorRepository $repository) {}

    /**
     * Get all of Colors with pagination
     */
    public function index(TableRequest $request): JsonResponse
    {
        return response()->json($this->repository->index($request), Response::HTTP_OK);
    }

    /**
     * Get all of Colors
     */
    public function activeColors(?Brand $brand = null): JsonResponse
    {
        return response()->json($this->repository->activeColors($brand), Response::HTTP_OK);
    }

    /**
     * Get the Color.
     */
    public function show(Color $color): JsonResponse
    {
        return response()->json($this->repository->show($color), Response::HTTP_OK);
    }
}
