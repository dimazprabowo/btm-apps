<?php

namespace App\Models;

use App\Enums\TaskPriority;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'number',
        'parent_id',
        'status_id',
        'title',
        'description',
        'priority',
        'reporter_id',
        'start_date',
        'due_date',
        'position',
        'completed_at',
    ];

    protected $casts = [
        'priority' => TaskPriority::class,
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'number' => 'integer',
        'position' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ProjectStatus::class, 'status_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')->orderBy('position');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_user')->withTimestamps();
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'task_label')->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->latest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class)->latest();
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(TaskChecklistItem::class)->orderBy('position');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TaskActivity::class)->latest();
    }

    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    public function getCodeAttribute(): string
    {
        return ($this->project?->code ?? 'TASK') . '-' . $this->number;
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->completed_at !== null || (bool) ($this->status?->is_completed);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date !== null
            && !$this->is_completed
            && $this->due_date->isPast();
    }
}
