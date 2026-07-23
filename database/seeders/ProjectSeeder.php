<?php

namespace Database\Seeders;

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Models\Label;
use App\Models\Project;
use App\Models\ProjectStatus as ProjectStatusModel;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskChecklistItem;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectSeeder extends Seeder
{
    private const DEFAULT_STATUSES = [
        ['name' => 'To Do',       'color' => 'gray',  'is_default' => true,  'is_completed' => false],
        ['name' => 'In Progress', 'color' => 'blue',  'is_default' => false, 'is_completed' => false],
        ['name' => 'Review',      'color' => 'amber', 'is_default' => false, 'is_completed' => false],
        ['name' => 'Done',        'color' => 'green', 'is_default' => false, 'is_completed' => true],
    ];

    private const LABEL_DEFS = [
        ['name' => 'Bug',         'color' => 'red'],
        ['name' => 'Feature',     'color' => 'blue'],
        ['name' => 'Improvement', 'color' => 'indigo'],
        ['name' => 'Urgent',      'color' => 'amber'],
        ['name' => 'Documentation','color' => 'purple'],
    ];

    public function run(): void
    {
        // 3 user — masing-masing pemilik 1 proyek
        $user1 = User::where('email', 'user1@app.com')->first();
        $user2 = User::where('email', 'user2@app.com')->first();
        $user3 = User::where('email', 'user3@app.com')->first();

        // Mapping: 1 user → 1 proyek
        $projects = [
            [
                'code' => 'WEB',
                'name' => 'Pengembangan Website Company Profile',
                'description' => 'Pembangunan website company profile dengan CMS dan integrasi API.',
                'color' => 'blue',
                'owner' => $user1,
            ],
            [
                'code' => 'MOB',
                'name' => 'Aplikasi Mobile BKI',
                'description' => 'Aplikasi mobile untuk layanan pelanggan dan tracking status.',
                'color' => 'indigo',
                'owner' => $user2,
            ],
            [
                'code' => 'INF',
                'name' => 'Infrastruktur & DevOps',
                'description' => 'Setup CI/CD, Docker, monitoring, dan migrasi server.',
                'color' => 'green',
                'owner' => $user3,
            ],
        ];

        foreach ($projects as $projData) {
            $owner = $projData['owner'];
            unset($projData['owner']);

            $project = Project::create([
                ...$projData,
                'status' => ProjectStatus::Active->value,
                'owner_id' => $owner->id,
                'start_date' => now()->subWeeks(2)->format('Y-m-d'),
                'end_date' => now()->addMonths(2)->format('Y-m-d'),
                'task_sequence' => 0,
            ]);

            $project->members()->attach($owner->id, ['role' => ProjectMemberRole::Manager->value]);

            $statuses = [];
            foreach (self::DEFAULT_STATUSES as $index => $statusData) {
                $statuses[] = $project->statuses()->create([
                    ...$statusData,
                    'position' => $index,
                ]);
            }

            $labels = [];
            foreach (self::LABEL_DEFS as $labelData) {
                $labels[] = $project->labels()->create($labelData);
            }

            $taskTitles = [
                'Setup project repository dan CI/CD',
                'Desain wireframe dan mockup UI',
                'Implementasi halaman landing page',
                'Buat API endpoint untuk data perusahaan',
                'Integrasi frontend dengan backend API',
                'Setup autentikasi dan authorization',
                'Implementasi dashboard admin',
                'Optimasi performa dan SEO',
                'Write unit dan integration tests',
                'Deploy ke staging environment',
                'Fix responsivitas pada mobile view',
                'Implementasi dark mode',
                'Setup monitoring dan error tracking',
                'Migrasi database ke production',
                'Code review dan refactoring',
            ];

            $members = $project->members()->get();
            $defaultStatus = collect($statuses)->firstWhere('is_default', true);

            foreach ($taskTitles as $i => $title) {
                $project->increment('task_sequence');
                $number = $project->fresh()->task_sequence;

                $statusIndex = $i < 5 ? 0 : ($i < 9 ? 1 : ($i < 12 ? 2 : 3));
                $status = $statuses[$statusIndex];

                $task = Task::create([
                    'project_id' => $project->id,
                    'number' => $number,
                    'parent_id' => null,
                    'status_id' => $status->id,
                    'title' => $title,
                    'description' => fake()->optional(0.7)->paragraph(2),
                    'priority' => fake()->randomElement(TaskPriority::values()),
                    'reporter_id' => $owner->id,
                    'start_date' => fake()->boolean(50) ? fake()->dateTimeBetween('-2 weeks', 'now')->format('Y-m-d') : null,
                    'due_date' => fake()->boolean(60) ? fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d') : null,
                    'position' => $i + 1,
                    'completed_at' => $status->is_completed ? now() : null,
                ]);

                $assigneeCount = min(fake()->numberBetween(1, 2), $members->count());
                $task->assignees()->attach($members->random($assigneeCount)->pluck('id'));

                $labelCount = fake()->numberBetween(0, 2);
                if ($labelCount > 0) {
                    $task->labels()->attach(collect($labels)->random($labelCount)->pluck('id'));
                }

                TaskActivity::create([
                    'task_id' => $task->id,
                    'user_id' => $owner->id,
                    'event' => 'created',
                    'description' => 'membuat tugas ini',
                ]);

                if (fake()->boolean(40)) {
                    $commentUser = $members->random();
                    TaskComment::create([
                        'task_id' => $task->id,
                        'user_id' => $commentUser->id,
                        'body' => fake()->sentence(8),
                    ]);
                }

                if (fake()->boolean(50)) {
                    $checklistCount = fake()->numberBetween(2, 4);
                    for ($c = 0; $c < $checklistCount; $c++) {
                        TaskChecklistItem::create([
                            'task_id' => $task->id,
                            'content' => fake()->sentence(3),
                            'is_done' => $status->is_completed ? true : fake()->boolean(30),
                            'position' => $c + 1,
                        ]);
                    }
                }

                if ($i < 3) {
                    $project->increment('task_sequence');
                    $subNumber = $project->fresh()->task_sequence;

                    $subtask = Task::create([
                        'project_id' => $project->id,
                        'number' => $subNumber,
                        'parent_id' => $task->id,
                        'status_id' => $defaultStatus->id,
                        'title' => fake()->sentence(3),
                        'description' => fake()->optional(0.5)->paragraph(),
                        'priority' => TaskPriority::Medium->value,
                        'reporter_id' => $owner->id,
                        'position' => 1,
                    ]);

                    $subtask->assignees()->attach($members->random()->id);

                    TaskActivity::create([
                        'task_id' => $subtask->id,
                        'user_id' => $owner->id,
                        'event' => 'created',
                        'description' => 'membuat tugas ini',
                    ]);
                }
            }
        }
    }
}
