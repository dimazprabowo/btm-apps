<div x-data="{ draggingId: null }">

    {{-- Toolbar --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center gap-3">
        {{-- View Mode Toggle --}}
        <div class="flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden w-full sm:w-auto">
            <button wire:key="tab-board" wire:click="setViewMode('board')" class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-sm font-medium {{ $viewMode === 'board' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300' }}">
                <span wire:loading.remove wire:target="setViewMode('board')">Board</span>
                <x-inline-spinner wire:loading wire:target="setViewMode('board')" size="h-4 w-4" />
            </button>
            <button wire:key="tab-list" wire:click="setViewMode('list')" class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-sm font-medium {{ $viewMode === 'list' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300' }}">
                <span wire:loading.remove wire:target="setViewMode('list')">List</span>
                <x-inline-spinner wire:loading wire:target="setViewMode('list')" size="h-4 w-4" />
            </button>
        </div>

        {{-- Search --}}
        <div class="flex-1 w-full sm:w-auto">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari tugas..."
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
        </div>

        {{-- Filter Popover --}}
        <x-filter-popover :filters="['priorityFilter', 'assigneeFilter']">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Prioritas</label>
                <x-searchable-select
                    wire:model.live="priorityFilter"
                    :options="$this->priorityOptions"
                    placeholder="Semua Prioritas"
                    searchPlaceholder="Cari prioritas..."
                />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Penerima</label>
                <x-searchable-select
                    wire:model.live="assigneeFilter"
                    :options="$this->memberOptions"
                    placeholder="Semua Penerima"
                    searchPlaceholder="Cari penerima..."
                />
            </div>
        </x-filter-popover>

        {{-- Action Buttons --}}
        <div class="flex items-center gap-2 w-full sm:w-auto">
            @can('tasks_export_excel')
                <x-loading-button wire:key="btn-export-excel" wire:click="exportExcel" target="exportExcel" variant="success" size="md" loadingText="Exporting..." title="Export Excel">
                    <x-slot:icon>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </x-slot:icon>
                    Excel
                </x-loading-button>
            @endcan
            @can('tasks_export_pdf')
                <x-loading-button wire:key="btn-export-pdf" wire:click="exportPdf" target="exportPdf" variant="danger" size="md" loadingText="Exporting..." title="Export PDF">
                    <x-slot:icon>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </x-slot:icon>
                    PDF
                </x-loading-button>
            @endcan
            @can('tasks_create')
                <x-loading-button wire:key="btn-create-task" wire:click="createTask" target="createTask" variant="primary" size="md" loadingText="Memuat..." class="flex-1 sm:flex-none">
                    <x-slot:icon>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </x-slot:icon>
                    Tambah Tugas
                </x-loading-button>
            @endcan
        </div>
    </div>

    {{-- BOARD VIEW --}}
    @if($viewMode === 'board')
        <div class="flex gap-3 sm:gap-4 overflow-x-auto pb-4 items-start snap-x snap-mandatory">
            @foreach($this->statuses as $status)
                <div class="w-72 sm:w-80 flex-shrink-0 snap-start bg-gray-100 dark:bg-gray-900/50 rounded-xl"
                    x-on:dragover.prevent
                    x-on:drop="$wire.dropTask(draggingId, {{ $status->id }}); draggingId = null">
                    <div class="px-4 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full {{ color_dot_class($status->color) }}"></span>
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $status->name }}</span>
                            <span class="text-xs text-gray-400">{{ $status->tasks->count() }}</span>
                        </div>
                        @can('tasks_create')
                            <button wire:key="col-add-{{ $status->id }}" wire:click="createTask({{ $status->id }})" class="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400" title="Tambah di kolom ini">
                                <span wire:loading.remove wire:target="createTask({{ $status->id }})">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                </span>
                                <x-inline-spinner wire:loading wire:target="createTask({{ $status->id }})" size="h-4 w-4" />
                            </button>
                        @endcan
                    </div>

                    <div class="px-2 pb-2 space-y-2 min-h-[60px]">
                        @forelse($status->tasks as $task)
                            <div draggable="true"
                                x-on:dragstart="draggingId = {{ $task->id }}"
                                wire:key="task-{{ $task->id }}"
                                wire:click="openTask({{ $task->id }})"
                                class="relative bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm border border-gray-200 dark:border-gray-700 cursor-pointer hover:border-blue-400 dark:hover:border-blue-500">
                                <div class="absolute inset-0 z-10 flex items-center justify-center rounded-lg bg-white/40 dark:bg-gray-800/40 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-150"
                                     wire:loading.class="opacity-100"
                                     wire:loading.class.remove="opacity-0 pointer-events-none"
                                     wire:target="openTask({{ $task->id }})">
                                    <x-inline-spinner size="h-5 w-5" class="text-blue-500" />
                                </div>
                                <div class="flex items-start justify-between gap-2">
                                    <span class="text-xs font-mono text-gray-400">{{ $task->code }}</span>
                                    <span class="px-1.5 py-0.5 text-[10px] font-medium rounded {{ $task->priority->badgeClass() }}">
                                        {{ $task->priority->label() }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $task->title }}</p>

                                @if($task->labels->isNotEmpty())
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        @foreach($task->labels as $label)
                                            <span class="px-1.5 py-0.5 text-[10px] rounded {{ color_badge_class($label->color) }}">{{ $label->name }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="mt-2 flex items-center justify-between text-xs text-gray-400">
                                    <div class="flex items-center gap-2">
                                        @if($task->due_date)
                                            <span class="inline-flex items-center gap-1 {{ $task->is_overdue ? 'text-red-500' : '' }}">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                {{ $task->due_date->format('d M') }}
                                            </span>
                                        @endif
                                        @if($task->subtasks_count > 0)
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>{{ $task->subtasks_count }}
                                            </span>
                                        @endif
                                        @if($task->comments_count > 0)
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4-.8L3 20l1.3-3.9A7.96 7.96 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>{{ $task->comments_count }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex -space-x-1">
                                        @foreach($task->assignees->take(3) as $assignee)
                                            <x-avatar :text="$assignee->name" size="sm" class="ring-2 ring-white dark:ring-gray-800" />
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-xs text-gray-400 py-6">Tarik tugas ke sini</div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- LIST VIEW --}}
        {{-- Mobile: card layout --}}
        <div class="sm:hidden space-y-3">
            @foreach($this->statuses as $status)
                @foreach($status->tasks as $task)
                    <div wire:key="card-{{ $task->id }}" wire:click="openTask({{ $task->id }})"
                        class="relative bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700 cursor-pointer hover:border-blue-400 dark:hover:border-blue-500">
                        <div class="absolute inset-0 z-10 flex items-center justify-center rounded-lg bg-white/40 dark:bg-gray-800/40 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-150"
                             wire:loading.class="opacity-100"
                             wire:loading.class.remove="opacity-0 pointer-events-none"
                             wire:target="openTask({{ $task->id }})">
                            <x-inline-spinner size="h-5 w-5" class="text-blue-500" />
                        </div>
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <span class="text-xs font-mono text-gray-400">{{ $task->code }}</span>
                            <span class="px-1.5 py-0.5 text-[10px] font-medium rounded {{ $task->priority->badgeClass() }}">{{ $task->priority->label() }}</span>
                        </div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white mb-3">{{ $task->title }}</p>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300">
                                    <span class="w-2 h-2 rounded-full {{ color_dot_class($status->color) }}"></span>{{ $status->name }}
                                </span>
                                @if($task->due_date)
                                    <span class="inline-flex items-center gap-1 text-xs {{ $task->is_overdue ? 'text-red-500' : 'text-gray-400' }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        {{ $task->due_date->format('d M Y') }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex -space-x-1">
                                @forelse($task->assignees->take(3) as $assignee)
                                    <x-avatar :text="$assignee->name" size="sm" class="ring-2 ring-white dark:ring-gray-800" />
                                @empty
                                    <span class="text-xs text-gray-400">-</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>

        {{-- Desktop: table layout --}}
        <div class="hidden sm:block bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kode</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Judul</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Prioritas</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Penerima</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Jatuh Tempo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->statuses as $status)
                            @foreach($status->tasks as $task)
                                <tr wire:key="row-{{ $task->id }}" wire:click="openTask({{ $task->id }})" class="hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                    <td class="hidden px-4 py-3" colspan="6" wire:loading.class.remove="hidden" wire:target="openTask({{ $task->id }})">
                                        <div class="flex items-center justify-center">
                                            <x-inline-spinner size="h-5 w-5" class="text-blue-500" />
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-xs font-mono text-gray-400 whitespace-nowrap" wire:loading.class="hidden" wire:target="openTask({{ $task->id }})">{{ $task->code }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white" wire:loading.class="hidden" wire:target="openTask({{ $task->id }})">{{ $task->title }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap" wire:loading.class="hidden" wire:target="openTask({{ $task->id }})">
                                        <span class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-300">
                                            <span class="w-2 h-2 rounded-full {{ color_dot_class($status->color) }}"></span>{{ $status->name }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap" wire:loading.class="hidden" wire:target="openTask({{ $task->id }})">
                                        <span class="px-2 py-0.5 text-xs font-medium rounded {{ $task->priority->badgeClass() }}">{{ $task->priority->label() }}</span>
                                    </td>
                                    <td class="px-4 py-3" wire:loading.class="hidden" wire:target="openTask({{ $task->id }})">
                                        <div class="flex -space-x-1">
                                            @forelse($task->assignees->take(3) as $assignee)
                                                <x-avatar :text="$assignee->name" size="sm" class="ring-2 ring-white dark:ring-gray-800" />
                                            @empty
                                                <span class="text-xs text-gray-400">-</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm whitespace-nowrap {{ $task->is_overdue ? 'text-red-500' : 'text-gray-600 dark:text-gray-300' }}" wire:loading.class="hidden" wire:target="openTask({{ $task->id }})">
                                        {{ $task->due_date ? $task->due_date->format('d M Y') : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @include('livewire.task-management.partials.task-modal')
    @include('livewire.task-management.partials.task-detail')

    <x-delete-modal :show="$showDeleteModal" wire:model="showDeleteModal"
        title="Hapus Tugas" message="Apakah Anda yakin ingin menghapus tugas"
        :itemName="$deletingTaskTitle" confirmMethod="deleteTask" />
</div>
