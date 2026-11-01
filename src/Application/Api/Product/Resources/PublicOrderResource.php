<?php

namespace Application\Api\Product\Resources;

use Domain\Product\Models\Order;
use Domain\Setting\Services\InvoiceUnlockService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

/**
 * Safe, public-facing order payload for invoice QR verification.
 *
 * When an unlock password is configured, only a minimal shell is returned;
 * full details require POST .../recipient with the shared password.
 *
 * @property-read Order $resource
 */
class PublicOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $unlockAvailable = app(InvoiceUnlockService::class)->hasPassword();

        if ($unlockAvailable) {
            return [
                'code' => $this->resource->code,
                'unlock_available' => true,
                'recipient_unlock_available' => true,
            ];
        }

        return [
            'code' => $this->resource->code,
            'product_count' => $this->resource->product_count,
            'amount' => $this->resource->amount,
            'delivery_amount' => $this->resource->delivery_amount,
            'discount_amount' => $this->resource->discount_amount,
            'total_amount' => $this->resource->total_amount,
            'status' => $this->resource->status,
            'unlock_available' => false,
            'recipient_unlock_available' => false,
            'products' => $this->whenLoaded('products', function () {
                return $this->resource->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'title' => $product->title,
                        'image' => $product->image,
                        'count' => $product->pivot->count,
                        'amount' => $product->pivot->amount,
                        'color_id' => $product->pivot->color_id,
                        'size_id' => $product->pivot->size_id,
                    ];
                })->values();
            }),
            'created_at' => $this->resource->created_at
                ? Jalalian::fromDateTime($this->resource->created_at)->format('Y/m/d H:i')
                : null,
        ];
    }
}
