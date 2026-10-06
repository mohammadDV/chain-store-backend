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
use Illuminate\Support\Facades\Cache;

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
     */
    public function index(TableRequest $request): Collection
    {
        $payload = Cache::remember('brands:index', 3600, function () {
            $brands = Brand::query()
                ->with(['banners', 'colors'])
                ->where('status', 1)
                ->orderBy('priority', 'desc')
                ->get();

            return $brands->map(fn ($brand) => (new BrandResource($brand))->resolve())->all();
        });

        return collect($payload);
    }

    /**
     * Get the brand.
     */
    public function show(Brand $brand): BrandResource
    {
        return new BrandResource($brand);

    }

    /**
     * Get the banners.
     */
    public function getBanners(Request $request): Collection
    {
        $brandId = $request->get('brand');
        $cacheKey = 'banners:'.($brandId ?: 'home');

        $payload = Cache::remember($cacheKey, 300, function () use ($brandId) {
            $banners = Banner::query()
                ->where('status', 1)
                ->when(! empty($brandId), function ($query) use ($brandId) {
                    $query->whereHas('brand', function ($query) use ($brandId) {
                        $query->where('id', $brandId);
                    });
                })
                ->when(empty($brandId), function ($query) {
                    $query->whereNull('brand_id');
                })
                ->orderByDesc('id')
                ->limit(10)
                ->get();

            return $banners->map(fn ($banner) => (new BannerResource($banner))->resolve())->all();
        });

        return collect($payload);
    }
}
