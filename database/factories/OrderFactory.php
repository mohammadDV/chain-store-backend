<?php

namespace Database\Factories;

use Domain\Product\Models\Order;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'discount_id' => null,
            'description' => null,
            'product_count' => 1,
            'total_amount' => 100000,
            'amount' => 100000,
            'discount_amount' => 0,
            'delivery_amount' => 0,
            'status' => Order::PENDING,
            'active' => 1,
            'vip' => 0,
            'code' => Order::generateCode(),
            'profit' => 0,
            'profit_rate' => 0,
            'exchange_rate' => 3000,
            'expire_date' => now()->addMinutes(30),
        ];
    }
}
