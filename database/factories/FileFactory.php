<?php

namespace Database\Factories;

use Domain\Product\Models\File;
use Domain\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<File>
 */
class FileFactory extends Factory
{
    protected $model = File::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'path' => 'products/'.fake()->uuid().'.jpg',
            'type' => 'image',
            'status' => 1,
            'priority' => 0,
        ];
    }
}
