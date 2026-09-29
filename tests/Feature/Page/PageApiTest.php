<?php

use Domain\Page\Models\Page;
use Domain\Setting\Models\Setting;
use Domain\Setting\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake();
    app(SettingService::class)->clearCache();
});

it('returns contact settings from the settings row', function () {
    Setting::getInstance()->update([
        'contact_title' => 'تماس با ما',
        'contact_subtitle' => 'کمک می‌کنیم',
        'contact_phone' => '021-111',
        'contact_phone_hours' => '۹ تا ۱۷',
        'contact_address' => 'تهران',
        'contact_map_url' => 'https://maps.example.com',
        'contact_email' => 'info@example.com',
        'contact_email_hint' => 'ایمیل بزنید',
    ]);

    expect(app(SettingService::class)->getContactSettings())->toMatchArray([
        'title' => 'تماس با ما',
        'subtitle' => 'کمک می‌کنیم',
        'phone' => '021-111',
        'phone_hours' => '۹ تا ۱۷',
        'address' => 'تهران',
        'map_url' => 'https://maps.example.com',
        'email' => 'info@example.com',
        'email_hint' => 'ایمیل بزنید',
    ]);
});

it('exposes contact settings via public api', function () {
    Setting::getInstance()->update([
        'contact_phone' => '021-999',
        'contact_email' => 'hi@boofstore.com',
    ]);

    $this->getJson('/api/settings/contact')
        ->assertOk()
        ->assertJsonPath('status', 1)
        ->assertJsonPath('data.phone', '021-999')
        ->assertJsonPath('data.email', 'hi@boofstore.com');
});

it('lists active pages and shows page by slug', function () {
    Page::query()->create([
        'slug' => 'about',
        'title' => 'درباره ما',
        'content' => '<p>about body</p>',
        'is_active' => true,
        'show_in_menu' => true,
        'sort_order' => 1,
    ]);

    $this->getJson('/api/pages')
        ->assertOk()
        ->assertJsonPath('status', 1)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'about');

    $this->getJson('/api/pages/about')
        ->assertOk()
        ->assertJsonPath('data.title', 'درباره ما')
        ->assertJsonPath('data.content', '<p>about body</p>');
});

it('returns 404 for missing or inactive pages', function () {
    Page::query()->create([
        'slug' => 'rules',
        'title' => 'قوانین',
        'content' => '<p>rules</p>',
        'is_active' => false,
        'show_in_menu' => false,
        'sort_order' => 1,
    ]);

    $this->getJson('/api/pages/rules')->assertNotFound();
    $this->getJson('/api/pages/missing')->assertNotFound();
});
