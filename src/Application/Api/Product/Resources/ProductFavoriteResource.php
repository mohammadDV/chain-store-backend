<?php

namespace Application\Api\Product\Resources;

use Domain\Product\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Product $resource
 */
class ProductFavoriteResource extends JsonResource
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
            'title' => $this->resource->title,
            'amount' => intval($this->resource->amount),
            'discount' => intval($this->resource->discount),
            'image' => $this->resource->image,
            'rate' => $this->resource->rate,
            'is_favorite' => true,
        ];
    }
}
