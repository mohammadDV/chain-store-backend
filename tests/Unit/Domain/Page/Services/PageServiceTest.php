<?php

use Domain\Page\Models\Page;
use Domain\Page\Services\PageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake();
    app(PageService::class)->clearCache();
});

it('returns navigation for active pages only', function () {
    Page::query()->create([
        'slug' => 'about',
        'title' => 'درباره ما',
        'content' => '<p>about</p>',
        'is_active' => true,
        'show_in_menu' => true,
        'sort_order' => 1,
    ]);
    Page::query()->create([
        'slug' => 'hidden',
        'title' => 'مخفی',
        'content' => '<p>hidden</p>',
        'is_active' => false,
        'show_in_menu' => true,
        'sort_order' => 2,
    ]);

    $nav = app(PageService::class)->getNavigation();

    expect($nav)->toHaveCount(1)
        ->and($nav[0])->toMatchArray([
            'slug' => 'about',
            'title' => 'درباره ما',
            'show_in_menu' => true,
            'sort_order' => 1,
        ]);
});

it('caches page by slug and clears on update', function () {
    $page = Page::query()->create([
        'slug' => 'rules',
        'title' => 'قوانین',
        'content' => '<p>old</p>',
        'is_active' => true,
        'show_in_menu' => false,
        'sort_order' => 1,
    ]);

    $service = app(PageService::class);
    $first = $service->getBySlug('rules');

    expect($first['content'])->toBe('<p>old</p>')
        ->and(Cache::has('page:rules'))->toBeTrue();

    Page::query()->whereKey($page->id)->update(['content' => '<p>stale</p>']);

    expect($service->getBySlug('rules')['content'])->toBe('<p>old</p>');

    $page->refresh()->update(['content' => '<p>new</p>']);

    expect($service->getBySlug('rules')['content'])->toBe('<p>new</p>');
});

it('returns null for inactive pages', function () {
    Page::query()->create([
        'slug' => 'complaint',
        'title' => 'شکایت',
        'content' => '<p>x</p>',
        'is_active' => false,
        'show_in_menu' => false,
        'sort_order' => 1,
    ]);

    expect(app(PageService::class)->getBySlug('complaint'))->toBeNull();
});
