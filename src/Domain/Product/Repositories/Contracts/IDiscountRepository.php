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
     */
    public function getActiveDiscount(): JsonResponse;
}
