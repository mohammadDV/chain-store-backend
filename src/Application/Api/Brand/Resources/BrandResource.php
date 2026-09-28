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
            'slug' => $this->resource->slug,
            'logo' => $this->resource->logo,
            'description' => $this->resource->description,
            'meta_title' => $this->resource->meta_title,
            'meta_description' => $this->resource->meta_description,
            'meta_keywords' => $this->resource->meta_keywords,
            'og_image' => $this->resource->og_image,
            'banners' => BannerResource::collection($this->whenLoaded('banners')),
            'colors' => ColorResource::collection($this->whenLoaded('colors')),
        ];
    }
}
