<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class SendDueDateReminders extends Command
{
    protected $signature = 'tasks:send-due-date-reminders';
    protected $description = 'Send due date reminders for tasks due in 1 or 3 days (H-1, H-3).';

    public function handle(): int
    {
        $reminders = [
            1 => 'besok',
            3 => '3 hari lagi',
        ];

        $total = 0;

        foreach ($reminders as $days => $label) {
            $targetDate = now()->addDays($days)->toDateString();

            $tasks = Task::whereNotNull('due_date')
                ->whereDate('due_date', $targetDate)
                ->whereHas('status', fn (Builder $q) => $q->where('is_completed', false))
                ->with(['assignees', 'project', 'reporter'])
                ->get();

            foreach ($tasks as $task) {
                $recipientIds = $this->getRecipients($task);

                foreach ($recipientIds as $userId) {
                    NotificationService::send(
                        userId: $userId,
                        title: 'Pengingat jatuh tempo',
                        message: "Tugas \"{$task->code}: {$task->title}\" jatuh tempo {$label} ({$task->due_date->format('d M Y')}).",
                        type: 'warning',
                        icon: 'clock',
                        actionUrl: route('task-management.board', $task->project),
                        data: ['task_id' => $task->id, 'days' => $days],
                    );
                    $total++;
                }
            }

            $this->info("H-{$days}: {$tasks->count()} tasks found, {$total} notifications sent so far.");
        }

        $this->info("Total notifications sent: {$total}");

        return self::SUCCESS;
    }

    private function getRecipients(Task $task): array
    {
        $assigneeIds = $task->assignees()->pluck('users.id')->toArray();
        $reporterId = $task->reporter_id;

        $ids = array_unique(array_merge(
            $assigneeIds,
            $reporterId ? [$reporterId] : [],
        ));

        return array_values($ids);
    }
}
