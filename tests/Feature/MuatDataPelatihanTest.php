<?php

namespace Tests\Feature;

use App\Models\KategoriKegiatan;
use App\Models\Kegiatan;
use App\Models\Kontak;
use App\Models\Perusahaan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MuatDataPelatihanTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        if (isset($this->tempDir) && is_dir($this->tempDir)) {
            foreach (glob($this->tempDir.'/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($this->tempDir);
        }

        parent::tearDown();
    }

    protected function makeDataDir(): string
    {
        $this->tempDir = sys_get_temp_dir().'/muat_pelatihan_'.uniqid();

        return $this->tempDir;
    }

    protected function makeXlsx(string $path, string $sheetTitle, array $rows): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetTitle);
        $sheet->fromArray($rows, null, 'A1');
        (new Xlsx($spreadsheet))->save($path);
    }

    #[Test]
    public function memuat_data_latih_dari_folder_dan_idempoten(): void
    {
        $dir = $this->makeDataDir();
        mkdir($dir);

        $this->makeXlsx($dir.'/rangga.xlsx', 'PIKAB 2026 - Sponsor Fix', [
            ['Target Peserta: Dokter Spesialis Anak', 'Tanggal: 24-26 April 2026', 'Venue: Holiday Inn Pasteur Bandung'],
            ['nama_perusahaan', 'nama', 'no_telepon'],
            ['PT Meprofarm', 'Budi', '0811-1465-133'],
            ['Sunthi Sepuri Nurdhin', 'Andi', '0812-8266-5004'],
        ]);

        $this->artisan('app:muat-data-pelatihan', ['--dir' => $dir])->assertSuccessful();

        // Kategori kanonik + kategori terdeteksi.
        $this->assertGreaterThanOrEqual(10, KategoriKegiatan::count());
        $this->assertDatabaseHas('kategori_kegiatans', ['nama_kategori' => 'Dokter Spesialis Anak']);

        // Kegiatan PIKAB lengkap (kategori, tanggal, venue) dari metadata sheet.
        $pikab = Kegiatan::where('nama_event', 'PIKAB 2026')->first();
        $this->assertNotNull($pikab);
        $this->assertSame('Dokter Spesialis Anak', $pikab->kategoriKegiatan->nama_kategori);
        $this->assertSame('2026-04-24', $pikab->tanggal_mulai?->format('Y-m-d'));
        $this->assertSame('Holiday Inn Pasteur Bandung', $pikab->venue);

        // Sinonim perusahaan menyatu ke nama kanonik.
        $this->assertDatabaseHas('perusahaans', ['nama_standar' => 'PT Meprofarm Pharmaceutical Industries']);
        $this->assertDatabaseHas('perusahaans', ['nama_standar' => 'Sunthi Sepuri']);

        $before = [
            'kontak' => Kontak::count(),
            'perusahaan' => Perusahaan::count(),
            'kegiatan' => Kegiatan::count(),
        ];

        $this->artisan('app:muat-data-pelatihan', ['--dir' => $dir])->assertSuccessful();

        $this->assertSame($before['kontak'], Kontak::count());
        $this->assertSame($before['perusahaan'], Perusahaan::count());
        $this->assertSame($before['kegiatan'], Kegiatan::count());
    }

    #[Test]
    public function menolak_folder_kosong_atau_tidak_ada(): void
    {
        $this->artisan('app:muat-data-pelatihan', ['--dir' => 'tidak-ada'])
            ->assertFailed()
            ->expectsOutputToContain('Folder data tidak ditemukan');

        $dir = $this->makeDataDir();
        mkdir($dir);

        $this->artisan('app:muat-data-pelatihan', ['--dir' => $dir])
            ->assertFailed()
            ->expectsOutputToContain('Tidak ada file .xlsx');
    }
}
