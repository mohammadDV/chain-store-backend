<?php

namespace Application\Api\Product\Resources;

use Domain\Product\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Category $resource
 */
class CategoryWithParentsResource extends JsonResource
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
            'image' => $this->resource->image ?? '',
            'status' => $this->resource->status,
            'parent_id' => $this->resource->parent_id,
            'parent' => $this->when($this->resource->parent !== null, function () {
                return [
                    'id' => $this->resource->parent->id,
                    'title' => $this->resource->parent->title,
                    'image' => $this->resource->parent->image ?? '',
                    'status' => $this->resource->parent->status,
                    'parent_id' => $this->resource->parent->parent_id,
                ];
            }),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
