<?php

namespace Database\Factories;

use App\Models\FeatureAssignment;
use App\Models\FeatureFlag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeatureAssignment>
 */
class FeatureAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'feature_flag_id' => FeatureFlag::factory(),
            'user_id' => User::factory(),
            'kind' => 'override',
            'active' => true,
        ];
    }
}
