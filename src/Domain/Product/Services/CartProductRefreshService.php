<?php

namespace Domain\Product\Services;

use Domain\Product\Jobs\RefreshProductOnCartJob;
use Domain\Product\Models\Product;
use Illuminate\Support\Facades\Log;

class CartProductRefreshService
{
    public function isEligible(Product $product): bool
    {
        if (! $product->active) {
            return false;
        }

        if ($product->status !== Product::COMPLETED) {
            return false;
        }

        $minAmount = (int) config('product.min_order_amount', 50000);
        if ((int) $product->amount < $minAmount) {
            return false;
        }

        $staleHours = (int) config('product_scraper.stale_refresh.hours', 48);
        $staleBefore = now()->subHours($staleHours);
        if ($product->updated_at === null || $product->updated_at->greaterThanOrEqualTo($staleBefore)) {
            return false;
        }

        $code = trim((string) $product->code);
        $brandId = (int) $product->brand_id;
        if ($code === '' || $brandId < 1) {
            return false;
        }

        return true;
    }

    /**
     * Dispatch a single-product refresh job when eligible.
     * Duplicates while pending/running are blocked by RefreshProductOnCartJob::ShouldBeUnique.
     *
     * @return bool True when a job was dispatched (caller may still be unique-deduped by the queue).
     */
    public function dispatchIfEligible(Product $product): bool
    {
        if (! $this->isEligible($product)) {
            Log::debug('CartProductRefreshService: skipped (not eligible)', [
                'product_id' => $product->id,
            ]);

            return false;
        }

        return $this->queueRefresh($product, forced: false);
    }

    /**
     * Force-queue a scraper refresh by product id (resolved to code + brand in the job).
     * Used by admin actions; skips cart eligibility gates.
     *
     * @return bool False when code or brand_id is missing.
     */
    public function queueRefresh(Product $product, bool $forced = true): bool
    {
        $code = trim((string) $product->code);
        $brandId = (int) $product->brand_id;

        if ($code === '' || $brandId < 1) {
            Log::debug('CartProductRefreshService: skipped (missing code or brand)', [
                'product_id' => $product->id,
            ]);

            return false;
        }

        $queue = (string) config('product_scraper.cart_refresh.queue', 'high');

        RefreshProductOnCartJob::dispatch((int) $product->id)
            ->onQueue($queue);

        Log::info('CartProductRefreshService: queued', [
            'product_id' => $product->id,
            'queue' => $queue,
            'forced' => $forced,
        ]);

        return true;
    }
}
