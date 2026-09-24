<?php

namespace Domain\Product\Services;

use Domain\Product\DTO\ProductRefreshOutcome;
use Domain\Product\DTO\StaleProductRefreshResult;
use Domain\Product\Exceptions\ProductScraperException;
use Domain\Product\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class StaleProductRefreshService
{
    public function __construct(
        private readonly ProductScraperService $scraper,
    ) {}

    public function refresh(?int $limit = null, ?int $staleHours = null): StaleProductRefreshResult
    {
        $limit = $limit ?? (int) config('product_scraper.stale_refresh.limit', 10);
        $staleHours = $staleHours ?? (int) config('product_scraper.stale_refresh.hours', 48);
        $staleBefore = now()->subHours($staleHours);

        $products = $this->candidates($limit, $staleBefore);

        if ($products->isEmpty()) {
            $message = sprintf(
                'No stale products to refresh (active=1, status=completed, updated_at older than %d hours).',
                $staleHours,
            );

            Log::info('StaleProductRefreshService: empty', [
                'stale_hours' => $staleHours,
                'limit' => $limit,
                'stale_before' => $staleBefore->toDateTimeString(),
            ]);

            return StaleProductRefreshResult::empty($message);
        }

        $outcomes = [];
        $succeeded = 0;
        $failed = 0;
        $skipped = 0;
        $delaySeconds = max(0, (int) config('product_scraper.stale_refresh.delay_seconds', 3));
        $remaining = $products->count();

        foreach ($products as $product) {
            $outcome = $this->refreshOne($product);
            $outcomes[] = $outcome;

            match ($outcome->status) {
                ProductRefreshOutcome::STATUS_SUCCESS => $succeeded++,
                ProductRefreshOutcome::STATUS_SKIPPED => $skipped++,
                default => $failed++,
            };

            $remaining--;
            if ($remaining > 0 && $delaySeconds > 0) {
                $this->pauseBetweenRequests($delaySeconds);
            }
        }

        $result = new StaleProductRefreshResult(
            selected: $products->count(),
            succeeded: $succeeded,
            failed: $failed,
            skipped: $skipped,
            outcomes: $outcomes,
            message: sprintf(
                'Refreshed %d product(s): %d succeeded, %d failed, %d skipped.',
                $products->count(),
                $succeeded,
                $failed,
                $skipped,
            ),
        );

        Log::info('StaleProductRefreshService: finished', $result->toLogContext());

        return $result;
    }

    /**
     * @return Collection<int, Product>
     */
    public function candidates(int $limit, Carbon $staleBefore): Collection
    {
        return Product::query()
            ->staleForRefresh($staleBefore)
            ->orderBy('updated_at')
            ->limit($limit)
            ->get(['id', 'code', 'brand_id', 'title', 'updated_at', 'active', 'status']);
    }

    public function refreshOne(Product $product): ProductRefreshOutcome
    {
        $code = trim((string) $product->code);
        $brandId = $product->brand_id !== null ? (int) $product->brand_id : null;

        if ($code === '' || $brandId === null || $brandId < 1) {
            $outcome = new ProductRefreshOutcome(
                productId: (int) $product->id,
                code: $code !== '' ? $code : null,
                brandId: $brandId,
                status: ProductRefreshOutcome::STATUS_SKIPPED,
                reason: 'Product is missing a code or brand_id.',
            );

            Log::warning('StaleProductRefreshService: skipped', $outcome->toArray());

            return $outcome;
        }

        try {
            $response = $this->scraper->apply(
                $this->scraper->payload(null, $code, $brandId, null)
            );

            $outcome = new ProductRefreshOutcome(
                productId: (int) $product->id,
                code: $code,
                brandId: $brandId,
                status: ProductRefreshOutcome::STATUS_SUCCESS,
                action: is_array($response) ? ($response['action'] ?? null) : null,
            );

            Log::info('StaleProductRefreshService: success', $outcome->toArray());

            return $outcome;
        } catch (ProductScraperException $exception) {
            $outcome = new ProductRefreshOutcome(
                productId: (int) $product->id,
                code: $code,
                brandId: $brandId,
                status: ProductRefreshOutcome::STATUS_FAILED,
                reason: $exception->getMessage(),
            );

            Log::warning('StaleProductRefreshService: failed', $outcome->toArray());

            return $outcome;
        } catch (Throwable $exception) {
            $outcome = new ProductRefreshOutcome(
                productId: (int) $product->id,
                code: $code,
                brandId: $brandId,
                status: ProductRefreshOutcome::STATUS_FAILED,
                reason: $exception->getMessage(),
            );

            Log::error('StaleProductRefreshService: unexpected failure', [
                ...$outcome->toArray(),
                'exception' => $exception::class,
            ]);

            return $outcome;
        }
    }

    public function refreshById(int $productId): ?ProductRefreshOutcome
    {
        $product = Product::query()->find($productId);

        if ($product === null) {
            Log::warning('StaleProductRefreshService: product not found', [
                'product_id' => $productId,
            ]);

            return null;
        }

        return $this->refreshOne($product);
    }

    /**
     * Wait between scraper calls. Extracted so unit tests can assert without sleeping.
     */
    protected function pauseBetweenRequests(int $seconds): void
    {
        sleep($seconds);
    }
}
