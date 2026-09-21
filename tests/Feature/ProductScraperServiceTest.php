<?php

namespace Tests\Feature;

use Domain\Product\Exceptions\ProductScraperException;
use Domain\Product\Services\ProductScraperService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductScraperServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('product_scraper.url', 'http://scraper.test');
        config()->set('product_scraper.token', 'secret');
        config()->set('product_scraper.timeout', 5);
    }

    public function test_preview_posts_payload_and_returns_json(): void
    {
        Http::fake([
            'http://scraper.test/preview' => Http::response([
                'mode' => 'url',
                'action' => 'create',
                'has_changes' => true,
                'product' => ['title' => 'Bileklik', 'code' => '8941380', 'price' => 199],
                'existing' => null,
                'changes' => [],
            ], 200),
        ]);

        $result = app(ProductScraperService::class)->preview([
            'url' => 'https://www.decathlon.com.tr/p/x',
            'brand_id' => 2,
            'category_id' => 4,
        ]);

        $this->assertSame('create', $result['action']);
        $this->assertSame('8941380', $result['product']['code']);
        Http::assertSent(function ($request) {
            return $request->url() === 'http://scraper.test/preview'
                && $request->hasHeader('Authorization', 'Bearer secret')
                && $request['brand_id'] === 2
                && $request['category_id'] === 4;
        });
    }

    public function test_apply_raises_on_error_body(): void
    {
        Http::fake([
            'http://scraper.test/apply' => Http::response(['error' => 'محصول موجود نیست'], 404),
        ]);

        $this->expectException(ProductScraperException::class);
        $this->expectExceptionMessage('محصول موجود نیست');

        app(ProductScraperService::class)->apply(['code' => 'missing', 'brand_id' => 2]);
    }

    public function test_payload_omits_empty_fields(): void
    {
        $payload = app(ProductScraperService::class)->payload(null, '8941380', 2, null);

        $this->assertSame([
            'brand_id' => 2,
            'code' => '8941380',
        ], $payload);
    }
}
