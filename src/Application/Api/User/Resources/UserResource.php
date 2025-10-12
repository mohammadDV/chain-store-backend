<?php

namespace Application\Api\User\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'nickname' => $this->nickname,
            'biography' => $this->biography,
            'profile_photo_path' => $this->profile_photo_path,
            'bg_photo_path' => $this->bg_photo_path,
            'biography' => $this->biography,
            'rate' => $this->rate,
            'point' => $this->point,
        ];
    }
}
