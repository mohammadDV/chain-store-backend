<?php

namespace Database\Factories;

use Domain\Product\Models\Endpoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Endpoint>
 */
class EndpointFactory extends Factory
{
    protected $model = Endpoint::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
        ];
    }
}
