<?php

namespace App\Models;

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectStatus as ProjectStatusEnum;
use App\Traits\HasDynamicLike;
use App\Traits\HasEncryptedRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes, HasDynamicLike, HasEncryptedRouteKey;

    protected $fillable = [
        'code',
        'name',
        'description',
        'color',
        'status',
        'owner_id',
        'start_date',
        'end_date',
        'task_sequence',
    ];

    protected $casts = [
        'status' => ProjectStatusEnum::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'task_sequence' => 'integer',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(ProjectStatus::class)->orderBy('position');
    }

    public function labels(): HasMany
    {
        return $this->hasMany(Label::class)->orderBy('name');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('members', fn ($m) => $m->where('users.id', $user->id));
        });
    }

    public function hasMember(User $user): bool
    {
        return $this->owner_id === $user->id
            || $this->members()->where('users.id', $user->id)->exists();
    }

    public function isManager(User $user): bool
    {
        if ($this->owner_id === $user->id) {
            return true;
        }

        return $this->members()
            ->where('users.id', $user->id)
            ->wherePivot('role', ProjectMemberRole::Manager->value)
            ->exists();
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
