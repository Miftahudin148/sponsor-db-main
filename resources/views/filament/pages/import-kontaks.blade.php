<x-filament-panels::page>
    @if ($this->saved && $this->saveResult)
        <div class="rounded-xl border border-primary-600/20 bg-primary-50 p-5 text-primary-900">
            <div class="flex items-center gap-2 text-base font-semibold">
                <x-filament::icon alias="heroicon-o-check-circle" class="h-5 w-5 text-success-600" />
                Import selesai
            </div>
            <p class="mt-1 text-sm text-primary-700">
                {{ $this->saveResult['kontak_dibuat'] }} kontak baru disimpan,
                {{ $this->saveResult['perusahaan_dibuat'] }} perusahaan baru dibuat,
                {{ $this->saveResult['dilewati'] }} baris dilewati (duplikat / data tidak lengkap).
            </p>
            <p class="mt-1 text-sm text-primary-700">
                Kontak baru otomatis berstatus verifikasi <strong>perlu_dicek</strong>.
            </p>
            <div class="mt-4">
                <x-filament::button color="gray" wire:click="resetImport">
                    Import file lain
                </x-filament::button>
            </div>
        </div>
    @elseif ($this->previews)
        <x-filament::section heading="Langkah 2 dari 2 — Simpan Hasil Import" :description="'Cek apakah kontak baru, cocok dengan perusahaan yang ada, atau duplikat; lalu simpan.'">
            <div class="flex flex-wrap items-center gap-3">
                <x-filament::button color="success" wire:click="saveImport">
                    Simpan {{ $this->counts['baru'] + $this->counts['cocok'] }} kontak baru
                </x-filament::button>
                <x-filament::button color="gray" wire:click="backToPreview">
                    Kembali ke pratinjau
                </x-filament::button>
                <x-filament::button color="gray" wire:click="resetImport">
                    Ganti file
                </x-filament::button>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <x-filament::section class="sm:col-span-1" :heading="'Akan dibuat'" :description="$this->counts['baru'] . ' perusahaan / kontak baru'">
                    <span class="text-3xl font-bold">{{ $this->counts['baru'] }}</span>
                </x-filament::section>
                <x-filament::section class="sm:col-span-1" :heading="'Dihubungkan'" :description="'ke perusahaan yang sudah ada'">
                    <span class="text-3xl font-bold">{{ $this->counts['cocok'] }}</span>
                </x-filament::section>
                <x-filament::section class="sm:col-span-1" :heading="'Duplikat dilewati'" :description="'nomor / nama sudah ada'">
                    <span class="text-3xl font-bold">{{ $this->counts['duplikat'] }}</span>
                </x-filament::section>
                <x-filament::section class="sm:col-span-1" :heading="'Data tidak lengkap'" :description="'baris dilewati'">
                    <span class="text-3xl font-bold">{{ $this->counts['data_tidak_lengkap'] }}</span>
                </x-filament::section>
                <x-filament::section class="sm:col-span-1" :heading="'Nomor tak valid'" :description="'tetap disimpan dgn flag'">
                    <span class="text-3xl font-bold">{{ $this->counts['nomor_tidak_valid'] }}</span>
                </x-filament::section>
            </div>

            @if ($this->editingIndex !== null)
                <div class="mt-4 rounded-xl border border-primary-600/30 bg-primary-50/50 p-4 dark:border-primary-500/30 dark:bg-primary-900/10" wire:key="edit-panel-{{ $this->editingIndex }}">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-primary-800 dark:text-primary-200">Edit baris hasil analisis</p>
                        <p class="text-xs text-primary-600 dark:text-primary-300">
                            Setelah disimpan, baris dianalisis ulang (kelengkapan, duplikat, validitas nomor).
                        </p>
                    </div>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">Nama perusahaan</label>
                            <input type="text" wire:model.blur="editNamaPerusahaan" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">Industri</label>
                            <input type="text" wire:model.blur="editIndustri" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">Nama kontak</label>
                            <input type="text" wire:model.blur="editNama" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">No. telepon (mentah)</label>
                            <input type="text" wire:model.blur="editNoTelepon" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">Catatan</label>
                            <input type="text" wire:model.blur="editCatatan" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <x-filament::button color="primary" wire:click="saveEdit">
                            Simpan perubahan
                        </x-filament::button>
                        <x-filament::button color="gray" wire:click="cancelEdit">
                            Batal
                        </x-filament::button>
                    </div>
                </div>
            @endif

            <div class="mt-4 flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                <div class="relative min-w-[200px] flex-1">
                    <x-filament::icon alias="heroicon-o-magnifying-glass" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="searchPreviews"
                        placeholder="Cari perusahaan, kontak, nomor, alasan..."
                        class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                    >
                </div>
                <select wire:model.live="sheetFilterPreviews" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                    <option value="">Semua sheet</option>
                    @foreach ($this->previewSheetOptions() as $sheet)
                        <option value="{{ $sheet }}">{{ $sheet }}</option>
                    @endforeach
                </select>
                <select wire:model.live="companyFilterPreviews" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                    <option value="">Semua perusahaan</option>
                    <option value="baru">Perusahaan baru</option>
                    <option value="cocok">Perusahaan sudah ada</option>
                </select>
                <select wire:model.live="statusFilterPreviews" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                    <option value="">Semua keputusan</option>
                    @foreach ($this->statusFilterOptions() as $status => $label)
                        <option value="{{ $status }}">{{ $label }}</option>
                    @endforeach
                </select>
                @if ($this->searchPreviews !== '' || $this->sheetFilterPreviews !== '' || $this->statusFilterPreviews !== '' || $this->companyFilterPreviews !== '')
                    <x-filament::button color="gray" size="sm" wire:click="resetFilters">
                        Reset filter
                    </x-filament::button>
                @endif
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    Menampilkan <strong>{{ count($this->filteredPreviews()) }}</strong> dari {{ count($this->previews) }} baris
                    <x-filament::loading-indicator
                        wire:loading
                        wire:target="searchPreviews,sheetFilterPreviews,companyFilterPreviews,statusFilterPreviews"
                        class="h-4 w-4 text-primary-500"
                        style="display:none"
                    />
                </span>
            </div>

            <div class="mt-2 overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/40">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide">Sheet</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide">Baris</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide">Perusahaan</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide">Kontak</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide">No. Telepon</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide">Keputusan</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->filteredPreviews() as $index => $p)
                            @php
                                $perusahaanTone = $p['perusahaan_status'] === 'cocok' ? 'success' : 'warning';
                                $kontakTone = match ($p['status_kontak']) {
                                    'dibuat' => 'success',
                                    'duplikat_telepon', 'duplikat_nama', 'duplikat_batch' => 'gray',
                                    'data_tidak_lengkap' => 'danger',
                                    default => 'gray',
                                };
                            @endphp
                            <tr class="border-b border-gray-100 dark:border-gray-700 {{ $this->editingIndex === $index ? 'bg-primary-50/60 dark:bg-primary-900/10' : '' }}" wire:key="preview-row-{{ $index }}">
                                <td class="px-4 py-2 text-gray-500">{{ $p['sheet'] ?? '-' }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ $p['baris'] }}</td>
                                <td class="px-4 py-2">
                                    {{ $p['nama_perusahaan'] }}
                                    <x-filament::badge :color="$perusahaanTone" size="sm">
                                        {{ $p['perusahaan_status'] === 'cocok' ? 'sudah ada' : 'baru' }}
                                    </x-filament::badge>
                                </td>
                                <td class="px-4 py-2 font-medium">{{ $p['nama'] }}</td>
                                <td class="px-4 py-2">
                                    <span class="font-mono">{{ $p['no_telepon'] ?: '-' }}</span>
                                    @if ($p['no_telepon_valid'])
                                        <x-filament::badge color="success" size="sm">valid</x-filament::badge>
                                    @elseif ($p['no_telepon'] !== '')
                                        <x-filament::badge color="warning" size="sm">tak valid</x-filament::badge>
                                    @endif
                                </td>
                                <td class="px-4 py-2">
                                    <x-filament::badge :color="$kontakTone" size="sm">
                                        {{ $this->kontakStatusLabel($p['status_kontak'], $p['perusahaan_status']) }}
                                    </x-filament::badge>
                                    @if ($p['alasan'])
                                        <p class="mt-1 text-xs text-gray-500">{{ $p['alasan'] }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <x-filament::icon-button
                                            icon="heroicon-o-pencil-square"
                                            tooltip="Edit baris"
                                            size="sm"
                                            color="gray"
                                            wire:click="startEdit({{ $index }})"
                                        />
                                        <x-filament::icon-button
                                            icon="heroicon-o-trash"
                                            tooltip="Hapus baris"
                                            size="sm"
                                            color="danger"
                                            wire:click="deletePreview({{ $index }})"
                                            wire:confirm="Hapus baris ini dari analisis? Baris tidak akan disimpan."
                                        />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">Tidak ada baris yang cocok dengan filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @elseif ($this->rows)
        <x-filament::section heading="Pratinjau Baris Mentah" :description="'Hasil ekstraksi file sebelum analisis. Kembali ke hasil analisis untuk melanjutkan menyimpan.'">
            <div class="flex flex-wrap items-center gap-3">
                <x-filament::button color="primary" wire:click="analyze">
                    Kembali ke hasil analisis
                </x-filament::button>
                <x-filament::button color="gray" wire:click="resetImport">
                    Ganti file
                </x-filament::button>
            </div>

            <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                Terkumpul <strong>{{ count($this->rows) }}</strong> baris dari file yang dipilih.
            </p>

            <div class="mt-3 flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                <div class="relative min-w-[200px] flex-1">
                    <x-filament::icon alias="heroicon-o-magnifying-glass" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="searchRows"
                        placeholder="Cari perusahaan, nama, nomor, catatan..."
                        class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                    >
                </div>
                <select wire:model.live="sheetFilterRows" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                    <option value="">Semua sheet</option>
                    @foreach ($this->rowSheetOptions() as $sheet)
                        <option value="{{ $sheet }}">{{ $sheet }}</option>
                    @endforeach
                </select>
                @if ($this->searchRows !== '' || $this->sheetFilterRows !== '')
                    <x-filament::button color="gray" size="sm" wire:click="resetFilters">
                        Reset filter
                    </x-filament::button>
                @endif
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    Menampilkan <strong>{{ count($this->filteredRows()) }}</strong> dari {{ count($this->rows) }} baris
                    <x-filament::loading-indicator
                        wire:loading
                        wire:target="searchRows,sheetFilterRows"
                        class="h-4 w-4 text-primary-500"
                        style="display:none"
                    />
                </span>
            </div>

            <div class="mt-2 overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/40">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide">Sheet</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide">#</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide">Perusahaan</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide">Nama</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide">No. Telepon (mentah)</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->filteredRows() as $index => $r)
                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                <td class="px-4 py-2 text-gray-500">{{ $r['sheet'] ?? '-' }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ $index + 1 }}</td>
                                <td class="px-4 py-2 font-medium">{{ $r['nama_perusahaan'] !== '' ? $r['nama_perusahaan'] : '-' }}</td>
                                <td class="px-4 py-2">{{ $r['nama'] !== '' ? $r['nama'] : '-' }}</td>
                                <td class="px-4 py-2"><span class="font-mono">{{ $r['no_telepon_mentah'] !== '' ? $r['no_telepon_mentah'] : '-' }}</span></td>
                                <td class="px-4 py-2 text-gray-500">{{ $r['catatan'] !== '' ? $r['catatan'] : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">Tidak ada baris yang cocok dengan filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @else
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <x-filament::section heading="Langkah 1 dari 2 — Pilih file Excel / CSV" :description="'Format: .xlsx, .xls, .ods, .csv, .tsv (maks 10 MB). Pratinjau sekaligus analisis dijalankan otomatis.'">
                    <div class="grid gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">File import</label>
                            <input
                                type="file"
                                wire:model="file"
                                accept=".xlsx,.xls,.ods,.csv,.tsv"
                                class="mt-2 block w-full rounded-lg border border-gray-300 bg-white text-sm text-gray-900 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-primary-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                            >
                            @error('file')
                                <p class="mt-1 text-xs text-danger-600">{{ $message }}</p>
                            @enderror
                            <p wire:loading wire:target="file" class="mt-1 text-xs text-primary-600">
                                Mengunggah file... mohon tunggu sebelum klik Pratinjau &amp; Analisis.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <x-filament::button wire:click="preview" wire:loading.attr="disabled" wire:target="preview,file">
                                <span wire:loading wire:target="file">Mengunggah file...</span>
                                <span wire:loading wire:target="preview">Memproses...</span>
                                <span wire:loading.remove wire:target="file,preview">Pratinjau & Analisis</span>
                            </x-filament::button>
                            <x-filament::button color="gray" wire:click="downloadTemplate">
                                Unduh template (CSV)
                            </x-filament::button>
                        </div>
                    </div>
                </x-filament::section>
            </div>

            <div>
                <x-filament::section heading="Format kolom yang dikenali">
                    <p class="text-sm">
                        Nama kolom dibebaskan & dikenali otomatis (Indonesia/Inggris,
                        mis. <code>No. HP</code> / <code>no_telepon</code> / <code>telp</code> /
                        <code>PIC / No. HP</code>). Header dicari otomatis, tidak harus
                        di baris pertama. Semua sheet dalam file Excel dibaca.
                        Kolom yang wajib ada:
                    </p>
                    <ul class="mt-2 list-inside list-disc space-y-1 text-sm">
                        <li><strong>nama_perusahaan</strong> (atau <em>perusahaan</em>)</li>
                        <li><strong>nama</strong> (atau <em>pic</em>)</li>
                        <li><strong>no_telepon</strong> (atau <em>hp</em> / <em>telp</em>)</li>
                    </ul>
                    <p class="mt-2 text-sm text-gray-500">
                        Opsional: <code>industri</code>, <code>jabatan</code>, <code>catatan</code>.
                    </p>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        Alur: <strong>Pratinjau &amp; Analisis</strong> = ekstraksi baris dari file
                        sekaligus pencocokan dengan data yang sudah ada (duplikat, perusahaan cocok,
                        nomor valid); lanjut ke langkah 2 untuk memeriksa lalu menyimpan.
                    </p>
                </x-filament::section>
            </div>
        </div>
    @endif
</x-filament-panels::page>