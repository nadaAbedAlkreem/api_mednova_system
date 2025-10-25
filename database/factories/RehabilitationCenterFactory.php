<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RehabilitationCenter>
 */
class RehabilitationCenterFactory extends Factory
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
            'year_establishment' => $this->faker->year(),
            'license_number' => strtoupper($this->faker->bothify('LCN-####')),
            'license_authority' => $this->faker->company(),
            'has_commercial_registration' => $this->faker->boolean(),
            'commercial_registration_number' => strtoupper($this->faker->bothify('CRN-####')),
            'commercial_registration_authority' => $this->faker->company(),
            'bio' => $this->faker->paragraph(),
        ];
    }
}
