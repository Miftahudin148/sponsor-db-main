<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Status Verifikasi Kontak
        </x-slot>

        <x-slot name="description">
            Alur status kontak sponsor: terverifikasi &middot; perlu dicek &middot; tidak aktif
        </x-slot>

        <div class="space-y-5">
            @foreach ($rows as $row)
                <div>
                    <div class="mb-1 flex items-center justify-between gap-4 text-sm">
                        <span class="font-medium text-gray-950 dark:text-white">{{ $row['label'] }}</span>
                        <span class="text-gray-500 dark:text-gray-400 tabular-nums">
                            {{ number_format($row['count'], 0, ',', '.') }}
                            <span class="text-gray-400 dark:text-gray-500">({{ $row['percent'] }}%)</span>
                        </span>
                    </div>
                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                        <div
                            class="h-full rounded-full transition-all duration-700"
                            style="width: {{ max($row['percent'], 0) }}%; background-color: {{ $row['color'] }}"
                        ></div>
                    </div>
                </div>
            @endforeach

            @if ($total === 0)
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Belum ada data kontak. Import atau tambahkan kontak terlebih dahulu.
                </p>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>