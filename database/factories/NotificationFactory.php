<?php

namespace Database\Factories;

use Domain\Notification\Models\Notification;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'content' => fake()->sentence(),
            'user_id' => User::factory(),
            'status' => 1,
            'read' => 0,
            'model_id' => null,
            'model_type' => null,
        ];
    }
}
