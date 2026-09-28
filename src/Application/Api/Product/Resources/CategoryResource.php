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
            'slug' => $this->resource->slug,
            'parent_id' => $this->resource->parent_id,
            'description' => $this->resource->description,
            'meta_title' => $this->resource->meta_title,
            'meta_description' => $this->resource->meta_description,
            'meta_keywords' => $this->resource->meta_keywords,
            'og_image' => $this->resource->og_image,
            'image' => $this->resource->image ?? '',
            'children' => CategoryResource::collection($this->whenLoaded('childrenRecursive')),
            'parent' => new CategoryResource($this->whenLoaded('parentRecursive')),
        ];
    }
}
