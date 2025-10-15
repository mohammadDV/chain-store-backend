<?php

namespace Database\Seeders;

use Domain\Product\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Nike categories
            [
                'title' => 'کفش ورزشی',
                'status' => 1,
                'brand_id' => 1,
                'parent_id' => 0,
                'priority' => 1
            ],
            [
                'title' => 'لباس ورزشی',
                'status' => 1,
                'brand_id' => 1,
                'parent_id' => 0,
                'priority' => 2
            ],
            // Adidas categories
            [
                'title' => 'کفش ورزشی',
                'status' => 1,
                'brand_id' => 2,
                'parent_id' => 0,
                'priority' => 1
            ],
            [
                'title' => 'لباس ورزشی',
                'status' => 1,
                'brand_id' => 2,
                'parent_id' => 0,
                'priority' => 2
            ],
            // Samsung categories
            [
                'title' => 'گوشی موبایل',
                'status' => 1,
                'brand_id' => 3,
                'parent_id' => 0,
                'priority' => 1
            ],
            [
                'title' => 'تلویزیون',
                'status' => 1,
                'brand_id' => 3,
                'parent_id' => 0,
                'priority' => 2
            ],
            // Apple categories
            [
                'title' => 'آیفون',
                'status' => 1,
                'brand_id' => 4,
                'parent_id' => 0,
                'priority' => 1
            ],
            [
                'title' => 'مک بوک',
                'status' => 1,
                'brand_id' => 4,
                'parent_id' => 0,
                'priority' => 2
            ],
            // Zara categories
            [
                'title' => 'لباس مردانه',
                'status' => 1,
                'brand_id' => 5,
                'parent_id' => 0,
                'priority' => 1
            ],
            [
                'title' => 'لباس زنانه',
                'status' => 1,
                'brand_id' => 5,
                'parent_id' => 0,
                'priority' => 2
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}