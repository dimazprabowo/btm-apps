<?php

namespace App\Services;

use App\Enums\ProjectMemberRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    private const DEFAULT_STATUSES = [
        ['name' => 'To Do',       'color' => 'gray',   'is_default' => true,  'is_completed' => false],
        ['name' => 'In Progress', 'color' => 'blue',   'is_default' => false, 'is_completed' => false],
        ['name' => 'Review',      'color' => 'amber',  'is_default' => false, 'is_completed' => false],
        ['name' => 'Done',        'color' => 'green',  'is_default' => false, 'is_completed' => true],
    ];

    public function getFilteredForUser(
        User $user,
        bool $viewAll = false,
        ?string $search = null,
        ?string $statusFilter = null,
        int $perPage = 12
    ): LengthAwarePaginator {
        $query = Project::query()
            ->with('owner:id,name')
            ->withCount(['tasks', 'members']);

        if (! $viewAll) {
            $query->forUser($user);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereLike('code', $search)
                  ->orWhereLike('name', $search);
            });
        }

        if ($statusFilter !== null && $statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function create(array $data, User $owner): Project
    {
        return DB::transaction(function () use ($data, $owner) {
            $project = Project::create([
                'code' => strtoupper($data['code']),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'color' => $data['color'] ?? 'blue',
                'status' => $data['status'] ?? 'active',
                'owner_id' => $owner->id,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
            ]);

            foreach (self::DEFAULT_STATUSES as $index => $status) {
                $project->statuses()->create([...$status, 'position' => $index]);
            }

            $project->members()->syncWithoutDetaching([
                $owner->id => ['role' => ProjectMemberRole::Manager->value],
            ]);

            $newMemberIds = $this->syncMembers($project, $data['member_ids'] ?? []);

            $this->notifyMembersAdded($project, $newMemberIds, $owner);

            return $project;
        });
    }

    public function update(Project $project, array $data): Project
    {
        $project->update([
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? $project->color,
            'status' => $data['status'] ?? $project->status,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ]);

        if (array_key_exists('member_ids', $data)) {
            $newMemberIds = $this->syncMembers($project, $data['member_ids']);
            $this->notifyMembersAdded($project, $newMemberIds, auth()->user());
        }

        return $project;
    }

    public function delete(Project $project): void
    {
        $memberIds = $project->members()->where('users.id', '!=', auth()->id())->pluck('users.id')->toArray();
        $projectName = $project->name;

        $project->delete();

        if (!empty($memberIds)) {
            NotificationService::sendToMany(
                userIds: $memberIds,
                title: 'Proyek dihapus',
                message: "Proyek \"{$projectName}\" telah dihapus oleh " . auth()->user()->name,
                type: 'danger',
                icon: 'trash',
            );
        }
    }

    public function syncMembers(Project $project, array $memberIds): array
    {
        $existingIds = $project->members()->pluck('users.id')->toArray();

        $sync = [];

        foreach ($memberIds as $id) {
            $id = (int) $id;
            if ($id === $project->owner_id) {
                continue;
            }
            $sync[$id] = ['role' => ProjectMemberRole::Member->value];
        }

        $sync[$project->owner_id] = ['role' => ProjectMemberRole::Manager->value];

        $project->members()->sync($sync);

        $newIds = array_diff(array_keys($sync), $existingIds);
        return array_values($newIds);
    }

    private function notifyMembersAdded(Project $project, array $newMemberIds, ?User $actor): void
    {
        if (empty($newMemberIds)) {
            return;
        }

        $actorName = $actor?->name ?? 'Sistem';
        $actionUrl = route('task-management.board', $project);

        NotificationService::sendToMany(
            userIds: $newMemberIds,
            title: 'Anda ditambahkan ke proyek',
            message: "{$actorName} menambahkan Anda ke proyek \"{$project->name}\".",
            type: 'info',
            icon: 'folder',
            actionUrl: $actionUrl,
        );
    }
}
