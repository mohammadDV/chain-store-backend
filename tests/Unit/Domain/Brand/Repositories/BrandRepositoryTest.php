<?php

use Application\Api\Brand\Resources\BrandResource;
use Core\Http\Requests\TableRequest;
use Domain\Brand\Models\Banner;
use Domain\Brand\Models\Brand;
use Domain\Brand\Repositories\BrandRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

it('lists only active brands ordered by priority', function () {
    Brand::factory()->create(['status' => 1, 'priority' => 1, 'title' => 'Low']);
    Brand::factory()->create(['status' => 1, 'priority' => 10, 'title' => 'High']);
    Brand::factory()->create(['status' => 0, 'priority' => 100, 'title' => 'Hidden']);

    $result = app(BrandRepository::class)->index(new TableRequest);

    expect($result)->toHaveCount(2);
    expect($result->first())->toBeInstanceOf(BrandResource::class);
    expect($result->first()->resource->title)->toBe('High');
});

it('filters banners by brand id', function () {
    $brand = Brand::factory()->create(['status' => 1]);
    Banner::query()->create([
        'brand_id' => $brand->id,
        'status' => 1,
        'image' => 'banner.jpg',
        'title' => 'Brand banner',
    ]);
    Banner::query()->create([
        'brand_id' => null,
        'status' => 1,
        'image' => 'global.jpg',
        'title' => 'Global',
    ]);

    $request = Request::create('/api/banners', 'GET', ['brand' => $brand->id]);
    $result = app(BrandRepository::class)->getBanners($request);

    expect($result)->toHaveCount(1);
    expect($result->first()->resource->brand_id)->toBe($brand->id);
});
