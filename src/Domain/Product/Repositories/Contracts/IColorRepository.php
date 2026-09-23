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
     */
    public function index(TableRequest $request): LengthAwarePaginator;

    /**
     * Get the colors.
     */
    public function activeColors(?Brand $brand = null);

    /**
     * Get the Color.
     */
    public function show(Color $color): ColorResource;
}
