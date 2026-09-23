<?php

namespace Application\Api\Review\Resources;

use Application\Api\User\Resources\UserResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
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
