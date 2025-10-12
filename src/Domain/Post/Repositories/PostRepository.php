<?php

namespace Domain\Post\Repositories;

use Application\Api\Post\Resources\PostResource;
use Core\Http\Requests\TableRequest;
use Core\Http\traits\GlobalFunc;
use Domain\Post\Models\Post;
use Domain\Post\Repositories\Contracts\IPostRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Domain\User\Services\TelegramNotificationService;

/**
 * Class PostRepository.
 */
class PostRepository implements IPostRepository
{
    use GlobalFunc;

    /**
     * Constructor of PostController.
     */
    public function __construct(protected TelegramNotificationService $service)
    {
        //
    }

    /**
     * Get the posts.
     * @param TableRequest $request
     * @return LengthAwarePaginator
     */
    public function getPosts(TableRequest $request) :LengthAwarePaginator
    {

        $search = $request->get('query');
        $posts = Post::query()
            ->where('status', 1)
            ->when(!empty($search), function ($query) use ($search) {
                return $query->where('title', 'like', '%' . $search . '%');
            })
            ->orderBy($request->get('column', 'id'), $request->get('sort', 'desc'))
            ->paginate($request->get('count', 25));

        return $posts->through(fn ($post) => new PostResource($post));

    }

    public function getPopularPosts(TableRequest $request) :LengthAwarePaginator
    {
        $posts = Post::query()
            ->where('status', 1)
            ->orderBy('view', 'desc')
            ->paginate($request->get('count', 15));

        return $posts->through(fn ($post) => new PostResource($post));
    }

    public function getLatestPosts(TableRequest $request) :LengthAwarePaginator
    {
        $posts = Post::query()
            ->where('status', 1)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('count', 3));

        return $posts->through(fn ($post) => new PostResource($post));
    }

     /**
     * Get the post info.
     * @param Post $post
     * @return PostResource
     */
    public function getPostInfo(Post $post) :PostResource
    {

        $post->increment('view');

        return new PostResource($post);

    }
}
