<?php

namespace Database\Factories;

use Domain\Wallet\Models\Wallet;
use Domain\Wallet\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WalletTransaction>
 */
class WalletTransactionFactory extends Factory
{
    protected $model = WalletTransaction::class;

    public function definition(): array
    {
        return [
            'wallet_id' => Wallet::factory(),
            'type' => WalletTransaction::DEPOSITE,
            'amount' => fake()->randomFloat(2, 1000, 100000),
            'currency' => Wallet::IRR,
            'status' => WalletTransaction::COMPLETED,
            'reference' => (string) fake()->unique()->numerify('##########'),
            'description' => fake()->sentence(),
        ];
    }
}
