<?php

namespace Domain\Post\Repositories\Contracts;

use Application\Api\Post\Resources\PostResource;
use Core\Http\Requests\TableRequest;
use Domain\Post\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface IPostRepository.
 */
interface IPostRepository
{
    /**
     * Get the posts.
     */
    public function getPosts(TableRequest $request): LengthAwarePaginator;

    /**
     * Get the popular posts.
     */
    public function getPopularPosts(TableRequest $request): LengthAwarePaginator;

    /**
     * Get the latest posts.
     */
    public function getLatestPosts(TableRequest $request): LengthAwarePaginator;

    /**
     * Get the post info.
     */
    public function getPostInfo(Post $post): PostResource;
}
