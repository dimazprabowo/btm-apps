<x-app-layout title="{{ isset($project) ? 'Edit Proyek' : 'Tambah Proyek' }}">
    <x-slot name="header">
        <div class="flex items-center gap-2 text-lg sm:text-xl min-w-0">
            <a href="{{ route('task-management.projects') }}" wire:navigate x-data="{ loading: false }" @click="loading = true" @navigate-start.window="loading = true" @navigate-complete.window="loading = false" class="flex items-center gap-2 min-w-0 text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                <span class="inline-flex items-center justify-center flex-shrink-0">
                    <template x-if="!loading">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </template>
                    <template x-if="loading">
                        <x-inline-spinner size="h-5 w-5" />
                    </template>
                </span>
                <span class="hidden sm:inline font-semibold whitespace-nowrap">Manajemen Tugas</span>
                <span class="hidden sm:inline text-gray-400">/</span>
                <h2 class="font-semibold text-gray-800 dark:text-gray-200 leading-tight truncate">
                    {{ isset($project) ? 'Edit Proyek' : 'Tambah Proyek' }}
                </h2>
            </a>
        </div>
    </x-slot>

    <livewire:task-management.project-form :project="$project ?? null" />
</x-app-layout>
