<x-filament-panels::page>
    @php $user = auth()->user(); @endphp

    {{-- Header ringkas — beda dari tabel admin --}}
    <div class="rounded-3xl border border-gray-200 bg-gradient-to-br from-white via-white to-primary-50/40 p-6 shadow-sm dark:border-gray-700 dark:from-gray-800 dark:via-gray-800 dark:to-primary-950/20 sm:p-7">
        <div class="flex flex-col items-center gap-5 sm:flex-row">
            <div class="relative shrink-0">
                @if($user->avatar_url && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar_url))
                    <img src="{{ Storage::disk('public')->url($user->avatar_url) }}" alt="Avatar" class="h-24 w-24 rounded-full object-cover ring-4 ring-primary-100 dark:ring-primary-900" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="hidden h-24 w-24 items-center justify-center rounded-full bg-primary-600 text-xl font-bold text-white ring-4 ring-primary-100 dark:ring-primary-900" style="display:none">
                        {{ strtoupper(collect(explode(' ', $user->name))->map(fn($p) => substr($p, 0, 1))->take(2)->implode('')) }}
                    </div>
                @else
                    <div class="flex h-24 w-24 items-center justify-center rounded-full bg-primary-600 text-xl font-bold text-white ring-4 ring-primary-100 dark:ring-primary-900">
                        {{ strtoupper(collect(explode(' ', $user->name))->map(fn($p) => substr($p, 0, 1))->take(2)->implode('')) }}
                    </div>
                @endif
                <div class="absolute -bottom-1 -right-1 flex h-7 w-7 items-center justify-center rounded-full bg-emerald-500 text-white shadow">
                    <x-filament::icon alias="heroicon-o-check" class="h-4 w-4" />
                </div>
            </div>
            <div class="text-center sm:text-left">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Halo, {{ explode(' ', $user->name)[0] }} 👋</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $user->email }} · {{ $user->divisi?->name ?? '-' }}</p>
            </div>
            <div class="ml-auto hidden items-center gap-2 sm:flex">
                <span class="rounded-full px-3 py-1 text-xs font-medium {{ $user->is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30' : 'bg-red-50 text-red-700' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                <a href="{{ \App\Filament\Resources\ActivityLogs\ActivityLogResource::getUrl('index') }}" wire:navigate class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">Histori →</a>
            </div>
        </div>
        <p class="mt-4 rounded-2xl bg-amber-50 p-3 text-xs leading-relaxed text-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
            <strong>Tips:</strong> Klik foto untuk <strong>atur posisi & bentuk lingkaran manual</strong> sebelum simpan. Foto otomatis di-resize ke 400px. <strong>DIVISI</strong> hanya bisa diubah Admin.
        </p>
    </div>

    {{-- Form Filament dengan imageEditor manual --}}
    <form wire:submit="save" class="mt-6 space-y-6">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button type="submit" color="primary" icon="heroicon-o-check">
                Simpan Perubahan
            </x-filament::button>
        </div>
    </form>

    <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-4 text-xs leading-relaxed text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
        Gunakan <kbd class="rounded bg-gray-100 px-1 py-0.5 dark:bg-gray-700">Ctrl+K</kbd> untuk cari kontak cepat. Perubahan profil tercatat di Histori.
    </div>
</x-filament-panels::page>
