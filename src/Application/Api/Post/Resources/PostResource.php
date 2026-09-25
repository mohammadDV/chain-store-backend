<?php

namespace Application\Api\Post\Resources;

use Domain\Post\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property-read Post $resource
 */
class PostResource extends JsonResource
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
            'pre_title' => $this->resource->pre_title,
            'title' => $this->resource->title,
            'slug' => $this->resource->slug,
            'summary' => $this->resource->summary,
            'content' => $this->resource->content,
            'type' => $this->resource->type,
            'image' => $this->resource->image,
            'video' => $this->resource->video,
            'view' => $this->resource->view,
            'special' => $this->resource->special,
            'created_at' => $this->resource->created_at ? Carbon::parse($this->resource->created_at)->format('Y M d H:i') : null,
        ];
    }
}
