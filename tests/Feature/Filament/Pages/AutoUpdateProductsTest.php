<?php

use App\Filament\Pages\AutoUpdateProducts;
use Domain\Product\Services\ProductScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
    $this->actingAsAdmin();
});

it('mounts the auto update products page', function () {
    $scraper = \Mockery::mock(ProductScraperService::class);
    $this->app->instance(ProductScraperService::class, $scraper);

    livewire(AutoUpdateProducts::class)
        ->assertSuccessful()
        ->assertFormSet(['mode' => 'url']);
});
