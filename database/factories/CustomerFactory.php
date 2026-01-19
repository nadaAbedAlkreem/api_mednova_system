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
    protected $model = Customer::class;

    public function definition(): array
    {
        $type = \fake()->randomElement([
            'therapist',
            'rehabilitation_center',
            'patient'
        ]);

        return [
            'full_name' => \fake()->name(),
            'email' => \fake()->unique()->safeEmail(),
            'phone' => \fake()->phoneNumber(),
            'gender' => \fake()->randomElement(['Male', 'Female']),
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'birth_date' => \fake()->date('Y-m-d', '2005-01-01'),
            'image' => \fake()->imageUrl(200, 200, 'people'),
            'provider' => null,
            'provider_id' => null,
            'fcm_token' => Str::random(10),
            'is_online' => \fake()->boolean(),
            'last_active_at' => now(),
            'is_banned' => false,
            'type_account' => $type,
            'status' => 'active',
        ];
    }

}
