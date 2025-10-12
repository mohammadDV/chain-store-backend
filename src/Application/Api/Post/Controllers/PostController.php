<?php

namespace Application\Api\Post\Controllers;

use Application\Api\Post\Requests\PostRequest;
use Application\Api\Post\Requests\PostUpdateRequest;
use Core\Http\Controllers\Controller;
use Core\Http\Requests\TableRequest;
use Domain\Post\Models\Post;
use Domain\Post\Repositories\Contracts\IPostRepository;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;


class PostController extends Controller
{
/**
     * Constructor of PostController.
     */
    public function __construct(protected IPostRepository $repository)
    {
        //
    }

    /**
     * Get all of active posts.
     */
    public function getPosts(TableRequest $request): JsonResponse
    {
        return response()->json($this->repository->getPosts($request), Response::HTTP_OK);
    }

    /**
     * Get all of popular posts.
     */
    public function getPopularPosts(TableRequest $request): JsonResponse
    {
        return response()->json($this->repository->getPopularPosts($request), Response::HTTP_OK);
    }

    /**
     * Get all of latest posts.
     */
    public function getLatestPosts(TableRequest $request): JsonResponse
    {
        return response()->json($this->repository->getLatestPosts($request), Response::HTTP_OK);
    }

    /**
     * Get the post info.
     */
    public function getPostInfo(Post $post): JsonResponse
    {
        return response()->json($this->repository->getPostInfo($post), Response::HTTP_OK);
    }
}
