<?php

namespace Database\Factories;

use Domain\Product\Models\Color;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Color>
 */
class ColorFactory extends Factory
{
    protected $model = Color::class;

    public function definition(): array
    {
        return [
            'title' => fake()->colorName(),
            'code' => fake()->unique()->hexColor(),
            'status' => 1,
            'priority' => 0,
        ];
    }
}
