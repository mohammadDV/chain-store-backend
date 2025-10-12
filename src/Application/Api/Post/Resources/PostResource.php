<?php

namespace Application\Api\Post\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class PostResource extends JsonResource
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
            'pre_title' => $this->pre_title,
            'title' => $this->title,
            'slug' => $this->slug,
            'summary' => $this->summary,
            'content' => $this->content,
            'type' => $this->type,
            'image' => $this->image,
            'video' => $this->video,
            'view' => $this->view,
            'special' => $this->special,
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->format('Y M d H:i') : null,
        ];
    }
}
