<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        $colors = ['blue', 'green', 'red', 'amber', 'indigo', 'purple'];
        $codes = ['WEB', 'MOB', 'API', 'INF', 'DSG', 'QA', 'OPS'];

        return [
            'code' => fake()->unique()->regexify('[A-Z]{3,6}'),
            'name' => fake()->catchPhrase(),
            'description' => fake()->paragraph(),
            'color' => fake()->randomElement($colors),
            'status' => fake()->randomElement(ProjectStatus::values()),
            'owner_id' => User::factory(),
            'start_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'end_date' => fake()->boolean(60) ? fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d') : null,
            'task_sequence' => 0,
        ];
    }
}
