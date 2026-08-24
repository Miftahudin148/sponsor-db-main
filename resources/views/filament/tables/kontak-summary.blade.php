@php
    /** @var array<int, string> $warnaBaris */
    $warnaTervalidasi = collect($warnaBaris ?? [])
        ->filter(fn (string $hex): bool => preg_match('/^#[0-9a-fA-F]{6}$/', $hex) === 1)
        ->values();
@endphp

@if ($warnaTervalidasi->isNotEmpty())
    {{-- Pewarnaan baris menurut kegiatan/kategori; tint tipis agar teks tetap terbaca. --}}
    <style>
        @foreach ($warnaTervalidasi as $hex)
            tr.{{ \App\Filament\Resources\Kontaks\Tables\KontaksTable::kelasWarnaBaris($hex) }} > td {
                background-color: {{ strtolower($hex) }}1f !important;
            }
        @endforeach
    </style>
@endif

<div class="fi-ta-kontak-summary grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
    @foreach ($cards as $card)
        <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
            <span
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                style="background-color: {{ $card['color'] }}1a"
            >
                <x-filament::icon :icon="$card['icon']" class="h-5 w-5" style="color: {{ $card['color'] }}" />
            </span>

            <div class="min-w-0">
                <div class="text-xl font-semibold leading-none tabular-nums text-gray-950 dark:text-white">
                    {{ number_format($card['count'], 0, ',', '.') }}
                </div>
                <div class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400">
                    {{ $card['label'] }}
                </div>
            </div>
        </div>
    @endforeach
</div>