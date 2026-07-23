<?php

namespace App\Services;

use App\Jobs\OptimizeTaskAttachment;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskService
{
    public function __construct(private FileStorageService $storage)
    {
    }

    public function create(Project $project, array $data): Task
    {
        return DB::transaction(function () use ($project, $data) {
            $locked = Project::whereKey($project->id)->lockForUpdate()->first();
            $number = $locked->task_sequence + 1;
            $locked->update(['task_sequence' => $number]);

            $statusId = $data['status_id']
                ?? $project->statuses()->where('is_default', true)->value('id')
                ?? $project->statuses()->orderBy('position')->value('id');

            $position = (int) Task::where('project_id', $project->id)
                ->where('status_id', $statusId)
                ->max('position');

            $task = Task::create([
                'project_id' => $project->id,
                'number' => $number,
                'parent_id' => $data['parent_id'] ?? null,
                'status_id' => $statusId,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'priority' => $data['priority'] ?? 'medium',
                'reporter_id' => $data['reporter_id'] ?? Auth::id(),
                'start_date' => $data['start_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'position' => $position + 1,
            ]);

            if (! empty($data['assignee_ids'])) {
                $task->assignees()->sync($data['assignee_ids']);
            }
            if (! empty($data['label_ids'])) {
                $task->labels()->sync($data['label_ids']);
            }

            $this->log($task, 'created', 'membuat tugas ini');

            $this->notifyTaskAssignees($task, $data['assignee_ids'] ?? [], 'ditugaskan ke', 'Anda ditugaskan ke tugas baru');

            if (!empty($data['parent_id'])) {
                $this->notifySubtaskCreated($task);
            }

            return $task;
        });
    }

    public function update(Task $task, array $data): Task
    {
        $original = $task->only(['title', 'priority', 'due_date', 'status_id']);

        $statusId = $data['status_id'] ?? $task->status_id;
        $statusChanged = $task->status_id !== (int) $statusId;

        $oldAssigneeIds = $task->assignees()->pluck('users.id')->toArray();

        $task->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'] ?? $task->priority,
            'status_id' => $statusId,
            'due_date' => $data['due_date'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'completed_at' => $statusChanged
                ? ($task->project->statuses()->find($statusId)?->is_completed ? now() : null)
                : $task->completed_at,
        ]);

        if (array_key_exists('assignee_ids', $data)) {
            $task->assignees()->sync($data['assignee_ids']);
            $newAssigneeIds = array_diff($data['assignee_ids'], $oldAssigneeIds);
            $this->notifyTaskAssignees($task, array_values($newAssigneeIds), 'ditugaskan ke', 'Anda ditugaskan ke tugas');
        }
        if (array_key_exists('label_ids', $data)) {
            $task->labels()->sync($data['label_ids']);
        }

        if ($statusChanged) {
            $statusName = $task->project->statuses()->find($statusId)?->name ?? '-';
            $this->log($task, 'status_changed', "memindahkan tugas ke \"{$statusName}\"");
            $this->notifyStatusChanged($task, $statusName);
        }

        $this->log($task, 'updated', 'memperbarui detail tugas', [
            'old' => $original,
            'new' => $task->only(['title', 'priority', 'due_date', 'status_id']),
        ]);

        return $task;
    }

    public function delete(Task $task): void
    {
        $recipientIds = $this->getTaskNotificationRecipients($task);
        $taskCode = $task->code;
        $taskTitle = $task->title;

        $task->delete();

        if (!empty($recipientIds)) {
            NotificationService::sendToMany(
                userIds: $recipientIds,
                title: 'Tugas dihapus',
                message: "Tugas \"{$taskCode}: {$taskTitle}\" telah dihapus oleh " . (Auth::user()?->name ?? 'Sistem'),
                type: 'danger',
                icon: 'trash',
            );
        }
    }

    public function moveToStatus(Task $task, int $statusId, array $orderedIds): void
    {
        DB::transaction(function () use ($task, $statusId, $orderedIds) {
            $status = $task->project->statuses()->find($statusId);
            if (! $status) {
                return;
            }

            $statusChanged = $task->status_id !== $statusId;

            foreach ($orderedIds as $index => $id) {
                Task::where('id', $id)
                    ->where('project_id', $task->project_id)
                    ->update([
                        'status_id' => $statusId,
                        'position' => $index + 1,
                    ]);
            }

            if ($statusChanged) {
                $task->refresh();
                $task->update([
                    'completed_at' => $status->is_completed ? now() : null,
                ]);
                $this->log($task, 'status_changed', "memindahkan tugas ke \"{$status->name}\"");
                $this->notifyStatusChanged($task, $status->name);
            }
        });
    }

    public function assign(Task $task, array $userIds): void
    {
        $task->assignees()->sync($userIds);
        $this->log($task, 'assigned', 'memperbarui penerima tugas');
    }

    public function addComment(Task $task, User $user, string $body): TaskComment
    {
        $comment = $task->comments()->create([
            'user_id' => $user->id,
            'body' => $body,
        ]);

        $this->log($task, 'commented', 'menambahkan komentar');

        $recipientIds = $this->getTaskNotificationRecipients($task, $user->id);
        if (!empty($recipientIds)) {
            NotificationService::sendToMany(
                userIds: $recipientIds,
                title: 'Komentar baru',
                message: "{$user->name} berkomentar di \"{$task->code}: {$task->title}\"",
                type: 'info',
                icon: 'chat',
                actionUrl: route('task-management.board', $task->project),
            );
        }

        return $comment;
    }

    public function deleteComment(TaskComment $comment): void
    {
        $comment->delete();
    }

    public function addAttachment(Task $task, UploadedFile $file, User $user): TaskAttachment
    {
        $temp = $this->storage->storeTemp($file, 'task-management');

        $result = $this->storage->moveFromTemp(
            $temp['path'],
            $temp['original_name'],
            'task-management',
            [$task->project->code . '-' . $task->number]
        );

        $attachment = $task->attachments()->create([
            'user_id' => $user->id,
            'disk' => file_disk(),
            'path' => $result['path'],
            'original_name' => $result['name'],
            'mime_type' => $file->getMimeType(),
            'size' => $result['size'],
        ]);

        OptimizeTaskAttachment::dispatch($attachment->id);

        $this->log($task, 'attached', "melampirkan berkas \"{$attachment->original_name}\"");

        return $attachment;
    }

    public function deleteAttachment(TaskAttachment $attachment): void
    {
        $this->storage->delete($attachment->path);
        $attachment->delete();
    }

    public function toggleChecklistItem(int $itemId, Task $task): void
    {
        $item = $task->checklistItems()->find($itemId);
        if ($item) {
            $item->update(['is_done' => ! $item->is_done]);
        }
    }

    private function log(Task $task, string $event, string $description, ?array $properties = null): void
    {
        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'event' => $event,
            'description' => $description,
            'properties' => $properties,
        ]);
    }

    private function getTaskNotificationRecipients(Task $task, ?int $excludeUserId = null): array
    {
        $assigneeIds = $task->assignees()->pluck('users.id')->toArray();
        $reporterId = $task->reporter_id;
        $ownerId = $task->project->owner_id;

        $ids = array_unique(array_merge(
            $assigneeIds,
            $reporterId ? [$reporterId] : [],
            $ownerId ? [$ownerId] : [],
        ));

        $exclude = $excludeUserId ?? Auth::id();

        return array_values(array_filter($ids, fn ($id) => $id !== $exclude));
    }

    private function notifyTaskAssignees(Task $task, array $assigneeIds, string $action, string $titlePrefix): void
    {
        $ids = array_values(array_filter($assigneeIds, fn ($id) => $id !== Auth::id()));
        if (empty($ids)) {
            return;
        }

        NotificationService::sendToMany(
            userIds: $ids,
            title: $titlePrefix,
            message: "Anda {$action} \"{$task->code}: {$task->title}\"",
            type: 'info',
            icon: 'clipboard',
            actionUrl: route('task-management.board', $task->project),
        );
    }

    private function notifyStatusChanged(Task $task, string $statusName): void
    {
        $recipientIds = $this->getTaskNotificationRecipients($task);
        if (empty($recipientIds)) {
            return;
        }

        $actorName = Auth::user()?->name ?? 'Sistem';

        NotificationService::sendToMany(
            userIds: $recipientIds,
            title: 'Status tugas berubah',
            message: "\"{$task->code}: {$task->title}\" dipindahkan ke \"{$statusName}\" oleh {$actorName}",
            type: 'info',
            icon: 'arrow-right',
            actionUrl: route('task-management.board', $task->project),
        );
    }

    private function notifySubtaskCreated(Task $subtask): void
    {
        $parent = $subtask->parent;
        if (!$parent) {
            return;
        }

        $recipientIds = $this->getTaskNotificationRecipients($parent);
        if (empty($recipientIds)) {
            return;
        }

        $actorName = Auth::user()?->name ?? 'Sistem';

        NotificationService::sendToMany(
            userIds: $recipientIds,
            title: 'Subtugas baru',
            message: "{$actorName} membuat subtugas \"{$subtask->code}: {$subtask->title}\" di \"{$parent->code}\"",
            type: 'info',
            icon: 'plus',
            actionUrl: route('task-management.board', $subtask->project),
        );
    }
}
