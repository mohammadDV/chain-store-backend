<?php

namespace Application\Api\Product\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

class OrderResource extends JsonResource
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
            'user_id' => $this->user_id,
            'product_count' => $this->product_count,
            'total_amount' => $this->total_amount,
            'delivery_amount' => $this->delivery_amount,
            'discount_amount' => $this->discount_amount,
            'status' => $this->status,
            'vip' => $this->vip,
            'products' => $this->whenLoaded('products', function () {
                return $this->products->map(function ($product) {
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
            'created_at' => $this->created_at ? Jalalian::fromDateTime($this->created_at)->format('Y/m/d') : null,
        ];
    }
}
