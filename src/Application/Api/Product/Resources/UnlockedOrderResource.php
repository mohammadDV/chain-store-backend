<?php

namespace Application\Api\Product\Resources;

use Domain\Product\Models\Color;
use Domain\Product\Models\Order;
use Domain\Product\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

/**
 * Full order payload returned after invoice unlock password/token succeeds.
 *
 * @property-read Order $resource
 */
class UnlockedOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $colorIds = [];
        $sizeIds = [];

        if ($this->resource->relationLoaded('products')) {
            foreach ($this->resource->products as $product) {
                if ($product->pivot->color_id) {
                    $colorIds[] = $product->pivot->color_id;
                }
                if ($product->pivot->size_id) {
                    $sizeIds[] = $product->pivot->size_id;
                }
            }
        }

        $colors = [];
        $sizes = [];

        if ($colorIds !== []) {
            $colors = Color::query()
                ->whereIn('id', array_unique($colorIds))
                ->pluck('title', 'id')
                ->all();
        }

        if ($sizeIds !== []) {
            $sizes = Size::query()
                ->whereIn('id', array_unique($sizeIds))
                ->pluck('title', 'id')
                ->all();
        }

        return [
            'code' => $this->resource->code,
            'status' => $this->resource->status,
            'product_count' => $this->resource->product_count,
            'amount' => $this->resource->amount,
            'delivery_amount' => $this->resource->delivery_amount,
            'discount_amount' => $this->resource->discount_amount,
            'total_amount' => $this->resource->total_amount,
            'description' => $this->resource->description,
            'vip' => (bool) $this->resource->vip,
            'fullname' => $this->resource->fullname ?: null,
            'mobile' => $this->resource->user->mobile,
            'address' => $this->resource->address,
            'postal_code' => $this->resource->postal_code,
            'products' => $this->whenLoaded('products', function () use ($colors, $sizes) {
                return $this->resource->products->map(function ($product) use ($colors, $sizes) {
                    $colorId = $product->pivot->color_id;
                    $sizeId = $product->pivot->size_id;

                    return [
                        'id' => $product->id,
                        'title' => $product->title,
                        'image' => $product->image,
                        'code' => $product->code,
                        'count' => $product->pivot->count,
                        'amount' => $product->pivot->amount,
                        'status' => $product->pivot->status,
                        'color_id' => $colorId,
                        'color' => $colorId ? ($colors[$colorId] ?? null) : null,
                        'size_id' => $sizeId,
                        'size' => $sizeId ? ($sizes[$sizeId] ?? null) : null,
                    ];
                })->values();
            }),
            'created_at' => $this->resource->created_at
                ? Jalalian::fromDateTime($this->resource->created_at)->format('Y/m/d H:i')
                : null,
        ];
    }
}
