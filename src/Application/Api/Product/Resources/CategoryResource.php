<?php

namespace Application\Api\Product\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'id' => $this->id,
            'title' => $this->title,
            'parent_id' => $this->parent_id,
            'image' => $this->image ?? '',
            'children' => CategoryResource::collection($this->whenLoaded('childrenRecursive')),
            'parent' => new CategoryResource($this->whenLoaded('parentRecursive')),
        ];
    }
}
