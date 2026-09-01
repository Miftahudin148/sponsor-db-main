<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    @php
        $record = $getRecord();
        $props = $record?->properties?->toArray() ?? [];
        $old = $props['old'] ?? [];
        $attributes = $props['attributes'] ?? [];
        $event = $record?->event ?? '';
        $isCreated = $event === 'created';
        $isDeleted = $event === 'deleted';
        $changedKeys = array_unique(array_merge(array_keys($old), array_keys($attributes)));
    @endphp

    @if(empty($old) && empty($attributes))
        <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-6 text-center dark:border-gray-700 dark:bg-gray-900">
            <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                <x-filament::icon alias="heroicon-o-information-circle" class="h-5 w-5 text-gray-400" />
            </div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Tidak ada detail perubahan tercatat</p>
            <p class="text-xs text-gray-400">Aktivitas ini mungkin dari sistem atau data lama.</p>
        </div>
    @elseif($isCreated)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
            <div class="flex items-center gap-2 text-sm font-semibold text-emerald-700 dark:text-emerald-300">
                <x-filament::icon alias="heroicon-o-plus-circle" class="h-4 w-4" />
                Data baru ditambahkan
            </div>
            <dl class="mt-3 grid gap-2">
                @foreach($attributes as $key => $value)
                    <div class="flex gap-3 rounded-lg bg-white p-2.5 dark:bg-gray-800">
                        <dt class="w-36 shrink-0 text-xs font-medium text-gray-500 dark:text-gray-400">{{ \App\Filament\Resources\ActivityLogs\ActivityLogResource::labelField($key) }}</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white break-all">{{ $value === null || $value === '' ? '—' : (is_bool($value) ? ($value ? 'Ya' : 'Tidak') : (string) $value) }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    @elseif($isDeleted)
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950/30">
            <div class="flex items-center gap-2 text-sm font-semibold text-red-700 dark:text-red-300">
                <x-filament::icon alias="heroicon-o-trash" class="h-4 w-4" />
                Data dihapus
            </div>
            @if(!empty($old))
                <dl class="mt-3 grid gap-2">
                    @foreach($old as $key => $value)
                        <div class="flex gap-3 rounded-lg bg-white p-2.5 dark:bg-gray-800">
                            <dt class="w-36 shrink-0 text-xs font-medium text-gray-500 dark:text-gray-400">{{ \App\Filament\Resources\ActivityLogs\ActivityLogResource::labelField($key) }}</dt>
                            <dd class="text-sm text-gray-700 dark:text-gray-300 break-all">{{ $value === null || $value === '' ? '—' : (string) $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            @else
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">Detail data yang dihapus tidak tersedia.</p>
            @endif
        </div>
    @else
        {{-- Diperbarui — tampilkan tabel perbandingan Sebelum → Sesudah --}}
        <div class="overflow-hidden rounded-xl border border-amber-200 bg-amber-50/50 dark:border-amber-900 dark:bg-amber-950/20">
            <div class="border-b border-amber-200 bg-amber-100 px-4 py-2.5 dark:border-amber-900 dark:bg-amber-900/30">
                <div class="flex items-center gap-2 text-sm font-semibold text-amber-800 dark:text-amber-200">
                    <x-filament::icon alias="heroicon-o-pencil-square" class="h-4 w-4" />
                    {{ count($changedKeys) }} field diperbarui
                </div>
            </div>
            <div class="divide-y divide-amber-100 dark:divide-amber-900/50">
                @foreach($changedKeys as $key)
                    @php $oldVal = $old[$key] ?? null; $newVal = $attributes[$key] ?? null; $hasOld = array_key_exists($key, $old); @endphp
                    <div class="grid grid-cols-1 gap-1 px-4 py-3 sm:grid-cols-[140px_1fr] sm:items-center">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ \App\Filament\Resources\ActivityLogs\ActivityLogResource::labelField($key) }}
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            @if($hasOld)
                                <span class="rounded-lg bg-white px-2.5 py-1 font-mono text-gray-500 line-through decoration-red-400 dark:bg-gray-800 dark:text-gray-400">{{ $oldVal === null || $oldVal === '' ? '— kosong' : (is_bool($oldVal) ? ($oldVal ? 'Ya' : 'Tidak') : (string) $oldVal) }}</span>
                                <x-filament::icon alias="heroicon-o-arrow-right" class="h-3 w-3 text-amber-600" />
                            @endif
                            <span class="rounded-lg bg-white px-2.5 py-1 font-medium font-mono text-emerald-700 ring-1 ring-emerald-200 dark:bg-gray-800 dark:text-emerald-300 dark:ring-emerald-900">{{ $newVal === null || $newVal === '' ? '— kosong' : (is_bool($newVal) ? ($newVal ? 'Ya' : 'Tidak') : (string) $newVal) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">Tip: nilai dicoret = sebelum, hijau = sesudah. Karyawan hanya melihat histori miliknya sendiri.</p>
    @endif
</x-dynamic-component>
