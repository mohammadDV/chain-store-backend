<?php

namespace Domain\Product\Repositories;

use Application\Api\Brand\Resources\ColorResource;
use Core\Http\Requests\TableRequest;
use Core\Http\traits\GlobalFunc;
use Domain\Brand\Models\Brand;
use Domain\Product\Models\Color;
use Domain\Product\Repositories\Contracts\IColorRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class ColorRepository.
 */
class ColorRepository implements IColorRepository
{
    use GlobalFunc;

    /**
     * Get the productCategories pagination.
     * @param TableRequest $request
     * @return LengthAwarePaginator
     */
    public function index(TableRequest $request) :LengthAwarePaginator
    {
        $search = $request->get('query');
        $colors = Color::query()
            ->when(!empty($search), function ($query) use ($search) {
                return $query->where('title', 'like', '%' . $search . '%');
            })
            ->orderBy($request->get('column', 'id'), $request->get('sort', 'desc'))
            ->paginate($request->get('count', 25));

        return $colors->through(fn ($color) => new ColorResource($color));
    }

    /**
     * Get the colors.
     * @param Brand|null $brand
     */
    public function activeColors(?Brand $brand = null)
    {
        $colors = Color::query()
            ->select('id', 'title', 'code')
            ->when($brand, function ($query) use ($brand) {
                $query->whereHas('brands', function ($query) use ($brand) {
                    $query->where('brand_id', $brand->id)
                        ->where('brand_category.status', 1);
                });
            })
            ->where('status', 1)
            ->orderBy('priority', 'desc')
            ->get();


        return ColorResource::collection($colors);
    }

    /**
     * Get the Category.
     * @param Color $color
     * @return ColorResource
     */
    public function show(Color $color) :ColorResource
    {
        return new ColorResource($color);
    }
}