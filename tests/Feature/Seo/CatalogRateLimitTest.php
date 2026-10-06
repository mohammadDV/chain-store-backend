<?php

use Core\Http\RateLimiting\CatalogRateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
    RateLimiter::clear('catalog-sitemap:127.0.0.1');
    RateLimiter::clear('catalog-search:127.0.0.1');
});

it('rate limits sitemap after too many requests from the same ip', function () {
    for ($i = 0; $i < CatalogRateLimiter::SITEMAP_PER_MINUTE; $i++) {
        $this->getJson('/api/seo/sitemap')->assertOk();
    }

    $this->getJson('/api/seo/sitemap')
        ->assertStatus(429)
        ->assertJsonPath('status', 0);
});

it('rate limits product search after too many requests from the same ip', function () {
    for ($i = 0; $i < CatalogRateLimiter::SEARCH_PER_MINUTE; $i++) {
        $this->postJson('/api/products/search', [
            'query' => 'nike',
            'count' => 10,
            'page' => 1,
        ])->assertOk();
    }

    $this->postJson('/api/products/search', [
        'query' => 'nike',
        'count' => 10,
        'page' => 1,
    ])
        ->assertStatus(429)
        ->assertJsonPath('status', 0);
});
