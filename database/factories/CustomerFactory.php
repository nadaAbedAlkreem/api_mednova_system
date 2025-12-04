<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
//        $type = $this->faker->randomElement(['therapist', 'rehabilitation_center', 'patient']);

        return [
            'full_name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'gender' => $this->faker->randomElement(['Male', 'Female']),
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'birth_date' => $this->faker->date('Y-m-d', '2005-01-01'),
            'image' => $this->faker->imageUrl(200, 200, 'people'),
            'provider' => null,
            'provider_id' => null,
            'fcm_token' => Str::random(10),
            'is_online' => $this->faker->boolean(),
            'last_active_at' => now(),
            'is_banned' => false,
            'type_account' => 'therapist',
            'status' => 'active',
        ];
    }
}
