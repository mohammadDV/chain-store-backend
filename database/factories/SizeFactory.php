<?php

namespace Database\Factories;

use Domain\Product\Models\Product;
use Domain\Product\Models\Size;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Size>
 */
class SizeFactory extends Factory
{
    protected $model = Size::class;

    public function definition(): array
    {
        $title = fake()->randomElement(['S', 'M', 'L', 'XL', '42', '43']);

        return [
            'title' => $title,
            'code' => $title.'-'.fake()->unique()->numerify('###'),
            'status' => 1,
            'priority' => 1,
            'product_id' => Product::factory(),
        ];
    }
}
