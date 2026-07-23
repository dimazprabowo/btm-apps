<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tasks_view');
    }

    public function view(User $user, Task $task): bool
    {
        return $user->can('tasks_view')
            && ($user->can('tasks_view_all')
                || $this->canManage($user, $task));
    }

    public function create(User $user, Task $task): bool
    {
        return $user->can('tasks_create') && $this->canManage($user, $task);
    }

    public function update(User $user, Task $task): bool
    {
        return $user->can('tasks_update') && $this->canManage($user, $task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->can('tasks_delete') && $this->canManage($user, $task);
    }

    public function assign(User $user, Task $task): bool
    {
        return $user->can('tasks_assign') && $this->canManage($user, $task);
    }

    public function comment(User $user, Task $task): bool
    {
        return $user->can('tasks_comment') && $this->canManage($user, $task);
    }

    public function deleteComment(User $user, Task $task, int $commentUserId): bool
    {
        return ($commentUserId === $user->id || $user->can('tasks_delete'))
            && $this->canManage($user, $task);
    }

    private function canManage(User $user, Task $task): bool
    {
        return $task->project->hasMember($user) || $user->can('projects_view_all');
    }
}
