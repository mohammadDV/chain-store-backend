<?php

namespace Domain\Post\Repositories\Contracts;

use Application\Api\Post\Requests\PostRequest;
use Application\Api\Post\Requests\PostUpdateRequest;
use Application\Api\Post\Resources\PostResource;
use Core\Http\Requests\TableRequest;
use Domain\Post\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface IPostRepository.
 */
interface IPostRepository
{

    /**
     * Get the posts.
     * @param TableRequest $request
     * @return LengthAwarePaginator
     */
    public function getPosts(TableRequest $request) :LengthAwarePaginator;

    /**
     * Get the popular posts.
     * @param TableRequest $request
     * @return LengthAwarePaginator
     */
    public function getPopularPosts(TableRequest $request) :LengthAwarePaginator;

    /**
     * Get the latest posts.
     * @param TableRequest $request
     * @return LengthAwarePaginator
     */
    public function getLatestPosts(TableRequest $request) :LengthAwarePaginator;

     /**
     * Get the post info.
     * @param Post $post
     * @return PostResource
     */
    public function getPostInfo(Post $post) :PostResource;
}
