<?php

namespace Database\Factories;

use Domain\Wallet\Models\Wallet;
use Domain\Wallet\Models\WithdrawalTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WithdrawalTransaction>
 */
class WithdrawalTransactionFactory extends Factory
{
    protected $model = WithdrawalTransaction::class;

    public function definition(): array
    {
        return [
            'wallet_id' => Wallet::factory(),
            'amount' => fake()->randomFloat(2, 10000, 500000),
            'currency' => Wallet::IRR,
            'status' => WithdrawalTransaction::PENDING,
            'reference' => (string) fake()->unique()->numerify('##########'),
            'card' => fake()->numerify('################'),
            'sheba' => 'IR'.fake()->numerify('########################'),
            'description' => fake()->sentence(),
            'image' => null,
            'reason' => null,
        ];
    }
}
