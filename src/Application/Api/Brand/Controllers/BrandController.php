<?php

namespace Application\Api\Brand\Controllers;

use Core\Http\Controllers\Controller;
use Core\Http\Requests\TableRequest;
use Domain\Brand\Repositories\Contracts\IBrandRepository;
use Domain\Brand\Models\Brand;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;


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
}
