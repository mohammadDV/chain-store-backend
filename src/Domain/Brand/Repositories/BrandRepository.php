<?php

namespace Domain\Brand\Repositories;

use Application\Api\Brand\Resources\BannerResource;
use Application\Api\Brand\Resources\BrandResource;
use Core\Http\Requests\TableRequest;
use Core\Http\traits\GlobalFunc;
use Domain\Brand\Models\Banner;
use Domain\Brand\Models\Brand;
use Domain\Brand\Repositories\Contracts\IBrandRepository;
use Illuminate\Http\Request;
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

    /**
     * Get the banners.
     * @param Request $request
     * @return Collection
     */
    public function getBanners(Request $request) :Collection
    {

        $banners = Banner::query()
            ->where('status', 1)
            ->when(!empty($request->get('brand')), function ($query) use ($request) {
                $query->whereHas('brand', function ($query) use ($request) {
                    $query->where('id', $request->get('brand'));
                });
            })
            ->when(empty($request->get('brand')), function ($query) {
                $query->whereNull('brand_id');
            })
            ->inRandomOrder()
            ->limit(10)
            ->get();

        return $banners->map(fn ($banner) => new BannerResource($banner));
    }
}
