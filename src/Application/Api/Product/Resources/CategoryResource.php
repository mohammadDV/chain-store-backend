<?php

namespace Application\Api\Product\Resources;

use Domain\Product\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Category $resource
 */
class CategoryResource extends JsonResource
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
            'parent_id' => $this->resource->parent_id,
            'image' => $this->resource->image ?? '',
            'children' => CategoryResource::collection($this->whenLoaded('childrenRecursive')),
            'parent' => new CategoryResource($this->whenLoaded('parentRecursive')),
        ];
    }
}
