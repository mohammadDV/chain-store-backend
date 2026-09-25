<?php

namespace Application\Api\Brand\Resources;

use Domain\Brand\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Banner $resource
 */
class BannerResource extends JsonResource
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
            'link' => $this->resource->link,
            'image' => $this->resource->image,
        ];
    }
}
