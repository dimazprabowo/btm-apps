<?php

namespace App\Livewire\TaskManagement;

use App\Enums\ProjectStatus;
use App\Livewire\Traits\HasNotification;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ProjectForm extends Component
{
    use AuthorizesRequests, HasNotification;

    public ?Project $project = null;
    public bool $editMode = false;

    public ?int $projectId = null;
    public string $code = '';
    public string $name = '';
    public ?string $description = null;
    public string $color = 'blue';
    public string $status = 'active';
    public ?string $start_date = null;
    public ?string $end_date = null;
    public array $member_ids = [];

    public function mount(?Project $project = null): void
    {
        if ($project && $project->exists) {
            $this->authorize('update', $project);
            $this->project = $project->load('owner');
            $this->editMode = true;
            $this->projectId = $project->id;
            $this->code = $project->code;
            $this->name = $project->name;
            $this->description = $project->description;
            $this->color = $project->color;
            $this->status = $project->status->value;
            $this->start_date = $project->start_date?->format('Y-m-d');
            $this->end_date = $project->end_date?->format('Y-m-d');
            $this->member_ids = $project->members->pluck('id')->map(fn ($id) => (int) $id)->toArray();
        } else {
            $this->authorize('create', Project::class);
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-]+$/', $this->editMode ? 'unique:projects,code,' . $this->projectId : 'unique:projects,code'],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'color' => 'required|string|max:20',
            'status' => ['required', 'string', 'in:' . implode(',', ProjectStatus::values())],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'member_ids' => 'array',
            'member_ids.*' => 'exists:users,id',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'code' => 'kode proyek',
            'name' => 'nama proyek',
            'description' => 'deskripsi',
            'color' => 'warna',
            'status' => 'status',
            'start_date' => 'tanggal mulai',
            'end_date' => 'tanggal selesai',
            'member_ids' => 'anggota',
        ];
    }

    #[Computed]
    public function statusOptions(): array
    {
        return ProjectStatus::options();
    }

    #[Computed]
    public function ownerName(): string
    {
        if ($this->editMode && $this->project) {
            return $this->project->owner?->name ?? '-';
        }

        return auth()->user()?->name ?? '-';
    }

    #[Computed]
    public function userOptions(): array
    {
        return User::active()
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $u) => [
                'value' => $u->id,
                'label' => $u->name,
                'sublabel' => $u->email,
            ])->toArray();
    }

    public function save(ProjectService $service): void
    {
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->notifyValidationError($e);
            throw $e;
        }

        try {
            $data = [
                'code' => $this->code,
                'name' => $this->name,
                'description' => $this->description,
                'color' => $this->color,
                'status' => $this->status,
                'start_date' => $this->start_date ?: null,
                'end_date' => $this->end_date ?: null,
                'member_ids' => $this->member_ids,
            ];

            if ($this->editMode) {
                $project = Project::findOrFail($this->projectId);
                $this->authorize('update', $project);
                if (! empty($this->member_ids)) {
                    $this->authorize('manageMembers', $project);
                }
                $service->update($project, $data);
                $message = 'Proyek berhasil diperbarui!';
            } else {
                $this->authorize('create', Project::class);
                if (! empty($this->member_ids)) {
                    $this->authorize('projects_manage_members');
                }
                $service->create($data, auth()->user());
                $message = 'Proyek berhasil dibuat!';
            }

            $this->notifySuccess($message);
            $this->redirect(route('task-management.projects'), navigate: true);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            $this->notifyError('Anda tidak memiliki izin untuk melakukan aksi ini.');
        } catch (\Exception $e) {
            report($e);
            $this->notifyError('Terjadi kesalahan sistem. Silakan coba lagi.');
        }
    }

    public function cancel(): void
    {
        $this->redirect(route('task-management.projects'), navigate: true);
    }

    public function render()
    {
        return view('livewire.task-management.project-form');
    }
}
