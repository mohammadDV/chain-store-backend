<?php

namespace Application\Api\Product\Resources;

use Domain\Product\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Size $resource
 */
class SizeResource extends JsonResource
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
            'stock' => intval($this->resource->stock->quantity ?? 0),
        ];
    }
}
