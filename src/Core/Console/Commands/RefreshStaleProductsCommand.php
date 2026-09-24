<?php

namespace Core\Console\Commands;

use Domain\Product\Jobs\RefreshStaleProductsJob;
use Domain\Product\Services\StaleProductRefreshService;
use Illuminate\Console\Command;

class RefreshStaleProductsCommand extends Command
{
    protected $signature = 'products:refresh-stale
                            {--limit= : Max products to refresh}
                            {--hours= : Treat products older than this many hours as stale}
                            {--queue : Dispatch to Horizon instead of running inline}';

    protected $description = 'Refresh the oldest stale active products via the product-scraper code endpoint';

    public function handle(StaleProductRefreshService $service): int
    {
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $hours = $this->option('hours') !== null ? (int) $this->option('hours') : null;

        if ($this->option('queue')) {
            RefreshStaleProductsJob::dispatch($limit, $hours);
            $this->info('RefreshStaleProductsJob queued.');

            return self::SUCCESS;
        }

        $result = $service->refresh($limit, $hours);

        $this->info($result->message);
        $this->table(
            ['product_id', 'code', 'brand_id', 'status', 'reason', 'action'],
            array_map(
                static fn (array $row) => [
                    $row['product_id'],
                    $row['code'] ?? '',
                    $row['brand_id'] ?? '',
                    $row['status'],
                    $row['reason'] ?? '',
                    $row['action'] ?? '',
                ],
                $result->toArray()['outcomes'],
            ),
        );

        return self::SUCCESS;
    }
}
