<?php

namespace Application\Api\Brand\Controllers;

use Core\Http\Controllers\Controller;
use Core\Http\Requests\TableRequest;
use Domain\Brand\Repositories\Contracts\IBrandRepository;
use Domain\Brand\Models\Brand;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{

    /**
     * @param IBrandRepository $repository
     */
    public function __construct(protected IBrandRepository $repository)
    {

    }

    /**
     * Get all of brands with pagination
     * @param TableRequest $request
     * @return JsonResponse
     */
    public function index(TableRequest $request): JsonResponse
    {

        $var = 9;
        var_dump(!($var & 1));
        return response()->json($this->repository->index($request), Response::HTTP_OK);
    }

    /**
     * Get the brand.
     * @param Brand $brand
     * @return JsonResponse
     */
    public function show(Brand $brand) :JsonResponse
    {
        return response()->json($this->repository->show($brand), Response::HTTP_OK);
    }

    /**
     * Get the banners.
     * @param Request $request
     * @return JsonResponse
     */
    public function getBanners(Request $request) :JsonResponse
    {
        return response()->json($this->repository->getBanners($request), Response::HTTP_OK);
    }
}