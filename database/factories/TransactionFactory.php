<?php

namespace Database\Factories;

use Domain\Payment\Models\Transaction;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'amount' => fake()->numberBetween(10000, 500000),
            'reference' => (string) fake()->unique()->numerify('##########'),
            'bank_transaction_id' => null,
            'image' => null,
            'manual' => 0,
            'model_type' => Transaction::WALLET,
            'model_id' => null,
            'user_id' => User::factory(),
            'description' => fake()->sentence(),
            'message' => null,
            'status' => Transaction::PENDING,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Transaction::COMPLETED,
        ]);
    }

    public function forOrder(int $orderId): static
    {
        return $this->state(fn (array $attributes) => [
            'model_type' => Transaction::ORDER,
            'model_id' => $orderId,
        ]);
    }

    public function forWallet(int $walletId): static
    {
        return $this->state(fn (array $attributes) => [
            'model_type' => Transaction::WALLET,
            'model_id' => $walletId,
        ]);
    }
}
