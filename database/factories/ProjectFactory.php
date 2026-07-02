<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'identifier' => fake()->unique()->lexify('???'),
            'description' => fake()->optional()->sentence(),
            'lead_id' => User::factory(),
            'default_state_id' => null,
            'archived_at' => null,
        ];
    }
}
