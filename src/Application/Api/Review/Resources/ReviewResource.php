<?php

namespace Application\Api\Review\Resources;

use Application\Api\User\Resources\UserResource;
use Carbon\Carbon;
use Domain\Review\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Review $resource
 */
class ReviewResource extends JsonResource
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
            'comment' => $this->resource->comment,
            'rate' => $this->resource->rate,
            'status' => $this->resource->status,
            'product_id' => $this->resource->product_id,
            'user_id' => $this->resource->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'likes_count' => $this->resource->likes_count,
            'created_at' => Carbon::parse($this->resource->created_at)->format('Y M d'),
        ];
    }
}
