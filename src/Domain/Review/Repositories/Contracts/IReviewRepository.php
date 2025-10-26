<?php

namespace Domain\Review\Repositories\Contracts;

use Application\Api\Review\Requests\ReviewRequest;
use Application\Api\Review\Resources\ReviewResource;
use Core\Http\Requests\TableRequest;
use Domain\Product\Models\Product;
use Domain\Review\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface IReviewRepository.
 */
interface IReviewRepository
{
    /**
     * Get my reviews with pagination
     * @param TableRequest $request
     * @return LengthAwarePaginator
     */
    public function myReviews(TableRequest $request) :LengthAwarePaginator;

    /**
     * Get the review.
     * @param Review $review
     * @return ReviewResource
     */
    public function show(Review $review) :ReviewResource;

    /**
     * Get the review per product.
     * @param TableRequest $request
     * @param Product $product
     * @return LengthAwarePaginator
     */
    public function getReviewsPerProduct(TableRequest $request, Product $product) :LengthAwarePaginator;

    /**
     * Store the review.
     * @param Product $product
     * @param ReviewRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function store(Product $product, ReviewRequest $request) :JsonResponse;

    /**
     * Update the review.
     * @param ReviewRequest $request
     * @param Review $review
     * @return JsonResponse
     * @throws \Exception
     */
    public function update(ReviewRequest $request, Review $review) :JsonResponse;

    /**
     * Change the review status.
     * @param Review $review
     * @return JsonResponse
     */
    public function changeStatus(Review $review) :JsonResponse;

    /**
     * Like the review.
     * @param Review $review
     * @return array
     */
    public function likeReview(Review $review) :array;
}
