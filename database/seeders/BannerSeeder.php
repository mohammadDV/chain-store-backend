<?php

namespace Database\Seeders;

use Domain\Brand\Models\Banner;
use Domain\Brand\Models\Brand;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have at least one brand
        $brands = Brand::all();

        if ($brands->isEmpty()) {
            $this->command->warn('No brands found. Please seed brands first.');
            return;
        }

        $banners = [
            [
                'title' => 'Summer Sale 2025',
                'link' => 'https://example.com/summer-sale',
                'image' => 'default/default-user-banner.jpg',
                'status' => 1,
                'priority' => 1,
                'brand_id' => $brands->random()->id,
            ],
            [
                'title' => 'New Collection Launch',
                'link' => 'https://example.com/new-collection',
                'image' => 'default/default-user-banner.jpg',
                'status' => 1,
                'priority' => 2,
                'brand_id' => $brands->random()->id,
            ],
            [
                'title' => 'Weekend Special Offer',
                'link' => 'https://example.com/weekend-offer',
                'image' => 'default/default-user-banner.jpg',
                'status' => 1,
                'priority' => 3,
                'brand_id' => $brands->random()->id,
            ],
            [
                'title' => 'Black Friday Deals',
                'link' => 'https://example.com/black-friday',
                'image' => 'default/default-user-banner.jpg',
                'status' => 1,
                'priority' => 4,
                'brand_id' => $brands->random()->id,
            ],
            [
                'title' => 'Spring Collection',
                'link' => 'https://example.com/spring-collection',
                'image' => 'default/default-user-banner.jpg',
                'status' => 1,
                'priority' => 5,
                'brand_id' => $brands->random()->id,
            ],
            [
                'title' => 'Flash Sale - 50% Off',
                'link' => 'https://example.com/flash-sale',
                'image' => 'default/default-user-banner.jpg',
                'status' => 1,
                'priority' => 6,
                'brand_id' => $brands->random()->id,
            ],
            [
                'title' => 'Holiday Special',
                'link' => 'https://example.com/holiday-special',
                'image' => 'default/default-user-banner.jpg',
                'status' => 1,
                'priority' => 7,
                'brand_id' => $brands->random()->id,
            ],
            [
                'title' => 'Exclusive Member Deals',
                'link' => 'https://example.com/member-deals',
                'image' => 'default/default-user-banner.jpg',
                'status' => 1,
                'priority' => 8,
                'brand_id' => $brands->random()->id,
            ],
            [
                'title' => 'Back to School Sale',
                'link' => 'https://example.com/back-to-school',
                'image' => 'default/default-user-banner.jpg',
                'status' => 1,
                'priority' => 9,
                'brand_id' => $brands->random()->id,
            ],
            [
                'title' => 'Clearance Event',
                'link' => 'https://example.com/clearance',
                'image' => 'default/default-user-banner.jpg',
                'status' => 1,
                'priority' => 10,
                'brand_id' => $brands->random()->id,
            ],
        ];

        foreach ($banners as $banner) {
            Banner::create($banner);
        }
    }
}
