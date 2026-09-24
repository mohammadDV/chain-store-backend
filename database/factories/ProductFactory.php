<?php

namespace Database\Factories;

use Domain\Brand\Models\Brand;
use Domain\Product\Models\Color;
use Domain\Product\Models\Product;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'details' => null,
            'points' => 0,
            'rate' => 0,
            'url' => fake()->unique()->url(),
            'amount' => 100,
            'discount' => 0,
            'discount_amount' => 0,
            'image' => null,
            'code' => fake()->unique()->numerify('########'),
            'active' => 1,
            'order_count' => 0,
            'view_count' => 0,
            'status' => Product::COMPLETED,
            'vip' => 0,
            'is_failed' => 0,
            'priority' => 0,
            'related_products' => null,
            'color_id' => Color::factory(),
            'brand_id' => Brand::factory(),
            'user_id' => User::factory(),
        ];
    }
}
