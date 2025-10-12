<?php

namespace Domain\Product\Repositories;

use Application\Api\Product\Requests\ProductRequest;
use Application\Api\Product\Resources\ProductResource;
use Core\Http\Requests\TableRequest;
use Core\Http\traits\GlobalFunc;
use Domain\Product\Models\Product;
use Domain\Product\Repositories\Contracts\IProductRepository;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Application\Api\Product\Requests\SearchProductRequest;
use Application\Api\Product\Resources\ProductBoxResource;
use DateTimeZone;
use Domain\Product\Models\Category;
use Domain\Product\Models\Favorite;
use Domain\Product\Models\ServiceVote;
use Domain\Product\Models\Weekend;
use Domain\Notification\Services\NotificationService;
use Domain\Review\Models\Review;
use Domain\User\Services\TelegramNotificationService;
use Illuminate\Support\Facades\DB;

/**
 * Class ProductRepository.
 */
class ProductRepository implements IProductRepository
{
    use GlobalFunc;

    public function __construct(protected TelegramNotificationService $service)
    {
        //
    }

    /**
     * Get the products pagination.
     * @param TableRequest $request
     * @return LengthAwarePaginator
     */
    public function index(TableRequest $request) :LengthAwarePaginator
    {
        $search = $request->get('query');
        $status = $request->get('status');
        $products = Product::query()
            ->with(['area', 'tags'])
            ->when(Auth::user()->level != 3, function ($query) {
                return $query->where('user_id', Auth::user()->id);
            })
            ->when(!empty($search), function ($query) use ($search) {
                return $query->where('title', 'like', '%' . $search . '%');
            })
            ->when(!empty($status), function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->orderBy($request->get('column', 'id'), $request->get('sort', 'desc'))
            ->paginate($request->get('count', 25));

        return $products->through(fn ($product) => new ProductBoxResource($product));
    }

    /**
     * Get the product.
     * @param Product $product
     * @return array
     */
    public function show(Product $product) :array
    {
        $product = Product::query()
                ->with([
                    'categories:id,title',
                    'country:id,title',
                    'city:id,title',
                    'area.city.country',
                    'tags',
                    'facilities:id,title',
                    'filters:id,title',
                    'files',
                ])
                ->where('id', $product->id)
                ->first();


        // votes and quantity services
        $services = $this->getServiceVotes($product?->id);

        $reviews = $this->getReviewsByRate($product->id);

        return [
            'product' => new ProductResource($product),
            'quality_services' => $services,
            'reviews' => $reviews,
        ];

    }

    /**
     * Edit the product.
     * @param Product $product
     * @return Product
     */
    public function edit(Product $product) :Product
    {
        $this->checkLevelAccess(Auth::user()->id == $product->user_id);

        return Product::query()
                ->with([
                    'categories:id,title',
                    'area.city.country',
                    'tags',
                    'facilities:id,title',
                    'filters:id,title',
                    'files',
                ])
                ->where('id', $product->id)
                ->first();

    }

    /**
     * Store the product.
     * @param ProductRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function store(ProductRequest $request) :JsonResponse
    {
        // if (empty(Auth::user()->status)) {
        //     return response()->json([
        //         'status' => 0,
        //         'message' => __('site.Your account is not active yet. Please send a message to the admin from ticket section.'),
        //     ], Response::HTTP_BAD_REQUEST);
        // }

        // if (empty(Auth::user()->verified_at)) {
        //     return response()->json([
        //         'status' => 0,
        //         'message' => __('site.You must verify your account to create a product'),
        //     ], Response::HTTP_BAD_REQUEST);
        // }

        try {
            DB::beginTransaction();

            $product = Product::create([
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'lat' => $request->input('lat'),
                'long' => $request->input('long'),
                'website' => $request->input('website'),
                'facebook' => $request->input('facebook'),
                'instagram' => $request->input('instagram'),
                'youtube' => $request->input('youtube'),
                'tiktok' => $request->input('tiktok'),
                'whatsapp' => $request->input('whatsapp'),
                'phone' => $request->input('phone'),
                'email' => $request->input('email'),
                'address' => $request->input('address'),
                'start_amount' => $request->input('start_amount'),
                'amount_type' => $request->input('amount_type', 0),
                'image' => $request->input('image'),
                'menu_image' => $request->input('menu_image'),
                'video' => $request->input('video'),
                'from_monday' => $request->input('from_monday', 0),
                'from_tuesday' => $request->input('from_tuesday', 0),
                'from_wednesday' => $request->input('from_wednesday', 0),
                'from_thursday' => $request->input('from_thursday', 0),
                'from_friday' => $request->input('from_friday', 0),
                'from_saturday' => $request->input('from_saturday', 0),
                'from_sunday' => $request->input('from_sunday', 0),
                'to_monday' => $request->input('to_monday', 0),
                'to_tuesday' => $request->input('to_tuesday', 0),
                'to_wednesday' => $request->input('to_wednesday', 0),
                'to_thursday' => $request->input('to_thursday', 1),
                'to_friday' => $request->input('to_friday', 0),
                'to_saturday' => $request->input('to_saturday', 0),
                'to_sunday' => $request->input('to_sunday', 0),
                'active' => 1,
                'status' => Product::PENDING,
                'country_id' => $request->input('country_id'),
                'city_id' => $request->input('city_id'),
                'area_id' => $request->input('area_id'),
                'user_id' => Auth::user()->id,
            ]);

            if ($product) {
                // Attach categories if provided
                if ($request->has('categories')) {
                    $product->categories()->attach($request->input('categories'));
                }

                // Create tags if provided
                if ($request->has('tags')) {
                    foreach ($request->input('tags') as $tagData) {
                        $product->tags()->create(['title' => $tagData, 'status' => 1]);
                    }
                }

                // Attach facilities if provided
                if ($request->has('facilities')) {
                    $product->facilities()->attach($request->input('facilities'));
                }

                // Attach filters if provided
                if ($request->has('filters')) {
                    $product->filters()->attach($request->input('filters'));
                }

                // Create files if provided
                if ($request->has('files')) {
                    foreach ($request->input('files') as $fileData) {
                        $product->files()->create($fileData);
                    }
                }

                NotificationService::create([
                    'title' => __('site.product_created_title'),
                    'content' => __('site.product_created_content', ['product_title' => $product->title]),
                    'id' => $product->id,
                    'type' => NotificationService::PRODUCT,
                ], $product->user);

                // $this->service->sendNotification(
                //     config('telegram.chat_id'),
                //     'ساخت آگهی جدید' . PHP_EOL .
                //     'id ' . Auth::user()->id . PHP_EOL .
                //     'nickname ' . Auth::user()->nickname . PHP_EOL .
                //     'title ' . $product->title . PHP_EOL .
                //     'time ' . now()
                // );


                DB::commit();

                return response()->json([
                    'status' => 1,
                    'message' => __('site.The operation has been successfully'),
                    'data' => new ProductResource($product)
                ], Response::HTTP_CREATED);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        throw new \Exception();
    }

    /**
     * Update the product.
     * @param ProductRequest $request
     * @param Product $product
     * @return JsonResponse
     * @throws \Exception
     */
    public function update(ProductRequest $request, Product $product) :JsonResponse
    {
        $this->checkLevelAccess(Auth::user()->id == $product->user_id);

        if (Auth::user()->level != 3 && $product->status != Product::PENDING) {
            throw New \Exception('Unauthorized', 403);
        }

        $updated = $product->update([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'lat' => $request->input('lat'),
            'long' => $request->input('long'),
            'website' => $request->input('website'),
            'facebook' => $request->input('facebook'),
            'instagram' => $request->input('instagram'),
            'youtube' => $request->input('youtube'),
            'tiktok' => $request->input('tiktok'),
            'whatsapp' => $request->input('whatsapp'),
            'phone' => $request->input('phone'),
            'email' => $request->input('email'),
            'address' => $request->input('address'),
            'start_amount' => $request->input('start_amount'),
            'amount_type' => $request->input('amount_type'),
            'image' => $request->input('image'),
            'menu_image' => $request->input('menu_image'),
            'video' => $request->input('video'),
            'from_monday' => $request->input('from_monday'),
            'from_tuesday' => $request->input('from_tuesday'),
            'from_wednesday' => $request->input('from_wednesday'),
            'from_thursday' => $request->input('from_thursday'),
            'from_friday' => $request->input('from_friday'),
            'from_saturday' => $request->input('from_saturday'),
            'from_sunday' => $request->input('from_sunday'),
            'to_monday' => $request->input('to_monday'),
            'to_tuesday' => $request->input('to_tuesday'),
            'to_wednesday' => $request->input('to_wednesday'),
            'to_thursday' => $request->input('to_thursday'),
            'to_friday' => $request->input('to_friday'),
            'to_saturday' => $request->input('to_saturday'),
            'to_sunday' => $request->input('to_sunday'),
            'country_id' => $request->input('country_id'),
            'city_id' => $request->input('city_id'),
            'area_id' => $request->input('area_id'),
        ]);

        if ($updated) {
            // Sync categories if provided
            if ($request->has('categories')) {
                $product->categories()->sync($request->input('categories'));
            }

            // Handle tags if provided (one-to-many relationship)
            if ($request->has('tags')) {
                $this->updateProductTags($product, $request->input('tags'));
            } else {
                // If tags not provided, keep existing tags (don't delete them)
            }

            // Handle files if provided (one-to-many relationship)
            if ($request->has('files')) {
                $this->updateProductFiles($product, $request->input('files'));
            } else {
                // If files not provided, keep existing files (don't delete them)
            }

            // Sync facilities if provided
            if ($request->has('facilities')) {
                $product->facilities()->sync($request->input('facilities'));
            }

            // Sync filters if provided
            if ($request->has('filters')) {
                $product->filters()->sync($request->input('filters'));
            }

            return response()->json([
                'status' => 1,
                'message' => __('site.The operation has been successfully'),
                'data' => new ProductResource($product)
            ], Response::HTTP_OK);
        }

        throw new \Exception();
    }

    /**
     * Favorite the product.
     * @param Product $product
     * @return JsonResponse
     */
    public function favorite(Product $product) :JsonResponse
    {
        $favorite = Favorite::query()
            ->where('favoritable_id', $product->id)
            ->where('favoritable_type', Product::class)
            ->where('user_id', Auth::user()->id)
            ->first();

        $active = 0;

        if ($favorite) {
            $favorite->delete();
        } else {
            $favorite = Favorite::create([
                'favoritable_id' => $product->id,
                'favoritable_type' => Product::class,
                'user_id' => Auth::user()->id,
            ]);
            $active = 1;
        }

        return response()->json([
            'status' => 1,
            'message' => __('site.The operation has been successfully'),
            'favorite' => $active,
        ], Response::HTTP_OK);
    }

    /**
     * Get favorite products.
     * @param TableRequest $request
     * @return LengthAwarePaginator
     */
    public function getFavoriteProducts(TableRequest $request): LengthAwarePaginator
    {
        $search = $request->get('query');
        $products = Product::query()
            ->with(['area', 'tags'])
            ->whereHas('favorites', function ($query) {
                $query->where('favorites.user_id', Auth::user()->id);
            })
            ->when(!empty($search), function ($query) use ($search) {
                return $query->where('title', 'like', '%' . $search . '%');
            })
            ->orderBy($request->get('column', 'id'), $request->get('sort', 'desc'))
            ->paginate($request->get('count', 25));

        return $products->through(fn ($product) => new ProductBoxResource($product));
    }

    /**
     * Get similar products.
     * @param Product $product
     */
    public function similarProducts(Product $product)
    {
        $similarProducts = Product::query()
            ->with(['area', 'tags'])
            ->where('active', 1)
            ->where('status', Product::APPROVED)
            ->whereHas('categories', function ($query) use ($product) {
                $query->whereIn('categories.id', $product->categories->pluck('id'));
            })
            ->where('id', '!=', $product->id)
            ->limit(4)
            ->get();

        return $similarProducts->map(fn ($product) => new ProductBoxResource($product));
    }

    /**
     * Get featured products by type with configurable limits.
     * @return array{sender: Collection, passenger: Collection}
     */
    public function getFeaturedProducts(): array
    {

        $products = Product::query()
            ->select('id', 'title', 'amount_type', 'start_amount', 'rate', 'lat', 'long', 'image', 'area_id')
            ->with(['area', 'tags'])
            ->where('active', 1)
            ->where('status', Product::APPROVED)
            ->orderBy('priority', 'desc')
            ->limit(config('product.limit'))
            ->get()
            ->map(fn ($product) => new ProductBoxResource($product));

        $weekends = Product::query()
            ->select('id', 'title', 'amount_type', 'start_amount', 'rate', 'lat', 'long', 'image', 'area_id')
            ->with(['area', 'tags'])
            ->whereHas('weekends', function ($query) {
                $query->where('weekends.status', 1);
            })
            ->where('active', 1)
            ->where('status', Product::APPROVED)
            ->inRandomOrder() // Replace orderBy with this
            ->limit(config('product.weekend_limit'))
            ->get()
            ->map(fn ($product) => new ProductBoxResource($product));

        return [
                'offers' => $products,
                'weekends' => $weekends
        ];
    }

    /**
     * Get weekends.
     * @return array
     */
    public function getWeekends(): array
    {

        $weekends = Weekend::query()->where('status', 1)->get();

        $result = [];

        foreach ($weekends as $weekend) {
            $result[$weekend->id]['title'] = $weekend->title;
            $result[$weekend->id]['products'] = $weekend->products()->with(['area', 'tags'])->where('status', Product::APPROVED)->get()->map(fn ($product) => new ProductBoxResource($product));
        }

        return $result;
    }

    /**
     * Search suggestions with filters and pagination.
     * @param TableRequest $request
     */
    public function searchSuggestions(TableRequest $request)
    {

        $search = $request->get('query');

        $categories = Category::query()
            ->with(['filters' => function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%');
            }])
            ->where(function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhereHas('filters', function ($q) use ($search) {
                        $q->where('title', 'like', '%' . $search . '%');
                    });
            })
            ->where('status', 1)
            ->limit(10)
            ->get();

        $queryProduct = Product::query()
            ->with(['area', 'tags'])
            ->where('active', 1)
            ->where('status', Product::APPROVED)
            ->where(function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhereHas('filters', function ($q) use ($search) {
                        $q->where('title', 'like', '%' . $search . '%');
                    });
            });

        $products = $queryProduct->orderBy($request->get('column', 'id'), $request->get('sort', 'desc'))
            ->limit(5)
            ->get();


        return [
            'products' => $products->map(fn ($product) => new ProductBoxResource($product)),
            'categories' => $categories,
        ];

    }

    /**
     * Search products with filters and pagination.
     * @param SearchProductRequest $request
     * @return LengthAwarePaginator
     */
    public function search(SearchProductRequest $request): LengthAwarePaginator
    {

        $search = $request->get('query');
        $catId = $request->get('category');
        $filters = $request->get('filters');
        $amountType = $request->get('amount_type');
        $now = $request->get('now');
        $lat = $request->get('lat');
        $long = $request->get('long');
        $areaId = $request->get('area_id');

        // Get current hour and day of week for product open filtering
        $currentDateTime = now();
        $currentHour = intval($currentDateTime->setTimezone(new DateTimeZone('Asia/Istanbul'))->format('H'));
        $currentDayOfWeek = $currentDateTime->format('l'); // Returns English day name (Monday, Tuesday, etc.)

        // Generate a unique cache key based on all search parameters
        $cacheKey = 'product_search_' . md5(json_encode([
            'query' => $search,
            'category' => $catId,
            'filters' => $filters,
            'amount_type' => $amountType,
            'now' => $now,
            'lat' => $lat,
            'long' => $long,
            'area_id' => $areaId,
            'page' => $request->input('page', 1),
        ]));

        // Try to get results from cache first
        // return cache()->remember($cacheKey, now()->addMinutes(5), function () use ($request, $today) {
            $query = Product::query()
                ->with(['area', 'tags'])
                ->where('active', 1)
                ->where('status', Product::APPROVED);

            // Apply title
            if (!empty($search)) {
                $query->where('title','like', '%' . $search . '%');
            }

            // amount type
            if (!empty($amountType)) {
                $query->where('amount_type', $amountType);
            }

            // category
            if (!empty($catId)) {
                $query->whereHas('categories', function ($q) use ($catId) {
                    $q->where('categories.id', $catId)
                      ->orWhere('categories.parent_id', $catId);
                });
            }

            // filters
            if (!empty($filters)) {
                $query->whereHas('filters', function ($q) use ($filters) {
                    $q->whereIn('filter_id', $filters);
                }, '=', count($filters));
            }

            // now
            if (!empty($now)) {
                $query->where('from_' . strtolower($currentDayOfWeek), '<=', $currentHour)
                    ->where('to_' . strtolower($currentDayOfWeek), '>=', $currentHour);
            }

            // near me
            if (!empty($lat) && !empty($long)) {
                // Filter products within 5 kilometers radius using a simpler approach
                $radius = 2; // kilometers

                // Use a bounding box approach for better performance and compatibility
                $latMin = $lat - ($radius / 111.32); // 1 degree = ~111.32 km
                $latMax = $lat + ($radius / 111.32);
                $longMin = $long - ($radius / (111.32 * cos(deg2rad($lat))));
                $longMax = $long + ($radius / (111.32 * cos(deg2rad($lat))));

                $query->whereBetween('lat', [$latMin, $latMax])
                      ->whereBetween('long', [$longMin, $longMax]);
            }

            // area
            if (!empty($areaId)) {
                $query->where('area_id', $areaId);
            }


            $products = $query->orderBy($request->get('column', 'id'), $request->get('sort', 'desc'))
                ->paginate($request->get('count', 25));

            return $products->through(fn ($product) => new ProductBoxResource($product));
        // });
    }

    /**
     * Update product tags intelligently
     * @param Product $product
     * @param array $tagsData
     */
    private function updateProductTags(Product $product, array $tagsData): void
    {
        // Get existing tags
        $existingTags = $product->tags()->pluck('id', 'title')->toArray();

        // Process new tags
        foreach ($tagsData as $tagData) {
            $title = $tagData;

            if (isset($existingTags[$title])) {
                // Tag exists, update it if needed
                $tagId = $existingTags[$title];
                $product->tags()->where('id', $tagId)->update([
                    'status' => $tagData['status'] ?? 1
                ]);
                // Remove from existing tags so we don't delete it
                unset($existingTags[$title]);
            } else {
                // Create new tag
                $product->tags()->create(['title' => $title, 'status' => 1]);
            }
        }

        // Delete tags that are no longer in the request
        if (!empty($existingTags)) {
            $product->tags()->whereIn('id', array_values($existingTags))->delete();
        }
    }

    /**
     * Update product files intelligently
     * @param Product $product
     * @param array $filesData
     */
    private function updateProductFiles(Product $product, array $filesData): void
    {
        // Get existing files
        $existingFiles = $product->files()->pluck('id', 'path')->toArray();

        // Process new files
        foreach ($filesData as $fileData) {
            $path = $fileData['path'];

            if (isset($existingFiles[$path])) {
                // File exists, update it if needed
                $fileId = $existingFiles[$path];
                $product->files()->where('id', $fileId)->update([
                    'type' => $fileData['type'] ?? 'image',
                    'status' => $fileData['status'] ?? 1
                ]);
                // Remove from existing files so we don't delete it
                unset($existingFiles[$path]);
            } else {
                // Create new file
                $product->files()->create($fileData);
            }
        }

        // Delete files that are no longer in the request
        if (!empty($existingFiles)) {
            $product->files()->whereIn('id', array_values($existingFiles))->delete();
        }
    }

    /**
     * Get reviews grouped by rate with counts for products
     * @param int|null $productId Optional product ID to filter by specific product
     * @return Collection
     */
    public function getReviewsByRate(?int $productId = null): Collection
    {
        $query = Review::query()
            ->select('rate', DB::raw('COUNT(*) as count'))
            ->where('active', 1) // Only approved reviews
            ->where('status', Review::APPROVED); // Only approved reviews

        if ($productId) {
            $query->where('product_id', $productId);
        }

        $titles = [
            1 => __('site.very_bad'),
            2 => __('site.bad'),
            3 => __('site.average'),
            4 => __('site.good'),
            5 => __('site.excellent')
        ];

        return $query->groupBy('rate')
            ->orderBy('rate', 'desc')
            ->get()
            ->map(function ($item) use ($titles) {
                return [
                    'title' => $titles[$item->rate],
                    'rate' => $item->rate,
                    'count' => $item->count,
                    'percentage' => 0 // Will be calculated below
                ];
            })
            ->map(function ($item, $index) use ($productId) {
                // Calculate percentage based on total reviews
                $totalReviews = Review::query()
                    ->where('status', 1)
                    ->when($productId ?? false, function ($query) use ($productId) {
                        return $query->where('product_id', $productId);
                    })
                    ->count();

                $item['percentage'] = $totalReviews > 0 ? round(($item['count'] / $totalReviews) * 100, 2) : 0;
                return $item;
            });
    }

    public function getServiceVotes(?int $productId = null): Collection
    {
        $query = ServiceVote::query()
            ->where('product_id', $productId)
            ->select('service_id', DB::raw('COUNT(*) as count'));

        return $query->groupBy('service_id')
            ->orderBy('service_id', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'title' => $item->service->title,
                    'count' => $item->count,
                ];
            });
    }
}
