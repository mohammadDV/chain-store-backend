<?php

namespace Application\Api\Product\Controllers;

use Core\Http\Controllers\Controller;
use Domain\Product\Repositories\Contracts\IDiscountRepository;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;


class DiscountController extends Controller
{

    /**
     * @param IDiscountRepository $repository
     */
    public function __construct(protected IDiscountRepository $repository)
    {

    }

    /**
     * Get the Discount.
     * @param Discount $discount
     * @return JsonResponse
     */
    public function getActiveDiscount() :JsonResponse
    {
        return $this->repository->getActiveDiscount();
    }
}
