<?php

namespace Application\Api\Review\Resources;

use Application\Api\Product\Resources\FileResource;
use Application\Api\Product\Resources\ServiceResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Application\Api\User\Resources\UserResource;
use Carbon\Carbon;

class ReviewResource extends JsonResource
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
            'comment' => $this->comment,
            'rate' => $this->rate,
            'status' => $this->status,
            'product_id' => $this->product_id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'likes_count' => $this->likes_count,
            'created_at' => Carbon::parse($this->created_at)->format('Y M d'),
        ];
    }
}