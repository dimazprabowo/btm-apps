@if($showDetail && ($task = $this->detailTask))
    <div class="fixed inset-0 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-gray-500/75 dark:bg-gray-900/80" wire:click="closeDetail"></div>

        <div class="absolute inset-y-0 right-0 max-w-full flex">
            <div class="w-screen max-w-2xl bg-white dark:bg-gray-800 shadow-xl flex flex-col h-full">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        @if($task->parent)
                            <button wire:key="back-parent-{{ $task->id }}" wire:click="backToParentTask" class="mb-2 inline-flex items-center gap-1 text-xs text-gray-400 hover:text-blue-600 dark:hover:text-blue-400">
                                <span wire:loading.remove wire:target="backToParentTask">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                </span>
                                <x-inline-spinner wire:loading wire:target="backToParentTask" size="h-3.5 w-3.5" />
                                <span class="font-mono">{{ $task->parent->code }}</span>
                                <span class="truncate max-w-[200px]">{{ $task->parent->title }}</span>
                            </button>
                        @endif
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-mono text-gray-400">{{ $task->code }}</span>
                            @if($task->status)
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ color_badge_class($task->status->color) }}">{{ $task->status->name }}</span>
                            @endif
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $task->priority->badgeClass() }}">{{ $task->priority->label() }}</span>
                        </div>
                        <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $task->title }}</h3>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        @can('tasks_update')
                            <button wire:key="edit-{{ $task->id }}" wire:click="editTask({{ $task->id }})" class="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400" title="Edit">
                                <span wire:loading.remove wire:target="editTask({{ $task->id }})">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </span>
                                <x-inline-spinner wire:loading wire:target="editTask({{ $task->id }})" size="h-5 w-5" />
                            </button>
                        @endcan
                        @can('tasks_delete')
                            <button wire:key="del-{{ $task->id }}" wire:click="confirmDeleteTask({{ $task->id }})" class="text-gray-400 hover:text-red-600 dark:hover:text-red-400" title="Hapus">
                                <span wire:loading.remove wire:target="confirmDeleteTask({{ $task->id }})">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </span>
                                <x-inline-spinner wire:loading wire:target="confirmDeleteTask({{ $task->id }})" size="h-5 w-5" />
                            </button>
                        @endcan
                        <button wire:key="close-detail" wire:click="closeDetail" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" title="Tutup">
                            <span wire:loading.remove wire:target="closeDetail">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </span>
                            <x-inline-spinner wire:loading wire:target="closeDetail" size="h-6 w-6" />
                        </button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-4 space-y-6">
                    {{-- Meta --}}
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Penerima</p>
                            <div class="flex flex-wrap gap-1">
                                @forelse($task->assignees as $a)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-xs text-gray-700 dark:text-gray-200">
                                        <x-avatar :text="$a->name" size="xs" />
                                        {{ $a->name }}
                                    </span>
                                @empty
                                    <span class="text-gray-400">Belum ditugaskan</span>
                                @endforelse
                            </div>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Pelapor</p>
                            <p class="text-gray-700 dark:text-gray-200">{{ $task->reporter?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Jatuh Tempo</p>
                            <p class="{{ $task->is_overdue ? 'text-red-500' : 'text-gray-700 dark:text-gray-200' }}">{{ $task->due_date ? $task->due_date->format('d M Y') : '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Label</p>
                            <div class="flex flex-wrap gap-1">
                                @forelse($task->labels as $l)
                                    <span class="px-1.5 py-0.5 text-[10px] rounded {{ color_badge_class($l->color) }}">{{ $l->name }}</span>
                                @empty
                                    <span class="text-gray-400">-</span>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Deskripsi</p>
                        <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">
                            {{ $task->description ?: 'Tidak ada deskripsi.' }}
                        </div>
                    </div>

                    {{-- Subtasks --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Subtugas ({{ $task->subtasks->count() }})</p>
                            @can('tasks_create')
                                <button wire:key="add-sub-{{ $task->id }}" wire:click="createTask(null, {{ $task->id }})" class="inline-flex items-center text-xs text-blue-600 dark:text-blue-400 hover:underline">
                                    <span wire:loading.remove wire:target="createTask(null, {{ $task->id }})">+ Tambah</span>
                                    <x-inline-spinner wire:loading wire:target="createTask(null, {{ $task->id }})" size="h-3 w-3" />
                                </button>
                            @endcan
                        </div>
                        <div class="space-y-1">
                            @forelse($task->subtasks as $sub)
                                <button wire:key="sub-{{ $sub->id }}" wire:click="openTask({{ $sub->id }})" class="w-full flex items-center gap-2 px-3 py-2 min-h-9 rounded-lg bg-gray-50 dark:bg-gray-900/50 hover:bg-gray-100 dark:hover:bg-gray-700 text-left">
                                    <span wire:loading.remove wire:target="openTask({{ $sub->id }})" class="flex items-center gap-2 flex-1 min-w-0">
                                        <span class="text-xs font-mono text-gray-400">{{ $sub->code }}</span>
                                        <span class="text-sm text-gray-700 dark:text-gray-200 flex-1 truncate">{{ $sub->title }}</span>
                                    </span>
                                    <span wire:loading.remove wire:target="openTask({{ $sub->id }})">
                                        @if($sub->status)<span class="text-xs text-gray-400">{{ $sub->status->name }}</span>@endif
                                    </span>
                                    <x-inline-spinner wire:loading wire:target="openTask({{ $sub->id }})" size="h-4 w-4" class="text-gray-400" />
                                </button>
                            @empty
                                <p class="text-sm text-gray-400">Belum ada subtugas.</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Checklist --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">
                            Checklist
                            @if($task->checklistItems->isNotEmpty())
                                ({{ $task->checklistItems->where('is_done', true)->count() }}/{{ $task->checklistItems->count() }})
                            @endif
                        </p>
                        <div class="space-y-1">
                            @foreach($task->checklistItems as $item)
                                <div class="flex items-center gap-2 group">
                                    <input type="checkbox" @checked($item->is_done) wire:click="toggleChecklistItem({{ $item->id }})"
                                        class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500" @cannot('tasks_update') disabled @endcannot>
                                    <span class="text-sm flex-1 {{ $item->is_done ? 'line-through text-gray-400' : 'text-gray-700 dark:text-gray-200' }}">{{ $item->content }}</span>
                                    @can('tasks_update')
                                        <button wire:key="del-check-{{ $item->id }}" wire:click="deleteChecklistItem({{ $item->id }})" class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-500">
                                            <span wire:loading.remove wire:target="deleteChecklistItem({{ $item->id }})">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </span>
                                            <x-inline-spinner wire:loading wire:target="deleteChecklistItem({{ $item->id }})" size="h-4 w-4" />
                                        </button>
                                    @endcan
                                </div>
                            @endforeach
                        </div>
                        @can('tasks_update')
                            <form wire:submit="addChecklistItem" class="mt-2 flex gap-2">
                                <input wire:model="newChecklistItem" type="text" placeholder="Tambah item checklist..."
                                    class="flex-1 px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                                <x-loading-button wire:key="btn-add-checklist" type="submit" target="addChecklistItem" variant="secondary" size="sm" loadingText="...">Tambah</x-loading-button>
                            </form>
                        @endcan
                    </div>

                    {{-- Attachments --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Lampiran ({{ $task->attachments->count() }})</p>
                        <div class="space-y-2">
                            @foreach($task->attachments as $att)
                                <div class="flex items-center gap-3 px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                                    <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-700 dark:text-gray-200 truncate">{{ $att->original_name }}</p>
                                        <p class="text-xs text-gray-400">{{ $att->size_for_humans }}</p>
                                    </div>
                                    @can('tasks_view')
                                    <a href="{{ route('task-management.attachments.download', $att->id) }}" class="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400" title="Unduh">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    </a>
                                    @endcan
                                    @can('tasks_update')
                                        <button wire:key="del-att-{{ $att->id }}" wire:click="deleteAttachment({{ $att->id }})" class="text-gray-400 hover:text-red-500" title="Hapus">
                                            <span wire:loading.remove wire:target="deleteAttachment({{ $att->id }})">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </span>
                                            <x-inline-spinner wire:loading wire:target="deleteAttachment({{ $att->id }})" size="h-5 w-5" />
                                        </button>
                                    @endcan
                                </div>
                            @endforeach
                        </div>
                        @can('tasks_update')
                            <label class="mt-2 flex items-center justify-center gap-2 px-3 py-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-500 dark:text-gray-400 cursor-pointer hover:border-blue-400">
                                <span wire:loading.remove wire:target="upload">Klik untuk mengunggah berkas</span>
                                <span wire:loading wire:target="upload" class="inline-flex items-center gap-2">
                                    <x-inline-spinner size="h-4 w-4" />
                                    Mengunggah...
                                </span>
                                <input type="file" wire:model="upload" class="hidden">
                            </label>
                            <x-input-error :messages="$errors->get('upload')" class="mt-1" />
                        @endcan
                    </div>

                    {{-- Comments --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Komentar ({{ $task->comments->count() }})</p>
                        @can('tasks_comment')
                            <form wire:submit="addComment" class="mb-4 flex gap-2">
                                <input wire:model="newComment" type="text" placeholder="Tulis komentar..."
                                    class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                                <x-loading-button wire:key="btn-add-comment" type="submit" target="addComment" variant="primary" size="md" loadingText="...">Kirim</x-loading-button>
                            </form>
                        @endcan
                        <div class="space-y-3">
                            @forelse($task->comments as $comment)
                                <div class="flex gap-3 group">
                                    <x-avatar :text="$comment->user?->name ?? '?'" size="md" gradient="from-indigo-500 to-indigo-600" />
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $comment->user?->name ?? 'User' }}</span>
                                            <span class="text-xs text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $comment->body }}</p>
                                    </div>
                                    @if($comment->user_id === auth()->id() || auth()->user()->can('tasks_delete'))
                                        <button wire:key="del-comment-{{ $comment->id }}" wire:click="deleteComment({{ $comment->id }})" class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-500 flex-shrink-0">
                                            <span wire:loading.remove wire:target="deleteComment({{ $comment->id }})">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </span>
                                            <x-inline-spinner wire:loading wire:target="deleteComment({{ $comment->id }})" size="h-4 w-4" />
                                        </button>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-gray-400">Belum ada komentar.</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Activity --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Aktivitas</p>
                        <div class="space-y-2">
                            @foreach($task->activities as $activity)
                                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $activity->user?->name ?? 'Sistem' }}</span>
                                    <span>{{ $activity->description }}</span>
                                    <span class="text-gray-400">· {{ $activity->created_at->diffForHumans() }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
