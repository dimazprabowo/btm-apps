<div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex-1 w-full sm:w-auto">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari proyek..."
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
        </div>
        <x-filter-popover :filters="['statusFilter']">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                <x-searchable-select
                    wire:model.live="statusFilter"
                    :options="$this->statusOptions"
                    placeholder="Semua Status"
                    searchPlaceholder="Cari status..."
                />
            </div>
        </x-filter-popover>

        <div class="flex items-center gap-2 w-full sm:w-auto">
            @can('projects_export_excel')
                <x-loading-button wire:key="btn-export-excel" wire:click="exportExcel" target="exportExcel" variant="success" size="md" loadingText="Exporting..." title="Export Excel">
                    <x-slot:icon>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </x-slot:icon>
                    Excel
                </x-loading-button>
            @endcan
            @can('projects_export_pdf')
                <x-loading-button wire:key="btn-export-pdf" wire:click="exportPdf" target="exportPdf" variant="danger" size="md" loadingText="Exporting..." title="Export PDF">
                    <x-slot:icon>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </x-slot:icon>
                    PDF
                </x-loading-button>
            @endcan
            @can('projects_create')
                <x-loading-button wire:key="btn-create" wire:click="create" target="create" variant="primary" size="md" loadingText="Memuat..." class="flex-1 sm:flex-none">
                    <x-slot:icon>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </x-slot:icon>
                    Tambah Proyek
                </x-loading-button>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($projects as $project)
            <div x-data="{ loading: false }" x-on:livewire:navigated.window="loading = false" class="relative bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col hover:shadow-md transition-shadow">
                <div x-show="loading" x-transition.opacity class="absolute inset-0 z-20 flex items-center justify-center bg-white/40 dark:bg-gray-800/40 backdrop-blur-[2px]">
                    <x-inline-spinner size="h-5 w-5" class="text-blue-500" />
                </div>
                <div class="p-5 flex-1">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-11 h-11 rounded-lg bg-gradient-to-br {{ color_gradient_class($project->color) }} flex items-center justify-center text-white font-bold flex-shrink-0">
                                {{ strtoupper(substr($project->code, 0, 2)) }}
                            </div>
                            <div class="min-w-0">
                                <a href="{{ route('task-management.board', $project) }}" wire:navigate x-on:click="loading = true" class="block text-sm font-semibold text-gray-900 dark:text-white truncate hover:text-blue-600 dark:hover:text-blue-400">
                                    {{ $project->name }}
                                </a>
                                <span class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $project->code }}</span>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full flex-shrink-0 {{ $project->status->badgeClass() }}">
                            {{ $project->status->label() }}
                        </span>
                    </div>

                    @if($project->description)
                        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400 line-clamp-2">{{ $project->description }}</p>
                    @endif

                    <div class="mt-4 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                        <span class="inline-flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            {{ $project->tasks_count }} tugas
                        </span>
                        <span class="inline-flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-6-5.292M15 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            {{ $project->members_count }} anggota
                        </span>
                    </div>
                </div>

                <div class="px-5 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <a href="{{ route('task-management.board', $project) }}" wire:navigate x-on:click="loading = true" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1">
                        Buka Board
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <div class="flex items-center gap-2">
                        @can('projects_update')
                            <button wire:key="btn-edit-{{ $project->id }}" wire:click="edit({{ $project->id }})"
                                wire:loading.attr="disabled"
                                wire:target="edit({{ $project->id }})"
                                class="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 disabled:opacity-50"
                                title="Edit">
                                <svg wire:loading.class="hidden" wire:target="edit({{ $project->id }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                <svg wire:loading wire:target="edit({{ $project->id }})" class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </button>
                        @endcan
                        @can('projects_delete')
                            @if($project->owner_id === auth()->id())
                                <button wire:key="btn-del-{{ $project->id }}" wire:click="confirmDelete({{ $project->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="confirmDelete({{ $project->id }})"
                                    class="text-gray-400 hover:text-red-600 dark:hover:text-red-400 disabled:opacity-50"
                                    title="Hapus">
                                    <svg wire:loading.class="hidden" wire:target="confirmDelete({{ $project->id }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    <svg wire:loading wire:target="confirmDelete({{ $project->id }})" class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                </button>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white dark:bg-gray-800 rounded-xl shadow-sm p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Belum ada proyek. Buat proyek pertama Anda.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $projects->links() }}
    </div>

    <x-delete-modal :show="$showDeleteModal" wire:model="showDeleteModal"
        title="Hapus Proyek" message="Apakah Anda yakin ingin menghapus proyek"
        :itemName="$deletingProjectName" confirmMethod="delete" />
</div>
