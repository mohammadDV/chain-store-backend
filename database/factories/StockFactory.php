<?php

namespace Database\Factories;

use Domain\Product\Models\Stock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Prefer StockService::setQuantity in application code and tests.
 * Creating via this factory requires an existing size_id whose auto-created stock row was removed,
 * or use update on the size's stock relation instead.
 *
 * @extends Factory<Stock>
 */
class StockFactory extends Factory
{
    protected $model = Stock::class;

    public function definition(): array
    {
        return [
            'reserved' => 0,
            'quantity' => 10,
        ];
    }
}
