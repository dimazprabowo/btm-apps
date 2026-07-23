@if($showTaskModal)
    <div class="fixed inset-0 z-[60] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75" wire:click="closeTaskModal"></div>

            <div class="inline-block align-bottom w-full bg-white dark:bg-gray-800 rounded-lg text-left shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <form wire:submit="saveTask">
                    <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            {{ $editMode ? ($parentId ? 'Edit Subtugas' : 'Edit Tugas') : ($parentId ? 'Tambah Subtugas' : 'Tambah Tugas') }}
                        </h3>

                        <div>
                            <x-input-label for="title" value="Judul" :required="true" />
                            <x-text-input id="title" wire:model="title" type="text" placeholder="Masukkan judul tugas" class="mt-1 w-full" />
                            <x-input-error :messages="$errors->get('title')" class="mt-1" />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="description" value="Deskripsi" />
                            <textarea id="description" wire:model="description" rows="3" placeholder="Masukkan deskripsi tugas"
                                class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-1" />
                        </div>

                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label value="Status" />
                                <x-searchable-select wire:model="status_id" :options="$this->statusColumnOptions" placeholder="Pilih status" />
                                <x-input-error :messages="$errors->get('status_id')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label value="Prioritas" :required="true" />
                                <x-searchable-select wire:model="priority" :options="$this->priorityOptions" placeholder="Pilih prioritas" />
                                <x-input-error :messages="$errors->get('priority')" class="mt-1" />
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="start_date" value="Tanggal Mulai" />
                                <x-text-input id="start_date" wire:model="start_date" type="date" class="mt-1 w-full" />
                                <x-input-error :messages="$errors->get('start_date')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="due_date" value="Jatuh Tempo" />
                                <x-text-input id="due_date" wire:model="due_date" type="date" class="mt-1 w-full" />
                                <x-input-error :messages="$errors->get('due_date')" class="mt-1" />
                            </div>
                        </div>

                        @can('tasks_assign')
                        <div class="mt-4">
                            <x-input-label value="Penerima Tugas" />
                            <x-multi-searchable-select wire:model="assignee_ids" :options="$this->memberOptions"
                                placeholder="Pilih penerima..." searchPlaceholder="Cari anggota..." />
                            <x-input-error :messages="$errors->get('assignee_ids')" class="mt-1" />
                        </div>
                        @endcan

                        @if(!empty($this->labelOptions))
                            <div class="mt-4">
                                <x-input-label value="Label" />
                                <x-multi-searchable-select wire:model="label_ids" :options="$this->labelOptions"
                                    placeholder="Pilih label..." searchPlaceholder="Cari label..." />
                                <x-input-error :messages="$errors->get('label_ids')" class="mt-1" />
                            </div>
                        @endif
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <x-loading-button wire:key="btn-save-task" type="submit" target="saveTask" variant="primary" size="lg" loadingText="Menyimpan..." class="w-full sm:w-auto">
                            {{ $editMode ? 'Update' : 'Simpan' }}
                        </x-loading-button>
                        <x-cancel-button wire:key="btn-cancel-task" wire:click="closeTaskModal" target="closeTaskModal" class="mt-3 sm:mt-0 w-full sm:w-auto" />
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
