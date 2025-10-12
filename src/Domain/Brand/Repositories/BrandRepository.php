<?php

namespace Domain\Brand\Repositories;

use Application\Api\Brand\Resources\BrandResource;
use Core\Http\Requests\TableRequest;
use Core\Http\traits\GlobalFunc;
use Domain\Brand\Models\Brand;
use Domain\Brand\Repositories\Contracts\IBrandRepository;
use Illuminate\Support\Collection;

/**
 * Class BrandRepository.
 */
class BrandRepository implements IBrandRepository
{
    use GlobalFunc;

    public function __construct()
    {
        //
    }

    /**
     * Get the brands collection.
     * @param TableRequest $request
     * @return Collection
     */
    public function index(TableRequest $request) :Collection
    {
        $brands = Brand::query()
            ->with(['banners', 'colors'])
            ->where('status', 1)
            ->orderBy('priority', 'desc')
            ->get();

        return $brands->map(fn ($brand) => new BrandResource($brand));
    }

    /**
     * Get the brand.
     * @param Brand $brand
     * @return array
     */
    public function show(Brand $brand) :BrandResource
    {
        return new BrandResource($brand);

    }
}
