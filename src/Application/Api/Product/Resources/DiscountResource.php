<?php

namespace Application\Api\Product\Resources;

use Domain\Product\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

/**
 * @property-read Discount $resource
 */
class DiscountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'code' => $this->resource->code,
            'type' => $this->resource->type,
            'value' => $this->resource->value.' '.($this->resource->type === 'percentage' ? '%' : 'تومان'),
            'max_value' => $this->resource->max_value,
            'expire_date' => $this->resource->expire_date ? Jalalian::fromDateTime($this->resource->expire_date)->format('Y/m/d') : null,
        ];
    }
}
