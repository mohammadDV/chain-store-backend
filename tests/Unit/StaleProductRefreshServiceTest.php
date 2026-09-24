<?php

namespace Tests\Unit;

use Domain\Product\DTO\ProductRefreshOutcome;
use Domain\Product\DTO\StaleProductRefreshResult;
use Domain\Product\Exceptions\ProductScraperException;
use Domain\Product\Models\Product;
use Domain\Product\Services\ProductScraperService;
use Domain\Product\Services\StaleProductRefreshService;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class StaleProductRefreshServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('product_scraper.stale_refresh.delay_seconds', 0);
    }

    public function test_refresh_returns_empty_result_without_calling_scraper(): void
    {
        $scraper = Mockery::mock(ProductScraperService::class);
        $scraper->shouldNotReceive('apply');
        $scraper->shouldNotReceive('payload');

        $service = Mockery::mock(StaleProductRefreshService::class, [$scraper])->makePartial();
        $service->shouldReceive('candidates')->once()->andReturn(new Collection);

        $result = $service->refresh(10, 48);

        $this->assertTrue($result->isEmpty());
        $this->assertSame(0, $result->selected);
        $this->assertStringContainsString('No stale products', $result->message);
    }

    public function test_refresh_counts_success_and_failure_per_product(): void
    {
        $ok = $this->makeProduct(11, 'CODE-OK', 2);
        $bad = $this->makeProduct(12, 'CODE-BAD', 2);

        $scraper = Mockery::mock(ProductScraperService::class);
        $scraper->shouldReceive('payload')
            ->twice()
            ->andReturnUsing(fn ($url, $code, $brandId) => [
                'brand_id' => $brandId,
                'code' => $code,
            ]);
        $scraper->shouldReceive('apply')
            ->once()
            ->with(['brand_id' => 2, 'code' => 'CODE-OK'])
            ->andReturn(['action' => 'update', 'has_changes' => true]);
        $scraper->shouldReceive('apply')
            ->once()
            ->with(['brand_id' => 2, 'code' => 'CODE-BAD'])
            ->andThrow(new ProductScraperException('محصول موجود نیست', 404));

        $service = Mockery::mock(StaleProductRefreshService::class, [$scraper])->makePartial();
        $service->shouldReceive('candidates')->once()->andReturn(new Collection([$ok, $bad]));

        $result = $service->refresh(10, 48);

        $this->assertSame(2, $result->selected);
        $this->assertSame(1, $result->succeeded);
        $this->assertSame(1, $result->failed);
        $this->assertSame(0, $result->skipped);
        $this->assertSame(ProductRefreshOutcome::STATUS_SUCCESS, $result->outcomes[0]->status);
        $this->assertSame('update', $result->outcomes[0]->action);
        $this->assertSame(ProductRefreshOutcome::STATUS_FAILED, $result->outcomes[1]->status);
        $this->assertSame('محصول موجود نیست', $result->outcomes[1]->reason);

        $log = $result->toLogContext();
        $this->assertCount(1, $log['failures']);
        $this->assertSame(12, $log['failures'][0]['product_id']);
    }

    public function test_refresh_pauses_between_products_when_delay_configured(): void
    {
        config()->set('product_scraper.stale_refresh.delay_seconds', 3);

        $first = $this->makeProduct(1, 'A', 2);
        $second = $this->makeProduct(2, 'B', 2);

        $scraper = Mockery::mock(ProductScraperService::class);
        $scraper->shouldReceive('payload')->twice()->andReturnUsing(
            fn ($url, $code, $brandId) => ['brand_id' => $brandId, 'code' => $code]
        );
        $scraper->shouldReceive('apply')->twice()->andReturn(['action' => 'update']);

        $service = Mockery::mock(StaleProductRefreshService::class, [$scraper])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('candidates')->once()->andReturn(new Collection([$first, $second]));
        $service->shouldReceive('pauseBetweenRequests')->once()->with(3);

        $service->refresh(2, 48);
    }

    public function test_refresh_one_skips_product_without_code(): void
    {
        $scraper = Mockery::mock(ProductScraperService::class);
        $scraper->shouldNotReceive('apply');

        $service = new StaleProductRefreshService($scraper);
        $outcome = $service->refreshOne($this->makeProduct(5, '', 2));

        $this->assertSame(ProductRefreshOutcome::STATUS_SKIPPED, $outcome->status);
        $this->assertStringContainsString('code or brand_id', (string) $outcome->reason);
    }

    public function test_result_empty_factory(): void
    {
        $result = StaleProductRefreshResult::empty('nothing');

        $this->assertTrue($result->isEmpty());
        $this->assertSame('nothing', $result->toArray()['message']);
    }

    private function makeProduct(int $id, string $code, int $brandId): Product
    {
        $product = new Product([
            'code' => $code,
            'brand_id' => $brandId,
            'title' => 'Sample',
            'active' => 1,
            'status' => Product::COMPLETED,
        ]);
        $product->id = $id;
        $product->exists = true;

        return $product;
    }
}
