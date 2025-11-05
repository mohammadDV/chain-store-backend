<?php

namespace Application\Api\Product\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

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
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'value' => $this->value . ' ' . ($this->type === 'percentage' ? '%' : 'تومان'),
            'max_value' => $this->max_value,
            'expire_date' => $this->expire_date ? Jalalian::fromDateTime($this->expire_date)->format('Y/m/d') : null,
        ];
    }
}
