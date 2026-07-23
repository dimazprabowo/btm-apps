<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('projects_view');
    }

    public function view(User $user, Project $project): bool
    {
        return $user->can('projects_view')
            && ($this->canOversee($user) || $project->hasMember($user));
    }

    public function create(User $user): bool
    {
        return $user->can('projects_create');
    }

    public function update(User $user, Project $project): bool
    {
        return $user->can('projects_update')
            && ($this->canOversee($user) || $project->isManager($user));
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->can('projects_delete')
            && ($this->canOversee($user) || $project->owner_id === $user->id);
    }

    public function manageMembers(User $user, Project $project): bool
    {
        return $user->can('projects_manage_members')
            && ($this->canOversee($user) || $project->isManager($user));
    }

    public function exportExcelList(User $user): bool
    {
        return $user->can('projects_export_excel');
    }

    public function exportPdfList(User $user): bool
    {
        return $user->can('projects_export_pdf');
    }

    public function exportExcel(User $user, Project $project): bool
    {
        return $user->can('tasks_export_excel')
            && ($this->canOversee($user) || $project->hasMember($user));
    }

    public function exportPdf(User $user, Project $project): bool
    {
        return $user->can('tasks_export_pdf')
            && ($this->canOversee($user) || $project->hasMember($user));
    }

    private function canOversee(User $user): bool
    {
        return $user->can('projects_view_all');
    }
}
