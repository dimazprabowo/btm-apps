<div>
    <form wire:submit="save">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Informasi Dasar</h3>
            </div>
            <div class="p-4 sm:p-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="code" value="Kode Proyek" :required="true" />
                        <x-text-input id="code" wire:model="code" type="text" placeholder="PRJ"
                            class="mt-1 w-full uppercase" />
                        <x-input-error :messages="$errors->get('code')" class="mt-1" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="name" value="Nama Proyek" :required="true" />
                        <x-text-input id="name" wire:model="name" type="text" placeholder="Masukkan nama proyek"
                            class="mt-1 w-full" />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>
                </div>

                <div>
                    <x-input-label value="Pemilik Proyek" />
                    <div class="mt-1 flex items-center gap-2 px-3 py-2 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-semibold text-xs">
                            {{ strtoupper(substr($this->ownerName, 0, 2)) }}
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $this->ownerName }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Pemilik proyek otomatis menjadi manajer dan tidak dapat diubah.</p>
                </div>

                <div>
                    <x-input-label for="description" value="Deskripsi" />
                    <textarea id="description" wire:model="description" rows="3" placeholder="Masukkan deskripsi proyek"
                        class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-1" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="status" value="Status" :required="true" />
                        <x-searchable-select wire:model="status" :options="$this->statusOptions"
                            placeholder="Pilih status" />
                        <x-input-error :messages="$errors->get('status')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label value="Warna" :required="true" />
                        <div class="mt-2 flex items-center gap-2">
                            @foreach(['blue','green','red','amber','indigo','purple','gray'] as $c)
                                <button type="button" wire:key="color-{{ $c }}" wire:click="$set('color', '{{ $c }}')"
                                    class="w-7 h-7 rounded-full bg-gradient-to-br {{ color_gradient_class($c) }} {{ $color === $c ? 'ring-2 ring-offset-2 ring-gray-900 dark:ring-white dark:ring-offset-gray-800' : '' }}"></button>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('color')" class="mt-1" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="start_date" value="Tanggal Mulai" />
                        <x-text-input id="start_date" wire:model="start_date" type="date"
                            class="mt-1 w-full" />
                        <x-input-error :messages="$errors->get('start_date')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="end_date" value="Tanggal Selesai" />
                        <x-text-input id="end_date" wire:model="end_date" type="date"
                            class="mt-1 w-full" />
                        <x-input-error :messages="$errors->get('end_date')" class="mt-1" />
                    </div>
                </div>
            </div>
        </div>

        @can('projects_manage_members')
        <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Anggota Proyek</h3>
            </div>
            <div class="p-4 sm:p-6">
                <x-multi-searchable-select wire:model="member_ids" :options="$this->userOptions"
                    placeholder="Pilih anggota..." searchPlaceholder="Cari user..." />
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Pemilik proyek otomatis menjadi manajer.</p>
                <x-input-error :messages="$errors->get('member_ids')" class="mt-1" />
            </div>
        </div>
        @endcan

        <div class="mt-6 flex items-center justify-end gap-3">
            <x-cancel-button wire:click="cancel" target="cancel" class="w-full sm:w-auto" wire:key="btn-cancel" />
            <x-loading-button type="submit" target="save" variant="primary" size="lg" loadingText="Menyimpan..." class="w-full sm:w-auto" wire:key="btn-save">
                {{ $editMode ? 'Update' : 'Simpan' }}
            </x-loading-button>
        </div>
    </form>
</div>
