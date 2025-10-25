<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
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
              'emergency_phone' => $this->faker->phoneNumber(),
              'relationship' => $this->faker->randomElement(['Father', 'Mother', 'Brother', 'Sister', 'Friend']),
                ];
    }
}
