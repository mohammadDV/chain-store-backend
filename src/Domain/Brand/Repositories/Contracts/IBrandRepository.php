<?php

namespace Domain\Brand\Repositories\Contracts;

use Application\Api\Brand\Resources\BrandResource;
use Core\Http\Requests\TableRequest;
use Domain\Brand\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Interface IBrandRepository.
 */
interface IBrandRepository
{
    /**
     * Get the brands collection.
     */
    public function index(TableRequest $request): Collection;

    /**
     * Get the brand.
     */
    public function show(Brand $brand): BrandResource;

    /**
     * Get the banners.
     */
    public function getBanners(Request $request): Collection;
}
