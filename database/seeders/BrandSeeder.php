<?php

namespace Database\Seeders;

use Domain\Brand\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            [
                'title' => 'نایک',
                'slug' => 'nike',
                'description' => 'برند معروف ورزشی نایک',
                'status' => 1,
                'priority' => 1
            ],
            [
                'title' => 'آدیداس',
                'slug' => 'adidas',
                'description' => 'برند ورزشی آدیداس',
                'status' => 1,
                'priority' => 2
            ],
            [
                'title' => 'سامسونگ',
                'slug' => 'samsung',
                'description' => 'برند الکترونیک سامسونگ',
                'status' => 1,
                'priority' => 3
            ],
            [
                'title' => 'اپل',
                'slug' => 'apple',
                'description' => 'برند تکنولوژی اپل',
                'status' => 1,
                'priority' => 4
            ],
            [
                'title' => 'زارا',
                'slug' => 'zara',
                'description' => 'برند پوشاک زارا',
                'status' => 1,
                'priority' => 5
            ],
        ];

        foreach ($brands as $brand) {
            Brand::create($brand);
        }
    }
}
