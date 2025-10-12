<?php

namespace Domain\Review\Repositories;

use Application\Api\Review\Requests\ReviewRequest;
use Application\Api\Review\Resources\ReviewResource;
use Core\Http\Requests\TableRequest;
use Core\Http\traits\GlobalFunc;
use Domain\Product\Models\Product;
use Domain\Product\Models\ServiceVote;
use Domain\Notification\Services\NotificationService;
use Domain\Review\Models\Review;
use Domain\Review\Repositories\Contracts\IReviewRepository;
use Domain\User\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Class ReviewRepository.
 */
class ReviewRepository implements IReviewRepository
{
    use GlobalFunc;

    /**
     * Get my reviews with pagination
     * @param TableRequest $request
     * @return LengthAwarePaginator
     */
    public function myReviews(TableRequest $request) :LengthAwarePaginator
    {

        $search = $request->get('query');
        return Review::query()
            ->with('user:id,nickname,profile_photo_path,rate')
            ->withCount('likes')
            ->withCount('dislikes')
            ->when(!empty($search), function ($query) use ($search) {
                return $query->where('comment', 'like', '%' . $search . '%');
            })
            ->orderBy($request->get('column', 'id'), $request->get('sort', 'desc'))
            ->paginate($request->get('count', 25));
    }

    /**
     * Get the review.
     * @param Review $review
     * @return ReviewResource
     */
    public function show(Review $review) :ReviewResource
    {
        $this->checkLevelAccess(Auth::user()->level == 3);

        $review = Review::query()
                ->where('id', $review->id)
                ->first();

        return new ReviewResource($review);
    }

    /**
     * Get the review per product.
     * @param TableRequest $request
     * @param Product $product
     * @return LengthAwarePaginator
     */
    public function getReviewsPerProduct(TableRequest $request, Product $product) :LengthAwarePaginator
    {
        $reviews = Review::query()
            ->with('user:id,nickname,profile_photo_path,rate', 'services', 'files')
            ->withCount('likes')
            ->withCount('dislikes')
            ->where('product_id', $product->id)
            ->where('status', Review::APPROVED)
            ->where('active', 1)
            ->orderBy($request->get('column', 'id'), $request->get('sort', 'desc'))
            ->paginate($request->get('count', 25));

        return $reviews->through(fn ($review) => new ReviewResource($review));;
    }

    /**
     * Store the review.
     * @param Product $product
     * @param ReviewRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function store(Product $product, ReviewRequest $request) :JsonResponse
    {
        if (empty(Auth::user()->status)) {
            return response()->json([
                'status' => 0,
                'message' => __('site.Your account is not active yet. Please send a message to the admin from ticket section.'),
            ], Response::HTTP_BAD_REQUEST);
        }

        // if (empty(Auth::user()->verified_at)) {
        //     return response()->json([
        //         'status' => 0,
        //         'message' => __('site.You must verify your account to create a review'),
        //     ], Response::HTTP_BAD_REQUEST);
        // }

        // Check for duplicate review
        $duplicate = Review::query()
            ->where('product_id', $product->id)
            ->where('user_id', Auth::id())
            ->exists();

        if ($duplicate) {
            return response()->json([
                'status' => 0,
                'message' => __('site.Duplicate review error'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            DB::beginTransaction();

            $review = Review::create([
                'comment' => $request->input('comment'),
                'rate' => $request->input('rate'),
                'product_id' => $product->id,
                'user_id' => Auth::id(),
                'status' => Review::PENDING,
            ]);

            // Create files if provided
            if ($request->has('files')) {
                foreach ($request->input('files') as $fileData) {
                    $review->files()->create($fileData);
                }
            }

            if ($request->has('services')) {
                foreach ($request->input('services') as $service) {
                    ServiceVote::create([
                        'review_id' => $review->id,
                        'service_id' => $service,
                        'product_id' => $product->id,
                    ]);
                }
            }

            // Calculate average rate from all reviews for this product
            $averageRate = Review::where('product_id', $product->id)
                ->avg('rate');

            // Update product with average rate (rounded to nearest integer)
            $product->update([
                'rate' => round($averageRate)
            ]);

            NotificationService::create([
                'title' => __('site.new_review_title'),
                'content' => __('site.new_review_content', ['user_nickname' => Auth::user()->nickname]),
                'id' => $product->id,
                'type' => NotificationService::PRODUCT,
            ], $product->user);

            DB::commit();

            return response()->json([
                'status' => 1,
                'message' => __('site.The operation has been successfully'),
                'data' => new ReviewResource($review)
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update the review.
     * @param ReviewRequest $request
     * @param Review $review
     * @return JsonResponse
     * @throws \Exception
     */
    public function update(ReviewRequest $request, Review $review) :JsonResponse
    {
        $review->update([
            'comment' => $request->input('comment'),
            'rate' => $request->input('rate'),
            'status' => $request->input('status') ?? Review::PENDING,
        ]);

        if ($request->has('files')) {
            $this->updateReviewFiles($review, $request->input('files'));
        }

        // Update product rates after review update
        $product = $review->product;
        $averageRate = Review::where('product_id', $product->id)
            ->avg('rate');

        if ($request->input('services')) {
            $this->updateReviewServices($review, $request->input('services'));
        }

        $product->update([
            'rate' => round($averageRate)
        ]);

        if ($review) {
            return response()->json([
                'status' => 1,
                'message' => __('site.The operation has been successfully')
            ], Response::HTTP_OK);
        }

        throw new \Exception();
    }

    /**
     * Change the review status.
     * @param Review $review
     * @return JsonResponse
     */
    public function changeStatus(Review $review) :JsonResponse
    {
        $this->checkLevelAccess(Auth::user()->id == $review->user_id);

        $review->update([
            'status' => in_array($review->status, [Review::PENDING, Review::APPROVED]) ? Review::CANCELLED : Review::PENDING
        ]);

        $review->refresh();

        return response()->json([
            'status_review' => $review->status,
            'status' => 1,
            'message' => __('site.The operation has been successfully')
        ], Response::HTTP_OK);
    }

    /**
     * Update review services.
     * @param Review $review
     * @param array $services
     */
    private function updateReviewServices(Review $review, array $services): void
    {
        // Delete existing service votes for this review
        ServiceVote::where('review_id', $review->id)->delete();

        // Create new service votes
        foreach ($services as $service) {
            ServiceVote::create([
                'review_id' => $review->id,
                'service_id' => $service,
                'product_id' => $review->product_id,
            ]);
        }
    }

    /**
     * Update review files intelligently
     * @param Review $review
     * @param array $filesData
     */
    private function updateReviewFiles(Review $review, array $filesData): void
    {
        // Get existing files
        $existingFiles = $review->files()->pluck('id', 'path')->toArray();

        // Process new files
        foreach ($filesData as $fileData) {
            $path = $fileData['path'];

            if (isset($existingFiles[$path])) {
                // File exists, update it if needed
                $fileId = $existingFiles[$path];
                $review->files()->where('id', $fileId)->update([
                    'type' => $fileData['type'] ?? 'image',
                    'status' => $fileData['status'] ?? 1
                ]);
                // Remove from existing files so we don't delete it
                unset($existingFiles[$path]);
            } else {
                // Create new file
                $review->files()->create($fileData);
            }
        }

        // Delete files that are no longer in the request
        if (!empty($existingFiles)) {
            $review->files()->whereIn('id', array_values($existingFiles))->delete();
        }
    }
}