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
            'image' => $this->image ?? '',
            'children' => $this->when($this->children->isNotEmpty(), function () {
                return $this->children->where('status', 1)->map(function ($child) {
                    return [
                        'title' => $child->title,
                        'image' => $child->image ?? '',
                        'status' => $child->status,
                    ];
                })->toArray();
            }, []),
        ];
    }
}
