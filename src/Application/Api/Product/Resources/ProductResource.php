<?php

namespace Application\Api\Product\Resources;

use Application\Api\Product\Resources\CategoryResource;
use Application\Api\Product\Resources\FileResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Application\Api\User\Resources\UserResource;

class ProductResource extends JsonResource
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
            'color_id' => $this->color_id,
            'brand_id' => $this->brand_id,
            'amount' => intval($this->amount),
            'status' => $this->status,
            'description' => $this->description,
            'vip' => $this->vip,
            'image' => $this->image,
            'rate' => $this->rate,
            'reviews_count' => $this->reviews()->count(),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'files' => FileResource::collection($this->whenLoaded('files')),
            'user' => new UserResource($this->whenLoaded('user')),
            'sizes' => SizeResource::collection($this->whenLoaded('sizes')),
        ];
    }
}
