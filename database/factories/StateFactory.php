<?php

namespace Database\Factories;

use App\Enums\StateGroup;
use App\Models\Project;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<State>
 */
class StateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'project_id' => Project::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'color' => fake()->hexColor(),
            'sequence' => fake()->numberBetween(1000, 9000),
            'group' => StateGroup::Unstarted,
            'is_default' => false,
        ];
    }
}
