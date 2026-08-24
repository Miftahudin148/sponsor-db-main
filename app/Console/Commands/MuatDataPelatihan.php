<?php

namespace App\Console\Commands;

use App\Models\KategoriKegiatan;
use App\Models\Kegiatan;
use App\Models\Kontak;
use App\Models\Perusahaan;
use App\Services\KontakImportService;
use App\Support\KlasifikasiTabel;
use App\Support\PhoneNormalizer;
use Illuminate\Console\Command;

class MuatDataPelatihan extends Command
{
    private const DEFAULT_DIR = '../data training';

    protected $signature = 'app:muat-data-pelatihan {--dir= : folder berisi file .xlsx data latih (default: data training)}';

    protected $description = 'Muati data latih sponsor (kategori, kegiatan, perusahaan, kontak) dari folder "data training"';

    public function handle(): int
    {
        $dir = $this->option('dir') ?: self::DEFAULT_DIR;

        if (! is_dir($dir)) {
            $this->error("Folder data tidak ditemukan: {$dir}");

            return self::FAILURE;
        }

        $files = glob(rtrim($dir, '/\\').'/*.xlsx') ?: [];

        if ($files === []) {
            $this->error('Tidak ada file .xlsx di folder data training.');

            return self::FAILURE;
        }

        // Kategori kanonik dipastikan ada lebih dulu.
        $kategoriCache = [];
        foreach (array_unique(array_values(KlasifikasiTabel::KATEGORI)) as $nama) {
            $kategoriCache[$nama] = KategoriKegiatan::firstOrCreate(['nama_kategori' => $nama]);
        }

        $this->info('Memuat data latih sponsor...');

        $totalPerusahaan = 0;
        $totalKontak = 0;
        $totalDilewati = 0;

        foreach ($files as $file) {
            $rows = app(KontakImportService::class)->extractRows($file);

            $result = $this->muatFile($file, $rows, $kategoriCache);

            $this->line(sprintf(
                '  %s: %d baris -> perusahaan %d · kontak %d · dilewati %d',
                basename($file),
                count($rows),
                $result['perusahaan'],
                $result['kontak'],
                $result['dilewati']
            ));

            $totalPerusahaan += $result['perusahaan'];
            $totalKontak += $result['kontak'];
            $totalDilewati += $result['dilewati'];
        }

        $this->newLine();
        $this->info(sprintf(
            'Selesai. Perusahaan baru: %d · Kontak baru: %d · Dilewati (junk/duplikat): %d',
            $totalPerusahaan,
            $totalKontak,
            $totalDilewati
        ));

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, KategoriKegiatan>  $kategoriCache
     * @return array{perusahaan: int, kontak: int, dilewati: int}
     */
    protected function muatFile(string $file, array $rows, array $kategoriCache): array
    {
        $perusahaanDibuat = 0;
        $kontakDibuat = 0;
        $dilewati = 0;

        $kegiatanCache = [];

        foreach ($rows as $row) {
            $canonical = KlasifikasiTabel::perusahaanKanonik($row['nama_perusahaan'] ?? '');
            $nama = trim((string) ($row['nama'] ?? ''));
            $rawPhone = trim((string) ($row['no_telepon_mentah'] ?? ''));
            $namaEvent = KlasifikasiTabel::eventKanonik($row['nama_event'] ?? null);
            $namaKategori = trim((string) ($row['nama_kategori'] ?? ''));

            if ($nama === '' && $rawPhone === '') {
                $dilewati++;

                continue;
            }

            if ($canonical === '' || KlasifikasiTabel::isJunk($canonical) || KlasifikasiTabel::isJunk((string) $row['nama_perusahaan'])) {
                $dilewati++;

                continue;
            }

            // --- Perusahaan (nama kanonik) ---
            $perusahaan = Perusahaan::firstOrCreate(
                ['nama_standar' => $canonical],
                ['industri' => $row['industri'] ?: null, 'catatan' => null]
            );

            if ($perusahaan->wasRecentlyCreated) {
                $perusahaanDibuat++;
            }

            // --- Kegiatan + kategori ---
            $kegiatanId = null;
            $kategoriId = null;

            if (filled($namaEvent)) {
                if (! isset($kegiatanCache[$namaEvent])) {
                    $kategoriNama = $namaKategori;
                    $kategoriByEvent = $this->kategoriDariKegiatan($namaEvent);

                    if ($kategoriNama === '' && $kategoriByEvent !== null) {
                        $kategoriNama = $kategoriByEvent;
                    }

                    $kategoriId = $kategoriNama !== ''
                        ? $kategoriCache[$kategoriNama]->id ?? (KategoriKegiatan::firstOrCreate(['nama_kategori' => $kategoriNama]))->id
                        : null;

                    $kegiatan = Kegiatan::firstOrCreate(['nama_event' => $namaEvent]);

                    if ($kategoriId !== null) {
                        $kegiatan->kategori_kegiatan_id = $kategoriId;
                    }

                    if (filled($row['venue'] ?? null)) {
                        $kegiatan->venue = (string) $row['venue'];
                    }

                    if (filled($row['tanggal_mulai'] ?? null)) {
                        $kegiatan->tanggal_mulai = (string) $row['tanggal_mulai'];
                    }

                    $kegiatan->save();
                    $kegiatanCache[$namaEvent] = $kegiatan;
                }

                $kegiatan = $kegiatanCache[$namaEvent];
                $kegiatanId = $kegiatan->id;
                $kategoriId = $kegiatan->kategori_kegiatan_id;
            }

            // --- Kontak (dedupe per perusahaan+event+nama+nomor) ---
            $phone = PhoneNormalizer::normalize($rawPhone);

            $existing = Kontak::query()
                ->where('perusahaan_id', $perusahaan->id)
                ->where('kegiatan_id', $kegiatanId)
                ->where('nama', $nama)
                ->where('no_telepon', $phone !== '' ? $phone : null)
                ->exists();

            if ($existing) {
                $dilewati++;

                continue;
            }

            Kontak::create([
                'perusahaan_id' => $perusahaan->id,
                'kegiatan_id' => $kegiatanId,
                'kategori_kegiatan_id' => $kategoriId,
                'nama' => $nama,
                'no_telepon' => $phone !== '' ? $phone : $rawPhone,
                'status_format_valid' => PhoneNormalizer::isValid($phone),
                'status_verifikasi' => 'perlu_dicek',
            ]);
            $kontakDibuat++;
        }

        return [
            'perusahaan' => $perusahaanDibuat,
            'kontak' => $kontakDibuat,
            'dilewati' => $dilewati,
        ];
    }

    protected function kategoriDariKegiatan(string $namaEvent): ?string
    {
        foreach (KlasifikasiTabel::KEGIATAN as $nama => $meta) {
            if ($nama === $namaEvent) {
                return KlasifikasiTabel::kategoriNama($meta['key'] ?? KlasifikasiTabel::KATEGORI_FALLBACK);
            }
        }

        return null;
    }
}
