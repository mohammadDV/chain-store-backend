<?php

use App\Filament\Resources\BannerResource\Pages\ListBanners;
use App\Filament\Resources\BrandResource\Pages\CreateBrand;
use App\Filament\Resources\BrandResource\Pages\ListBrands;
use App\Filament\Resources\CategoryResource\Pages\ListCategories;
use App\Filament\Resources\ColorResource\Pages\CreateColor;
use App\Filament\Resources\ColorResource\Pages\ListColors;
use App\Filament\Resources\CostCategoryResource\Pages\ListCostCategories;
use App\Filament\Resources\CostResource\Pages\ListCosts;
use App\Filament\Resources\DiscountResource\Pages\ListDiscounts;
use App\Filament\Resources\NotificationResource\Pages\ListNotifications;
use App\Filament\Resources\PostResource\Pages\ListPosts;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Resources\ReviewResource\Pages\ListReviews;
use App\Filament\Resources\SettingResource\Pages\EditSetting;
use App\Filament\Resources\SettingResource\Pages\ListSettings;
use App\Filament\Resources\TicketMessageResource\Pages\ListTicketMessages;
use App\Filament\Resources\TicketResource\Pages\ListTickets;
use App\Filament\Resources\TicketSubjectResource\Pages\CreateTicketSubject;
use App\Filament\Resources\TicketSubjectResource\Pages\ListTicketSubjects;
use App\Filament\Resources\TransactionResource\Pages\ListTransactions;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\WalletTransactionResource\Pages\ListWalletTransactions;
use Domain\Brand\Models\Brand;
use Domain\Product\Models\Color;
use Domain\Setting\Models\Setting;
use Domain\Ticket\Models\TicketSubject;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
    $this->actingAsAdmin();
});

it('lists all admin resource index pages', function (string $page) {
    livewire($page)->assertSuccessful();
})->with([
    'banners' => ListBanners::class,
    'brands' => ListBrands::class,
    'categories' => ListCategories::class,
    'colors' => ListColors::class,
    'cost categories' => ListCostCategories::class,
    'costs' => ListCosts::class,
    'discounts' => ListDiscounts::class,
    'notifications' => ListNotifications::class,
    'posts' => ListPosts::class,
    'products' => ListProducts::class,
    'reviews' => ListReviews::class,
    'ticket messages' => ListTicketMessages::class,
    'tickets' => ListTickets::class,
    'ticket subjects' => ListTicketSubjects::class,
    'transactions' => ListTransactions::class,
    'users' => ListUsers::class,
    'wallet transactions' => ListWalletTransactions::class,
]);

it('creates a brand from the admin panel', function () {
    livewire(CreateBrand::class)
        ->fillForm([
            'title' => 'Test Brand Admin',
            'slug' => 'test-brand-admin',
            'domain' => 'example.com',
            'description' => 'Brand from filament test',
            'status' => 1,
            'priority' => 0,
            'has_stock_management' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Brand::query()->where('title', 'Test Brand Admin')->exists())->toBeTrue();
});

it('creates a color from the admin panel', function () {
    livewire(CreateColor::class)
        ->fillForm([
            'title' => 'Filament Red',
            'code' => '#ff0000',
            'status' => 1,
            'priority' => 0,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Color::query()->where('title', 'Filament Red')->exists())->toBeTrue();
});

it('creates a ticket subject from the admin panel', function () {
    livewire(CreateTicketSubject::class)
        ->fillForm([
            'title' => 'Shipping question',
            'status' => 1,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(TicketSubject::query()->where('title', 'Shipping question')->exists())->toBeTrue();
});

it('redirects settings index to the single edit page', function () {
    livewire(ListSettings::class)
        ->assertRedirect();
});

it('opens the settings edit page', function () {
    $setting = Setting::getInstance();

    livewire(EditSetting::class, ['record' => $setting->getRouteKey()])
        ->assertSuccessful();
});
