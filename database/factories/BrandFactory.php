<?php

namespace Database\Factories;

use Domain\Brand\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        $title = fake()->unique()->company();

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('###'),
            'logo' => null,
            'domain' => fake()->domainName(),
            'description' => fake()->sentence(),
            'status' => 1,
            'priority' => 0,
            'has_stock_management' => 0,
        ];
    }

    public function withStockManagement(): static
    {
        return $this->state(fn () => ['has_stock_management' => 1]);
    }
}
