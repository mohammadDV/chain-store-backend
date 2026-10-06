<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
    Cache::flush();
});

it('caches product search results for identical queries', function () {
    [, $product] = $this->seedProductWithStock();
    $product->update(['title' => 'Nike Air Zoom', 'active' => 1]);

    $this->postJson('/api/products/search', [
        'query' => 'Nike',
        'count' => 10,
        'page' => 1,
    ])->assertOk();

    $queries = 0;
    DB::listen(function () use (&$queries) {
        $queries++;
    });

    $this->postJson('/api/products/search', [
        'query' => 'Nike',
        'count' => 10,
        'page' => 1,
    ])->assertOk()
        ->assertJsonPath('data.0.title', 'Nike Air Zoom');

    expect($queries)->toBe(0);
});

it('searches product titles only and ignores description matches', function () {
    [, $product] = $this->seedProductWithStock();
    $product->update([
        'title' => 'Plain Shoes',
        'description' => 'UniqueZebraToken in description',
        'details' => 'UniqueZebraToken in details',
        'active' => 1,
    ]);

    $this->postJson('/api/products/search', [
        'query' => 'UniqueZebraToken',
        'count' => 10,
        'page' => 1,
    ])->assertOk()
        ->assertJsonPath('data', []);
});
