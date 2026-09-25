<?php

namespace Database\Factories;

use Domain\Product\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discount>
 */
class DiscountFactory extends Factory
{
    protected $model = Discount::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('DISC####')),
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 10,
            'max_value' => null,
            'visible' => 1,
            'expire_date' => now()->addMonth()->toDateString(),
            'active' => 1,
        ];
    }

    public function fixed(float $value = 50000): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Discount::TYPE_FIXED,
            'value' => $value,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expire_date' => now()->subDay()->toDateString(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => 0,
        ]);
    }
}
