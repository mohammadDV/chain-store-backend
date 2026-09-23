<?php

namespace Application\Api\Review\Controllers;

use Application\Api\Review\Requests\ReviewRequest;
use Core\Http\Controllers\Controller;
use Core\Http\Requests\TableRequest;
use Domain\Product\Models\Product;
use Domain\Review\Models\Review;
use Domain\Review\Repositories\Contracts\IReviewRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ReviewController extends Controller
{
    public function __construct(protected IReviewRepository $repository) {}

    /**
     * Get my reviews with pagination
     */
    public function myReviews(TableRequest $request): JsonResponse
    {
        return response()->json($this->repository->myReviews($request), Response::HTTP_OK);
    }

    /**
     * Get the reviews per product pagination.
     */
    public function getReviewsPerProduct(TableRequest $request, Product $product): JsonResponse
    {
        return response()->json($this->repository->getReviewsPerProduct($request, $product), Response::HTTP_OK);
    }

    /**
     * Get the review.
     */
    public function show(Review $review): JsonResponse
    {
        return response()->json($this->repository->show($review), Response::HTTP_OK);
    }

    /**
     * Store the review.
     */
    public function store(Product $product, ReviewRequest $request): JsonResponse
    {
        return $this->repository->store($product, $request);
    }

    /**
     * Update the review.
     */
    public function update(ReviewRequest $request, Review $review): JsonResponse
    {
        return $this->repository->update($request, $review);
    }

    /**
     * Change the review status.
     */
    public function changeStatus(Review $review): JsonResponse
    {
        return $this->repository->changeStatus($review);
    }

    /**
     * Like the review.
     */
    public function likeReview(Review $review): JsonResponse
    {
        return response()->json($this->repository->likeReview($review), Response::HTTP_OK);
    }
}
