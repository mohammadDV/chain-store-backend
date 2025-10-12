<?php

namespace Application\Api\Product\Resources;

use Application\Api\Address\Resources\AreaResource;
use Application\Api\Address\Resources\CityResource;
use Application\Api\Address\Resources\CountryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductBoxResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {

        return [
            'id' => $this->id,
            'title' => $this->title,
            'amount' => intval($this->amount),
            'full_amount' => intval($this->full_amount),
            'color_id' => $this->color_id,
            'brand_id' => $this->brand_id,
            'description' => $this->description,
            'image' => $this->image,
            'rate' => $this->rate,
        ];
    }
}
