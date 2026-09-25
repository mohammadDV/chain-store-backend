<?php

namespace Application\Api\Product\Resources;

use Domain\Product\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

/**
 * @property-read Order $resource
 */
class OrderResource extends JsonResource
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
            'code' => $this->resource->code,
            'postal' => $this->resource->postal,
            'address' => $this->resource->address,
            'postal_code' => $this->resource->postal_code,
            'user_id' => $this->resource->user_id,
            'product_count' => $this->resource->product_count,
            'total_amount' => $this->resource->total_amount,
            'delivery_amount' => $this->resource->delivery_amount,
            'discount_amount' => $this->resource->discount_amount,
            'amount' => $this->resource->amount,
            'status' => $this->resource->status,
            'vip' => $this->resource->vip,
            'products' => $this->whenLoaded('products', function () {
                return $this->resource->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'title' => $product->title,
                        'image' => $product->image,
                        'count' => $product->pivot->count,
                        'amount' => $product->pivot->amount,
                        'status' => $product->pivot->status,
                        'color_id' => $product->pivot->color_id,
                        'size_id' => $product->pivot->size_id,
                    ];
                });
            }),
            'created_at' => $this->resource->created_at ? Jalalian::fromDateTime($this->resource->created_at)->format('Y/m/d') : null,
        ];
    }
}
