<?php

namespace Domain\Product\Jobs;

use Domain\Product\Services\StaleProductRefreshService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshProductOnCartJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout;

    /**
     * Max seconds the unique lock may be held if a worker dies mid-job.
     */
    public int $uniqueFor;

    public function __construct(
        public readonly int $productId,
    ) {
        $timeout = (int) config('product_scraper.timeout', 180);
        $this->timeout = $timeout;
        $this->uniqueFor = $timeout;
    }

    public function uniqueId(): string
    {
        return (string) $this->productId;
    }

    public function handle(StaleProductRefreshService $service): void
    {
        $startedAt = microtime(true);

        Log::info('RefreshProductOnCartJob: started', [
            'product_id' => $this->productId,
        ]);

        $outcome = $service->refreshById($this->productId);

        if ($outcome === null) {
            return;
        }

        Log::info('RefreshProductOnCartJob: finished', [
            ...$outcome->toArray(),
            'duration_seconds' => round(microtime(true) - $startedAt, 2),
        ]);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('RefreshProductOnCartJob: permanently failed', [
            'product_id' => $this->productId,
            'error' => $exception?->getMessage(),
            'exception' => $exception ? $exception::class : null,
        ]);
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return [
            'products',
            'scraper',
            'cart-refresh',
            'product:'.$this->productId,
        ];
    }
}
