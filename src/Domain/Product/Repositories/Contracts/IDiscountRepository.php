<?php

namespace Domain\Product\Repositories\Contracts;

use Illuminate\Http\JsonResponse;

/**
 * Interface IDiscountRepository.
 */
interface IDiscountRepository
{

    /**
     * Get the Active Discount.
     * @return JsonResponse
     */
    public function getActiveDiscount() :JsonResponse;

}
