<?php

namespace Domain\Product\Repositories;

use Application\Api\Product\Resources\DiscountResource;
use Core\Http\traits\GlobalFunc;
use Domain\Product\Models\Discount;
use Domain\Product\Repositories\Contracts\IDiscountRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Class DiscountRepository.
 */
class DiscountRepository implements IDiscountRepository
{
    use GlobalFunc;

    /**
     * Get the Category.
     * @param Discount $discount
     * @return JsonResponse
     */
    public function getActiveDiscount() :JsonResponse
    {

        $discount = Discount::query()
            ->where('active', 1)
            ->where('visible', 1)
            ->where(function ($query) {
                $query->whereNull('expire_date')
                    ->orWhere('expire_date', '>=', now());
            })
            ->first();

        if (!$discount) {
            return response()->json([
                'status' => 0,
                'message' => __('site.Discount not found')
            ], Response::HTTP_NOT_FOUND);
        }
        return response()->json([
            'status' => 1,
            'data' => new DiscountResource($discount),
            'message' => __('site.The operation has been successfully')
        ], Response::HTTP_OK);
    }
}
