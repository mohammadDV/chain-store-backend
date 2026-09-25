<?php

namespace Application\Api\Product\Resources;

use Application\Api\Brand\Resources\BrandResource;
use Application\Api\User\Resources\UserResource;
use Core\Helpers\HelperClass;
use Domain\Product\Models\Favorite;
use Domain\Product\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Product $resource
 */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isFavorite = false;

        // Manually check if user is logged in and get user ID
        $userId = HelperClass::getUserIdFromToken($request);

        if ($userId) {
            $isFavorite = Favorite::query()
                ->where('product_id', $this->resource->id)
                ->where('user_id', $userId)
                ->exists();
        }

        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'color_id' => $this->resource->color_id,
            'amount' => intval($this->resource->amount),
            'discount' => intval($this->resource->discount),
            'status' => $this->resource->status,
            'description' => $this->resource->description,
            'details' => $this->resource->details,
            'vip' => $this->resource->vip,
            'image' => $this->resource->image,
            'rate' => $this->resource->rate,
            'reviews_count' => $this->resource->reviews()->count(),
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
