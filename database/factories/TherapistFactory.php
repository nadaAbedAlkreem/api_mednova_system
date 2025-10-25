<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\MedicalSpecialtie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Therapist>
 */
class TherapistFactory extends Factory
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
            'medical_specialties_id' => MedicalSpecialtie::inRandomOrder()->first()?->id ?? MedicalSpecialtie::factory(),
            'experience_years' => $this->faker->numberBetween(1, 20),
            'university_name' => $this->faker->company(),
            'countries_certified' => $this->faker->country(),
            'graduation_year' => $this->faker->year(),
            'license_number' => strtoupper($this->faker->bothify('LIC-####')),
            'license_authority' => $this->faker->company(),
            'bio' => $this->faker->paragraph(),
        ];
    }
}
