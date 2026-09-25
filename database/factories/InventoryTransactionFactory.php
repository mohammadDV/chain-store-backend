<?php

namespace Database\Factories;

use Domain\Product\Enums\InventoryTransactionSource;
use Domain\Product\Enums\InventoryTransactionType;
use Domain\Product\Models\InventoryTransaction;
use Domain\Product\Models\Product;
use Domain\Product\Models\Size;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryTransaction>
 */
class InventoryTransactionFactory extends Factory
{
    protected $model = InventoryTransaction::class;

    public function definition(): array
    {
        $previous = 10;
        $change = -2;
        $resulting = $previous + $change;

        return [
            'product_id' => Product::factory(),
            'size_id' => fn (array $attributes) => Size::factory()->create([
                'product_id' => $attributes['product_id'],
            ])->id,
            'type' => InventoryTransactionType::Adjust,
            'source' => InventoryTransactionSource::System,
            'user_id' => null,
            'quantity_change' => $change,
            'previous_quantity' => $previous,
            'resulting_quantity' => $resulting,
            'description' => null,
        ];
    }
}
