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
     * @param TableRequest $request
     * @return Collection
     */
    public function index(TableRequest $request) :Collection;

    /**
     * Get the brand.
     * @param Brand $brand
     * @return BrandResource
     */
    public function show(Brand $brand) :BrandResource;

    /**
     * Get the banners.
     * @param Request $request
     * @return Collection
     */
    public function getBanners(Request $request) :Collection;
}