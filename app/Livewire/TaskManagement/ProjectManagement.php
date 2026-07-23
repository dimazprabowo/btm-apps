<?php

namespace App\Livewire\TaskManagement;

use App\Enums\ProjectStatus;
use App\Exports\ProjectsExport;
use App\Livewire\Traits\HasNotification;
use App\Models\Project;
use App\Services\ProjectService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectManagement extends Component
{
    use WithPagination, AuthorizesRequests, HasNotification;

    public string $search = '';
    public string $statusFilter = '';
    public bool $filterChanged = false;

    public bool $showDeleteModal = false;
    public ?int $deletingProjectId = null;
    public string $deletingProjectName = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Project::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->filterChanged = true;
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
        $this->filterChanged = true;
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->resetPage();
        $this->filterChanged = true;
        $this->notifySuccess('Filter berhasil direset.');
    }

    #[Computed]
    public function statusOptions(): array
    {
        return ProjectStatus::options();
    }

    public function create(): void
    {
        $this->authorize('create', Project::class);
        $this->redirect(route('task-management.projects.create'), navigate: true);
    }

    public function edit(int $id): void
    {
        $project = Project::findOrFail($id);
        $this->authorize('update', $project);
        $this->redirect(route('task-management.projects.edit', $project), navigate: true);
    }

    public function confirmDelete(int $id): void
    {
        $project = Project::findOrFail($id);
        $this->authorize('delete', $project);
        $this->deletingProjectId = $project->id;
        $this->deletingProjectName = $project->name;
        $this->showDeleteModal = true;
    }

    public function delete(ProjectService $service): void
    {
        try {
            $project = Project::findOrFail($this->deletingProjectId);
            $this->authorize('delete', $project);
            $service->delete($project);
            $this->notifySuccess('Proyek berhasil dihapus!');
            $this->showDeleteModal = false;
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            $this->notifyError('Anda tidak dapat menghapus proyek ini.');
        } catch (\Exception $e) {
            report($e);
            $this->notifyError('Terjadi kesalahan sistem. Silakan coba lagi.');
        }
    }

    public function exportExcel()
    {
        $this->authorize('exportExcelList', Project::class);

        $user = auth()->user();
        $viewAll = $user->can('projects_view_all');

        return (new ProjectsExport(
            $user,
            $viewAll,
            $this->search ?: null,
            $this->statusFilter ?: null,
        ))->download('proyek-' . now()->format('Y-m-d-His') . '.xlsx');
    }

    public function exportPdf()
    {
        $this->authorize('exportPdfList', Project::class);

        $user = auth()->user();
        $viewAll = $user->can('projects_view_all');

        $query = Project::query()
            ->with('owner:id,name')
            ->withCount(['tasks', 'members']);

        if (! $viewAll) {
            $query->forUser($user);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereLike('code', $this->search)
                  ->orWhereLike('name', $this->search);
            });
        }

        if ($this->statusFilter !== null && $this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        $projects = $query->orderByDesc('created_at')->get();

        $pdf = Pdf::loadView('exports.projects-pdf', ['projects' => $projects]);
        $pdf->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'proyek-' . now()->format('Y-m-d-His') . '.pdf'
        );
    }

    public function getListeners(): array
    {
        $userId = auth()->id();

        return [
            "echo-private:user.{$userId},NewNotification" => '$refresh',
        ];
    }

    public function render(ProjectService $service)
    {
        $user = auth()->user();
        $viewAll = $user->can('projects_view_all');

        $projects = $service->getFilteredForUser(
            $user,
            $viewAll,
            $this->search,
            $this->statusFilter,
        );

        if ($this->filterChanged) {
            $this->notifySuccess("Ditemukan {$projects->total()} data proyek.");
            $this->filterChanged = false;
        }

        return view('livewire.task-management.project-management', [
            'projects' => $projects,
        ]);
    }
}
