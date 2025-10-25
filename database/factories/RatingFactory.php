<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Rating;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Rating>
 */
class RatingFactory extends Factory
{
    protected $model = Rating::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reviewer_id' => 1,
            'reviewee_id' => 2,
            'reviewee_type' => 'therapist',
            'rating' =>4.5,

        ];
    }
}
