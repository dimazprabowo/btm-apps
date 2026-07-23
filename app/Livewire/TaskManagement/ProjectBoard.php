<?php

namespace App\Livewire\TaskManagement;

use App\Enums\TaskPriority;
use App\Exports\TasksExport;
use App\Livewire\Traits\HasNotification;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProjectBoard extends Component
{
    use AuthorizesRequests, HasNotification, WithFileUploads;

    public Project $project;

    public string $viewMode = 'board';
    public string $search = '';
    public string $priorityFilter = '';
    public string $assigneeFilter = '';

    public bool $showTaskModal = false;
    public bool $editMode = false;
    public ?int $taskId = null;
    public ?int $parentId = null;
    public string $title = '';
    public ?string $description = null;
    public string $priority = 'medium';
    public ?int $status_id = null;
    public ?string $start_date = null;
    public ?string $due_date = null;
    public array $assignee_ids = [];
    public array $label_ids = [];

    public bool $showDetail = false;
    public ?int $detailTaskId = null;

    public string $newComment = '';
    public string $newChecklistItem = '';
    public $upload;

    public bool $showDeleteModal = false;
    public ?int $deletingTaskId = null;
    public string $deletingTaskTitle = '';

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);
        $this->project = $project;
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
    }

    public function resetFilters(): void
    {
        $this->priorityFilter = '';
        $this->assigneeFilter = '';
        $this->notifySuccess('Filter berhasil direset.');
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'priority' => ['required', 'in:' . implode(',', TaskPriority::values())],
            'status_id' => 'nullable|exists:project_statuses,id',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'assignee_ids' => 'array',
            'assignee_ids.*' => 'exists:users,id',
            'label_ids' => 'array',
            'label_ids.*' => 'exists:labels,id',
        ];
    }

    #[Computed]
    public function priorityOptions(): array
    {
        return TaskPriority::options();
    }

    #[Computed]
    public function memberOptions(): array
    {
        return $this->project->members()
            ->orderBy('name')
            ->get(['users.id', 'name', 'email'])
            ->map(fn (User $u) => [
                'value' => $u->id,
                'label' => $u->name,
                'sublabel' => $u->email,
            ])->toArray();
    }

    #[Computed]
    public function statusColumnOptions(): array
    {
        return $this->project->statuses()
            ->orderBy('position')
            ->get(['id', 'name'])
            ->map(fn ($s) => ['value' => $s->id, 'label' => $s->name])
            ->toArray();
    }

    #[Computed]
    public function labelOptions(): array
    {
        return $this->project->labels()
            ->get(['id', 'name'])
            ->map(fn ($l) => ['value' => $l->id, 'label' => $l->name])
            ->toArray();
    }

    #[Computed]
    public function statuses()
    {
        return $this->project->statuses()->with(['tasks' => function ($q) {
            $q->whereNull('parent_id')
              ->with(['assignees:id,name', 'labels:id,name,color'])
              ->withCount(['subtasks', 'comments', 'attachments'])
              ->when($this->search, fn ($qq) => $qq->whereLike('title', $this->search))
              ->when($this->priorityFilter, fn ($qq) => $qq->where('priority', $this->priorityFilter))
              ->when($this->assigneeFilter, fn ($qq) => $qq->whereHas('assignees', fn ($a) => $a->where('users.id', $this->assigneeFilter)))
              ->orderBy('position');
        }])->get();
    }

    #[Computed]
    public function detailTask(): ?Task
    {
        if (! $this->detailTaskId) {
            return null;
        }

        return Task::with([
            'status', 'reporter:id,name', 'assignees:id,name', 'labels',
            'parent:id,project_id,number,title', 'subtasks.status',
            'checklistItems', 'attachments.user:id,name',
            'comments.user:id,name', 'activities.user:id,name',
        ])->find($this->detailTaskId);
    }

    public function createTask(?int $statusId = null, ?int $parentId = null): void
    {
        $sample = (new Task)->setRelation('project', $this->project);
        $sample->project_id = $this->project->id;
        $this->authorize('create', $sample);

        $this->resetTaskForm();
        $this->editMode = false;
        $this->status_id = $statusId ?? $this->project->statuses()->where('is_default', true)->value('id');
        $this->parentId = $parentId;
        $this->showTaskModal = true;
    }

    public function editTask(int $id): void
    {
        $task = Task::with(['assignees:id', 'labels:id'])->findOrFail($id);
        $this->authorize('update', $task);

        $this->taskId = $task->id;
        $this->parentId = $task->parent_id;
        $this->title = $task->title;
        $this->description = $task->description;
        $this->priority = $task->priority->value;
        $this->status_id = $task->status_id;
        $this->start_date = $task->start_date?->format('Y-m-d');
        $this->due_date = $task->due_date?->format('Y-m-d');
        $this->assignee_ids = $task->assignees->pluck('id')->map(fn ($i) => (int) $i)->toArray();
        $this->label_ids = $task->labels->pluck('id')->map(fn ($i) => (int) $i)->toArray();

        $this->editMode = true;
        $this->showTaskModal = true;
    }

    public function saveTask(TaskService $service): void
    {
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->notifyValidationError($e);
            throw $e;
        }

        $data = [
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'status_id' => $this->status_id,
            'parent_id' => $this->parentId,
            'start_date' => $this->start_date ?: null,
            'due_date' => $this->due_date ?: null,
            'assignee_ids' => $this->assignee_ids,
            'label_ids' => $this->label_ids,
        ];

        try {
            if ($this->editMode) {
                $task = Task::findOrFail($this->taskId);
                $this->authorize('update', $task);
                if (! empty($this->assignee_ids)) {
                    $this->authorize('assign', $task);
                }
                $service->update($task, $data);
                $message = 'Tugas berhasil diperbarui!';
            } else {
                $sample = (new Task)->setRelation('project', $this->project);
                $this->authorize('create', $sample);
                if (! empty($this->assignee_ids)) {
                    $this->authorize('assign', $sample);
                }
                $service->create($this->project, $data);
                $message = 'Tugas berhasil dibuat!';
            }

            unset($this->statuses);
            if ($this->parentId) {
                unset($this->detailTask);
            }
            $this->notifySuccess($message);
            $this->closeTaskModal();
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            $this->notifyError('Anda tidak memiliki izin untuk aksi ini.');
        } catch (\Exception $e) {
            report($e);
            $this->notifyError('Terjadi kesalahan sistem. Silakan coba lagi.');
        }
    }

    public function confirmDeleteTask(int $id): void
    {
        $task = Task::findOrFail($id);
        $this->authorize('delete', $task);
        $this->deletingTaskId = $task->id;
        $this->deletingTaskTitle = $task->title;
        $this->showDeleteModal = true;
    }

    public function deleteTask(TaskService $service): void
    {
        try {
            $task = Task::findOrFail($this->deletingTaskId);
            $this->authorize('delete', $task);
            $service->delete($task);
            unset($this->statuses);
            $this->showDeleteModal = false;
            $this->showDetail = false;
            $this->notifySuccess('Tugas berhasil dihapus!');
        } catch (\Exception $e) {
            report($e);
            $this->notifyError('Terjadi kesalahan sistem. Silakan coba lagi.');
        }
    }

    public function dropTask(?int $taskId, int $statusId, TaskService $service): void
    {
        if ($taskId === null) {
            $this->notifyError('ID tugas tidak valid.');
            return;
        }

        try {
            $task = Task::findOrFail($taskId);
            $this->authorize('update', $task);

            if ($task->project_id !== $this->project->id) {
                return;
            }

            $orderedIds = $this->project->tasks()
                ->whereNull('parent_id')
                ->where('status_id', $statusId)
                ->where('id', '!=', $taskId)
                ->orderBy('position')
                ->pluck('id')
                ->toArray();
            $orderedIds[] = $taskId;

            $service->moveToStatus($task, $statusId, $orderedIds);
            unset($this->statuses);

            $status = $this->project->statuses()->find($statusId);
            $this->notifySuccess("{$task->code} dipindahkan ke \"{$status->name}\".");
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            $this->notifyError('Anda tidak memiliki izin memindahkan tugas.');
        } catch (\Exception $e) {
            report($e);
            $this->notifyError('Gagal memindahkan tugas. Silakan coba lagi.');
        }
    }

    public function openTask(int $id): void
    {
        $task = Task::findOrFail($id);
        $this->authorize('view', $task);
        $this->detailTaskId = $id;
        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->detailTaskId = null;
        $this->newComment = '';
        $this->newChecklistItem = '';
    }

    public function addComment(TaskService $service): void
    {
        $task = $this->detailTask;
        if (! $task) {
            return;
        }
        $this->authorize('comment', $task);

        $this->validate(['newComment' => 'required|string|max:2000']);
        $service->addComment($task, auth()->user(), $this->newComment);
        $this->newComment = '';
        unset($this->detailTask);
        $this->notifySuccess('Komentar ditambahkan.');
    }

    public function deleteComment(int $commentId, TaskService $service): void
    {
        $task = $this->detailTask;
        $comment = $task?->comments->firstWhere('id', $commentId);
        if (! $comment) {
            return;
        }
        $this->authorize('deleteComment', [$task, $comment->user_id]);
        $service->deleteComment($comment);
        unset($this->detailTask);
    }

    public function addChecklistItem(): void
    {
        $task = $this->detailTask;
        if (! $task) {
            return;
        }
        $this->authorize('update', $task);
        $this->validate(['newChecklistItem' => 'required|string|max:255']);

        $position = (int) $task->checklistItems()->max('position');
        $task->checklistItems()->create([
            'content' => $this->newChecklistItem,
            'position' => $position + 1,
        ]);
        $this->newChecklistItem = '';
        unset($this->detailTask);
    }

    public function toggleChecklistItem(int $itemId, TaskService $service): void
    {
        $task = $this->detailTask;
        if (! $task) {
            return;
        }
        $this->authorize('update', $task);
        $service->toggleChecklistItem($itemId, $task);
        unset($this->detailTask);
    }

    public function deleteChecklistItem(int $itemId): void
    {
        $task = $this->detailTask;
        $item = $task?->checklistItems->firstWhere('id', $itemId);
        if ($item) {
            $this->authorize('update', $task);
            $item->delete();
            unset($this->detailTask);
        }
    }

    public function updatedUpload(TaskService $service): void
    {
        $task = $this->detailTask;
        if (! $task) {
            return;
        }
        $this->authorize('update', $task);

        try {
            $this->validate([
                'upload' => file_upload_validation_rule('task-attachment', true),
            ]);

            $service->addAttachment($task, $this->upload, auth()->user());
            $this->reset('upload');
            unset($this->detailTask);
            $this->notifySuccess('Berkas berhasil dilampirkan.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->notifyValidationError($e);
        } catch (\Exception $e) {
            report($e);
            $this->notifyError('Gagal mengunggah berkas.');
        }
    }

    public function deleteAttachment(int $attachmentId, TaskService $service): void
    {
        $task = $this->detailTask;
        $attachment = $task?->attachments->firstWhere('id', $attachmentId);
        if (! $attachment) {
            return;
        }
        $this->authorize('update', $task);
        $service->deleteAttachment($attachment);
        unset($this->detailTask);
        $this->notifySuccess('Lampiran dihapus.');
    }

    public function closeTaskModal(): void
    {
        $this->showTaskModal = false;
        $this->resetTaskForm();
        $this->resetValidation();
    }

    private function resetTaskForm(): void
    {
        $this->reset([
            'taskId', 'parentId', 'title', 'description', 'status_id',
            'start_date', 'due_date', 'assignee_ids', 'label_ids',
        ]);
        $this->priority = 'medium';
    }

    public function backToParentTask(): void
    {
        $task = $this->detailTask;
        if (! $task || ! $task->parent_id) {
            return;
        }
        $parentId = $task->parent_id;
        $this->detailTaskId = $parentId;
        unset($this->detailTask);
    }

    public function exportExcel()
    {
        $this->authorize('exportExcel', $this->project);

        return (new TasksExport(
            $this->project,
            $this->search ?: null,
            $this->priorityFilter ?: null,
            $this->assigneeFilter ? (int) $this->assigneeFilter : null,
        ))->download($this->project->code . '-tugas-' . now()->format('Y-m-d-His') . '.xlsx');
    }

    public function exportPdf()
    {
        $this->authorize('exportPdf', $this->project);

        $tasks = $this->project->tasks()
            ->with(['status', 'assignees:id,name'])
            ->when($this->search, fn ($q) => $q->whereLike('title', $this->search))
            ->when($this->priorityFilter, fn ($q) => $q->where('priority', $this->priorityFilter))
            ->when($this->assigneeFilter, fn ($q) => $q->whereHas('assignees', fn ($a) => $a->where('users.id', $this->assigneeFilter)))
            ->orderBy('number')
            ->get();

        $pdf = Pdf::loadView('exports.tasks-pdf', ['project' => $this->project, 'tasks' => $tasks]);
        $pdf->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            $this->project->code . '-tugas-' . now()->format('Y-m-d-His') . '.pdf'
        );
    }

    public function getListeners(): array
    {
        $userId = auth()->id();

        return [
            "echo-private:user.{$userId},NewNotification" => '$refresh',
        ];
    }

    public function render()
    {
        return view('livewire.task-management.project-board');
    }
}
