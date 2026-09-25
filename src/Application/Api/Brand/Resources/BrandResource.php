<?php

namespace Application\Api\Brand\Resources;

use Domain\Brand\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Brand $resource
 */
class BrandResource extends JsonResource
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
            'logo' => $this->resource->logo,
            'description' => $this->resource->description,
            'banners' => BannerResource::collection($this->whenLoaded('banners')),
            'colors' => ColorResource::collection($this->whenLoaded('colors')),
        ];
    }
}
