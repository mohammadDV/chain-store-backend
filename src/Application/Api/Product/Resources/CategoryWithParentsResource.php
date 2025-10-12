<?php

namespace Application\Api\Product\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'id' => $this->id,
            'title' => $this->title,
            'image' => $this->image ?? '',
            'status' => $this->status,
            'parent_id' => $this->parent_id,
            'parent' => $this->when($this->parent, function () {
                return [
                    'id' => $this->parent->id,
                    'title' => $this->parent->title,
                    'image' => $this->parent->image ?? '',
                    'status' => $this->parent->status,
                    'parent_id' => $this->parent->parent_id,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
