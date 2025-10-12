<?php

namespace Application\Api\Brand\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {

        return [
            'id' => $this->id,
            'title' => $this->title,
            'logo' => $this->logo,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'banners' => BannerResource::collection($this->whenLoaded('banners')),
            'colors' => ColorResource::collection($this->whenLoaded('colors')),
        ];
    }
}
