<?php

namespace Domain\Product\Repositories\Contracts;

use Application\Api\Brand\Resources\ColorResource;
use Core\Http\Requests\TableRequest;
use Domain\Brand\Models\Brand;
use Domain\Product\Models\Color;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface IColorRepository.
 */
interface IColorRepository
{
    /**
     * Get the colors pagination.
     * @param TableRequest $request
     * @return LengthAwarePaginator
     */
    public function index(TableRequest $request) :LengthAwarePaginator;

    /**
     * Get the colors.
     * @param Brand|null $brand
     */
    public function activeColors(?Brand $brand = null);

    /**
     * Get the Color.
     * @param Color $color
     * @return ColorResource
     */
    public function show(Color $color) :ColorResource;

}