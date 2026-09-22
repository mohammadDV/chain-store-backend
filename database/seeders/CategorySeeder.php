<?php

namespace Database\Seeders;

use Domain\Brand\Models\Brand;
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
            ['title' => 'کفش ورزشی', 'status' => 1, 'parent_id' => 0, 'priority' => 1, 'brands' => [1, 2]],
            ['title' => 'لباس ورزشی', 'status' => 1, 'parent_id' => 0, 'priority' => 2, 'brands' => [1, 2]],
            ['title' => 'گوشی موبایل', 'status' => 1, 'parent_id' => 0, 'priority' => 3, 'brands' => [3]],
            ['title' => 'تلویزیون', 'status' => 1, 'parent_id' => 0, 'priority' => 4, 'brands' => [3]],
            ['title' => 'آیفون', 'status' => 1, 'parent_id' => 0, 'priority' => 5, 'brands' => [4]],
            ['title' => 'مک بوک', 'status' => 1, 'parent_id' => 0, 'priority' => 6, 'brands' => [4]],
            ['title' => 'لباس مردانه', 'status' => 1, 'parent_id' => 0, 'priority' => 7, 'brands' => [5]],
            ['title' => 'لباس زنانه', 'status' => 1, 'parent_id' => 0, 'priority' => 8, 'brands' => [5]],
        ];

        foreach ($categories as $data) {
            $brandIds = $data['brands'];
            unset($data['brands']);

            $category = Category::create($data);

            foreach ($brandIds as $index => $brandId) {
                if (Brand::find($brandId)) {
                    $category->brands()->attach($brandId, [
                        'priority' => $index + 1,
                        'status' => 1,
                    ]);
                }
            }
        }
    }
}
