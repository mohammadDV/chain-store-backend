<?php

namespace Application\Api\Product\Controllers;

use Core\Http\Controllers\Controller;
use Domain\Product\Repositories\Contracts\IDiscountRepository;
use Illuminate\Http\JsonResponse;

class DiscountController extends Controller
{
    public function __construct(protected IDiscountRepository $repository) {}

    /**
     * Get the Discount.
     */
    public function getActiveDiscount(): JsonResponse
    {
        return $this->repository->getActiveDiscount();
    }
}
