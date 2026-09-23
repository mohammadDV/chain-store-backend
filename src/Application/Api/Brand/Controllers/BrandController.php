<?php

namespace Application\Api\Brand\Controllers;

use Core\Http\Controllers\Controller;
use Core\Http\Requests\TableRequest;
use Domain\Brand\Models\Brand;
use Domain\Brand\Repositories\Contracts\IBrandRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BrandController extends Controller
{
    public function __construct(protected IBrandRepository $repository) {}

    /**
     * Get all of brands with pagination
     */
    public function index(TableRequest $request): JsonResponse
    {
        return response()->json($this->repository->index($request), Response::HTTP_OK);
    }

    /**
     * Get the brand.
     */
    public function show(Brand $brand): JsonResponse
    {
        return response()->json($this->repository->show($brand), Response::HTTP_OK);
    }

    /**
     * Get the banners.
     */
    public function getBanners(Request $request): JsonResponse
    {
        return response()->json($this->repository->getBanners($request), Response::HTTP_OK);
    }
}
