<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'formatted_address' => $this->faker->address(),
            'country' => $this->faker->country(),
            'region' => $this->faker->state(),
            'city' => $this->faker->city(),
            'district' => $this->faker->streetName(),
            'postal_code' => $this->faker->postcode(),
            'location_type' => $this->faker->randomElement(['home', 'clinic', 'center']),
        ];
    }
}
