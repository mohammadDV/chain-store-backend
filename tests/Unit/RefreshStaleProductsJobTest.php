<?php

namespace Tests\Unit;

use Domain\Product\DTO\StaleProductRefreshResult;
use Domain\Product\Jobs\RefreshStaleProductsJob;
use Domain\Product\Services\StaleProductRefreshService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class RefreshStaleProductsJobTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_handle_delegates_to_service(): void
    {
        $service = Mockery::mock(StaleProductRefreshService::class);
        $service->shouldReceive('refresh')
            ->once()
            ->with(3, 2)
            ->andReturn(StaleProductRefreshResult::empty('No stale products to refresh'));

        (new RefreshStaleProductsJob(limit: 3, staleDays: 2))->handle($service);
    }

    public function test_job_exposes_horizon_tags_and_limits(): void
    {
        $job = new RefreshStaleProductsJob;

        $this->assertSame(['products', 'scraper', 'stale-refresh'], $job->tags());
        $this->assertSame(1, $job->tries);
        $this->assertSame(1200, $job->timeout);
    }
}
