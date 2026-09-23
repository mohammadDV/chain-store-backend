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
     */
    public function myReviews(TableRequest $request): LengthAwarePaginator;

    /**
     * Get the review.
     */
    public function show(Review $review): ReviewResource;

    /**
     * Get the review per product.
     */
    public function getReviewsPerProduct(TableRequest $request, Product $product): LengthAwarePaginator;

    /**
     * Store the review.
     *
     * @throws \Exception
     */
    public function store(Product $product, ReviewRequest $request): JsonResponse;

    /**
     * Update the review.
     *
     * @throws \Exception
     */
    public function update(ReviewRequest $request, Review $review): JsonResponse;

    /**
     * Change the review status.
     */
    public function changeStatus(Review $review): JsonResponse;

    /**
     * Like the review.
     */
    public function likeReview(Review $review): array;
}
