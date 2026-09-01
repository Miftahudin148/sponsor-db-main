<?php

namespace App\Filament\Pages;

use App\Models\Kontak;
use App\Services\KontakImportService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportKontaks extends Page
{
    protected string $view = 'filament.pages.import-kontaks';

    protected static ?string $slug = 'import-data';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static ?string $navigationLabel = 'Import Data';

    protected static ?string $title = 'Import Data Kontak & Perusahaan';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('import', Kontak::class) ?? false;
    }

    /**
     * Import diakses dari dalam fitur Kontak (tombol header daftar kontak),
     * jadi halaman ini tidak muncul sebagai item navigasi tersendiri.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static ?int $navigationSort = 2;

    public $file = null;

    /** @var array<int, array<string, mixed>>|null Baris mentah hasil ekstraksi (langkah pratinjau). */
    public ?array $rows = null;

    /** @var array<int, array<string, mixed>>|null Baris berklasifikasi (langkah analisis). */
    public ?array $previews = null;

    /** @var array<string, int> */
    public array $counts = [];

    public bool $saved = false;

    /** @var array{perusahaan_dibuat: int, kontak_dibuat: int, dilewati: int}|null */
    public ?array $saveResult = null;

    public string $searchRows = '';

    public string $sheetFilterRows = '';

    public string $searchPreviews = '';

    public string $sheetFilterPreviews = '';

    public string $statusFilterPreviews = '';

    public string $companyFilterPreviews = '';

    public ?int $editingIndex = null;

    public string $editNamaPerusahaan = '';

    public string $editIndustri = '';

    public string $editNama = '';

    public string $editNoTelepon = '';

    public string $editCatatan = '';

    public function startEdit(int $index): void
    {
        if ($this->previews === null || ! isset($this->previews[$index])) {
            return;
        }

        $row = $this->previews[$index];
        $this->editingIndex = $index;
        $this->editNamaPerusahaan = (string) ($row['nama_perusahaan'] ?? '');
        $this->editIndustri = (string) ($row['industri'] ?? '');
        $this->editNama = (string) ($row['nama'] ?? '');
        $this->editNoTelepon = (string) ($row['no_telepon_mentah'] ?? '');
        $this->editCatatan = (string) ($row['catatan'] ?? '');
    }

    public function cancelEdit(): void
    {
        $this->editingIndex = null;
    }

    public function saveEdit(): void
    {
        if ($this->rows === null || $this->editingIndex === null || ! isset($this->rows[$this->editingIndex])) {
            return;
        }

        $this->rows[$this->editingIndex] = array_merge($this->rows[$this->editingIndex], [
            'nama_perusahaan' => trim($this->editNamaPerusahaan),
            'industri' => trim($this->editIndustri),
            'nama' => trim($this->editNama),
            'no_telepon_mentah' => trim($this->editNoTelepon),
            'catatan' => trim($this->editCatatan),
        ]);

        $this->editingIndex = null;
        $this->reclassify();
    }

    public function deletePreview(int $index): void
    {
        if ($this->rows === null || ! isset($this->rows[$index])) {
            return;
        }

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);

        if ($this->editingIndex !== null) {
            if ($this->editingIndex === $index) {
                $this->editingIndex = null;
            } elseif ($this->editingIndex > $index) {
                $this->editingIndex--;
            }
        }

        $this->reclassify();
    }

    protected function reclassify(): void
    {
        $this->previews = app(KontakImportService::class)->classify($this->rows);
        $this->counts = KontakImportService::summaryCounts($this->previews);
    }

    public function resetFilters(): void
    {
        $this->searchRows = '';
        $this->sheetFilterRows = '';
        $this->searchPreviews = '';
        $this->sheetFilterPreviews = '';
        $this->statusFilterPreviews = '';
        $this->companyFilterPreviews = '';
    }

    public function preview(): void
    {
        $this->validate(
            ['file' => ['required', 'file', 'max:10240', 'mimes:xlsx,xls,csv,tsv,ods']],
            [
                'file.required' => 'Pilih file terlebih dahulu. Jika baru memilih file, tunggu unggahan selesai lalu klik Pratinjau lagi.',
                'file.file' => 'File yang dipilih tidak valid.',
                'file.max' => 'Ukuran file maksimal 10 MB.',
                'file.mimes' => 'Format file harus: .xlsx, .xls, .csv, .tsv, atau .ods.',
            ]
        );

        $service = app(KontakImportService::class);
        $path = $this->file->getRealPath();
        $extension = $this->file instanceof TemporaryUploadedFile
            ? strtolower(pathinfo($this->file->getFilename(), PATHINFO_EXTENSION))
            : (string) $this->file->getClientOriginalExtension();

        $rows = $service->extractRows((string) $path, $extension);

        if ($rows === []) {
            Notification::make()
                ->title('File tidak berisi data yang bisa diproses')
                ->body('Pastikan ada baris header lalu baris data di bawahnya. Contoh: nama_perusahaan, nama, no_telepon.')
                ->danger()
                ->send();

            return;
        }

        // Pratinjau sekaligus analisis: satu klik langsung menghasilkan
        // klasifikasi (baru/duplikat/junk) tanpa tombol "Analisis" terpisah.
        $this->rows = $rows;
        $this->reclassify();
        $this->saved = false;
        $this->saveResult = null;
        $this->resetFilters();
    }

    public function analyze(): void
    {
        if (blank($this->rows)) {
            Notification::make()
                ->title('Belum ada data untuk dianalisis')
                ->body('Pratinjau file terlebih dahulu.')
                ->warning()
                ->send();

            return;
        }

        $this->previews = app(KontakImportService::class)->classify($this->rows);
        $this->counts = KontakImportService::summaryCounts($this->previews);
        $this->saved = false;
        $this->saveResult = null;
        $this->resetFilters();
    }

    public function backToPreview(): void
    {
        $this->previews = null;
        $this->counts = [];
        $this->saved = false;
        $this->saveResult = null;
        $this->resetFilters();
    }

    public function saveImport(): void
    {
        if ($this->previews === null || $this->saved) {
            return;
        }

        $result = app(KontakImportService::class)->save((int) auth()->id(), $this->previews);

        $this->saveResult = $result;
        $this->saved = true;
        $this->file = null;

        Notification::make()
            ->title(sprintf('Import selesai: %d kontak baru', $result['kontak_dibuat']))
            ->body(sprintf(
                'Perusahaan baru: %d · dilewati (duplikat/lengkap): %d',
                $result['perusahaan_dibuat'],
                $result['dilewati']
            ))
            ->success()
            ->send();
    }

    public function resetImport(): void
    {
        $this->file = null;
        $this->rows = null;
        $this->previews = null;
        $this->counts = [];
        $this->saved = false;
        $this->saveResult = null;
        $this->resetFilters();
    }

    /** @return array<int, array<string, mixed>> */
    public function filteredRows(): array
    {
        $rows = $this->rows ?? [];

        if ($this->sheetFilterRows !== '') {
            $rows = array_values(array_filter(
                $rows,
                fn (array $r): bool => (string) ($r['sheet'] ?? '') === $this->sheetFilterRows
            ));
        }

        if (trim($this->searchRows) !== '') {
            $q = mb_strtolower(trim($this->searchRows));
            $rows = array_values(array_filter(
                $rows,
                fn (array $r): bool => str_contains(mb_strtolower($this->rowSearchText($r)), $q)
            ));
        }

        return $rows;
    }

    protected function rowSearchText(array $r): string
    {
        return implode(' ', [
            (string) ($r['sheet'] ?? ''),
            (string) ($r['nama_perusahaan'] ?? ''),
            (string) ($r['nama'] ?? ''),
            (string) ($r['no_telepon_mentah'] ?? ''),
            (string) ($r['catatan'] ?? ''),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function filteredPreviews(): array
    {
        $previews = $this->previews ?? [];

        if ($this->sheetFilterPreviews !== '') {
            $previews = array_filter(
                $previews,
                fn (array $p): bool => (string) ($p['sheet'] ?? '') === $this->sheetFilterPreviews
            );
        }

        if ($this->statusFilterPreviews !== '') {
            $previews = array_filter(
                $previews,
                fn (array $p): bool => ($p['status_kontak'] ?? '') === $this->statusFilterPreviews
            );
        }

        if ($this->companyFilterPreviews !== '') {
            $previews = array_filter(
                $previews,
                fn (array $p): bool => ($p['perusahaan_status'] ?? '') === $this->companyFilterPreviews
            );
        }

        if (trim($this->searchPreviews) !== '') {
            $q = mb_strtolower(trim($this->searchPreviews));
            $previews = array_filter(
                $previews,
                fn (array $p): bool => str_contains(mb_strtolower($this->previewSearchText($p)), $q)
            );
        }

        return $previews;
    }

    protected function previewSearchText(array $p): string
    {
        return implode(' ', [
            (string) ($p['sheet'] ?? ''),
            (string) ($p['nama_perusahaan'] ?? ''),
            (string) ($p['perusahaan_nama_resmi'] ?? ''),
            (string) ($p['nama'] ?? ''),
            (string) ($p['no_telepon_mentah'] ?? ''),
            (string) ($p['no_telepon'] ?? ''),
            (string) ($p['catatan'] ?? ''),
            (string) ($p['alasan'] ?? ''),
        ]);
    }

    /** @return array<int, string> */
    public function rowSheetOptions(): array
    {
        return $this->distinctColumn($this->rows, 'sheet');
    }

    /** @return array<int, string> */
    public function previewSheetOptions(): array
    {
        return $this->distinctColumn($this->previews, 'sheet');
    }

    /** @return array<int, string> */
    protected function distinctColumn(?array $rows, string $key): array
    {
        if ($rows === null) {
            return [];
        }

        $values = [];
        foreach ($rows as $row) {
            $v = trim((string) ($row[$key] ?? ''));
            if ($v !== '') {
                $values[$v] = true;
            }
        }

        return array_keys($values);
    }

    public function kontakStatusLabel(string $status, string $perusahaanStatus): string
    {
        return match ($status) {
            'dibuat' => $perusahaanStatus === 'cocok' ? 'Disimpan (perusahaan lama)' : 'Disimpan (baru)',
            'duplikat_telepon' => 'Duplikat nomor',
            'duplikat_nama' => 'Duplikat nama',
            'duplikat_batch' => 'Duplikat dlm file',
            'data_tidak_lengkap' => 'Dilewati',
            default => $status,
        };
    }

    public function statusFilterOptions(): array
    {
        return [
            'dibuat' => 'Disimpan',
            'duplikat_telepon' => 'Duplikat nomor',
            'duplikat_nama' => 'Duplikat nama',
            'duplikat_batch' => 'Duplikat dlm file',
            'data_tidak_lengkap' => 'Dilewati (tidak lengkap)',
        ];
    }

    public function downloadTemplate()
    {
        $csv = "nama_perusahaan,industri,nama,no_telepon,catatan\n"
            ."PT Alfa Medika,Farmasi,Budi Santoso,0811-1465-133,Catatan opsional\n"
            ."CV Sinar Sehat,Distributor Alkes,Siti Aminah,6281291018454,-\n";

        return response()->streamDownload(
            static fn () => print ($csv),
            'template-import-kontak-csv.csv',
            ['Content-Type' => 'text/csv']
        );
    }
}
