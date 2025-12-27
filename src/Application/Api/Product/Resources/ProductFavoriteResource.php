<?php

namespace Application\Api\Product\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductFavoriteResource extends JsonResource
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
            'discount' => intval($this->discount),
            'image' => $this->image,
            'rate' => $this->rate,
            'is_favorite' => true,
        ];
    }
}