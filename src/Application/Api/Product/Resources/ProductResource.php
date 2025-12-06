<?php

namespace Application\Api\Product\Resources;

use Application\Api\Brand\Resources\BrandResource;
use Application\Api\Product\Resources\CategoryResource;
use Application\Api\Product\Resources\FileResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Application\Api\User\Resources\UserResource;
use Core\Helpers\HelperClass;
use Domain\Product\Models\Favorite;

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

        $isFavorite = false;

        // Manually check if user is logged in and get user ID
        $userId = HelperClass::getUserIdFromToken($request);

        if ($userId) {
            $isFavorite = Favorite::query()
                ->where('product_id', $this->id)
                ->where('user_id', $userId)
                ->exists();
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'color_id' => $this->color_id,
            'amount' => intval($this->amount),
            'discount' => intval($this->discount),
            'status' => $this->status,
            'description' => $this->description,
            'details' => $this->details,
            'vip' => $this->vip,
            'image' => $this->image,
            'rate' => $this->rate,
            'reviews_count' => $this->reviews()->count(),
            'attributes' => ProductAttributeResource::collection($this->whenLoaded('attributes')),
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'files' => FileResource::collection($this->whenLoaded('files')),
            'user' => new UserResource($this->whenLoaded('user')),
            'sizes' => SizeResource::collection($this->whenLoaded('sizes')),
            'is_favorite' => $isFavorite,
        ];
    }
}
