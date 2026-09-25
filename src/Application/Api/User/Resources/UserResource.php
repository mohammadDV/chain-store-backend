<?php

namespace Application\Api\User\Resources;

use Domain\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read User $resource
 */
class UserResource extends JsonResource
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
            'nickname' => $this->resource->nickname,
            'biography' => $this->resource->biography,
            'profile_photo_path' => $this->resource->profile_photo_path,
            'bg_photo_path' => $this->resource->bg_photo_path,
            'rate' => $this->resource->rate,
            'point' => $this->resource->point,
        ];
    }
}
