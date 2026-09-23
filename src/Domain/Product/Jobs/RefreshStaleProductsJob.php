<?php

namespace Domain\Product\Jobs;

use Domain\Product\Services\StaleProductRefreshService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshStaleProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Do not retry the whole batch; successful products already got a fresh updated_at.
     */
    public int $tries = 1;

    /**
     * Allow enough time for up to N sequential scraper /apply calls.
     */
    public int $timeout = 1200;

    public function __construct(
        public readonly ?int $limit = null,
        public readonly ?int $staleDays = null,
    ) {}

    public function handle(StaleProductRefreshService $service): void
    {
        $startedAt = microtime(true);

        Log::info('RefreshStaleProductsJob: started', [
            'limit' => $this->limit,
            'stale_days' => $this->staleDays,
        ]);

        $result = $service->refresh($this->limit, $this->staleDays);

        Log::info('RefreshStaleProductsJob: finished', [
            ...$result->toLogContext(),
            'duration_seconds' => round(microtime(true) - $startedAt, 2),
        ]);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('RefreshStaleProductsJob: permanently failed', [
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
            'stale-refresh',
        ];
    }
}
