<?php

namespace Application\Api\Review\Controllers;

use Application\Api\Review\Requests\ReviewRequest;
use Core\Http\Controllers\Controller;
use Core\Http\Requests\TableRequest;
use Domain\Product\Models\Product;
use Domain\Review\Models\Review;
use Domain\Review\Repositories\Contracts\IReviewRepository;
use Domain\User\Models\User;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;


class ReviewController extends Controller
{

    /**
     * @param IReviewRepository $repository
     */
    public function __construct(protected IReviewRepository $repository)
    {

    }

    /**
     * Get my reviews with pagination
     * @param TableRequest $request
     * @return JsonResponse
     */
    public function myReviews(TableRequest $request): JsonResponse
    {
        return response()->json($this->repository->myReviews($request), Response::HTTP_OK);
    }

    /**
     * Get the reviews per product pagination.
     * @param TableRequest $request
     * @param Product $product
     * @return JsonResponse
     */
    public function getReviewsPerProduct(TableRequest $request, Product $product): JsonResponse
    {
        return response()->json($this->repository->getReviewsPerProduct($request, $product), Response::HTTP_OK);
    }

    /**
     * Get the review.
     * @param
     * @return JsonResponse
     */
    public function show(Review $review) :JsonResponse
    {
        return response()->json($this->repository->show($review), Response::HTTP_OK);
    }

    /**
     * Store the review.
     * @param Product $product
     * @param ReviewRequest $request
     * @return JsonResponse
     */
    public function store(Product $product, ReviewRequest $request) :JsonResponse
    {
        return $this->repository->store($product, $request);
    }

    /**
     * Update the review.
     * @param ReviewRequest $request
     * @param Review $review
     * @return JsonResponse
     */
    public function update(ReviewRequest $request, Review $review) :JsonResponse
    {
        return $this->repository->update($request, $review);
    }

    /**
     * Change the review status.
     * @param Review $review
     * @return JsonResponse
     */
    public function changeStatus(Review $review) :JsonResponse
    {
        return $this->repository->changeStatus($review);
    }
}
