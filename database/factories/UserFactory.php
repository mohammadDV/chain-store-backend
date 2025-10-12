<?php

namespace Database\Factories;

use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Domain\User\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'nickname' => fake()->unique()->userName(),
            'customer_number' => User::generateCustumerNumber(),
            'role_id' => 2,
            'status' => 1,
            'email' => fake()->unique()->safeEmail(),
            'mobile' => fake()->numerify('09##########'),
            'password' => static::$password ??= Hash::make('Password123!'),
            'profile_photo_path' => config('image.default-profile-image'),
            'bg_photo_path' => config('image.default-background-image'),
            'point' => 0,
            'rate' => 0,
            'level' => 0,
            'is_private' => false,
            'is_report' => false,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
