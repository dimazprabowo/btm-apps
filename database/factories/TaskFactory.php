<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'number' => 1,
            'parent_id' => null,
            'status_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->optional(0.7)->paragraph(),
            'priority' => fake()->randomElement(TaskPriority::values()),
            'reporter_id' => User::factory(),
            'start_date' => fake()->boolean(50) ? fake()->dateTimeBetween('-1 week', 'now')->format('Y-m-d') : null,
            'due_date' => fake()->boolean(60) ? fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d') : null,
            'position' => 1,
            'completed_at' => null,
        ];
    }
}
