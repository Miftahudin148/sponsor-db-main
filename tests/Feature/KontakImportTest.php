<?php

namespace Tests\Feature;

use App\Filament\Pages\ImportKontaks;
use App\Models\Kontak;
use App\Models\Perusahaan;
use App\Models\User;
use App\Services\KontakImportService;
use App\Support\Hooks\SanitizeUtf8State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleComponents\ComponentContext;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KontakImportTest extends TestCase
{
    use RefreshDatabase;

    protected function csvContent(array $rows): string
    {
        $lines = [['nama_perusahaan', 'industri', 'nama', 'no_telepon', 'jabatan', 'catatan']];
        $lines = array_merge($lines, $rows);

        return implode("\n", array_map(fn (array $row): string => implode(',', $row), $lines));
    }

    protected function makeCsv(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'kontak_import').'.csv';
        file_put_contents($path, $this->csvContent($rows));

        return $path;
    }

    protected function makeXlsx(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'kontak_import').'.xlsx';
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $data = [['nama_perusahaan', 'industri', 'nama', 'no_telepon', 'jabatan', 'catatan']];
        $data = array_merge($data, $rows);
        $sheet->fromArray($data, null, 'A1');
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    #[Test]
    public function normalisasi_nomor_dilakukan_sebelum_pencocokan_duplikat(): void
    {
        $alfa = Perusahaan::factory()->create(['nama_standar' => 'PT Alfa Medika']);
        Kontak::factory()->create(['perusahaan_id' => $alfa->id, 'no_telepon' => '628111465133']);

        $rows = (new KontakImportService)->extractRows(
            $this->makeCsv([['pt alfa medika', 'Farmasi', 'Budi', '0811-1465-133', 'PIC', '']])
        );
        $previews = (new KontakImportService)->classify($rows);

        $p = $previews[0];
        $this->assertSame('cocok', $p['perusahaan_status']);
        $this->assertSame('duplikat_telepon', $p['status_kontak']);
        $this->assertSame('628111465133', $p['no_telepon']);
    }

    #[Test]
    public function perusahaan_baru_dan_kontak_baru_berlabel_dibuat(): void
    {
        $rows = (new KontakImportService)->extractRows(
            $this->makeCsv([['PT Beta Farmasi', 'Farmasi', 'Andi', '81291018454', 'Dirut', 'catatan']])
        );
        $previews = (new KontakImportService)->classify($rows);

        $p = $previews[0];
        $this->assertSame('baru', $p['perusahaan_status']);
        $this->assertSame('dibuat', $p['status_kontak']);
        $this->assertSame('6281291018454', $p['no_telepon']);
        $this->assertTrue($p['no_telepon_valid']);
    }

    #[Test]
    public function duplikat_dalam_file_yang_sama_dilewati(): void
    {
        $rows = (new KontakImportService)->extractRows(
            $this->makeCsv([
                ['CV Sinar Sehat', 'Alkes', 'Siti', '6281291018454', 'Sales', ''],
                ['CV Sinar Sehat', 'Alkes', 'Siti', '6281291018454', 'Sales', ''],
            ])
        );
        $previews = (new KontakImportService)->classify($rows);

        $this->assertSame('dibuat', $previews[0]['status_kontak']);
        $this->assertSame('duplikat_batch', $previews[1]['status_kontak']);
    }

    #[Test]
    public function nomor_asing_tidak_valid_tetap_ditandai_bukan_diblokir(): void
    {
        $rows = (new KontakImportService)->extractRows(
            $this->makeCsv([['PT Global', 'Impor', 'John', '44123456789', 'GM', '']])
        );
        $previews = (new KontakImportService)->classify($rows);

        $this->assertSame('dibuat', $previews[0]['status_kontak']);
        $this->assertFalse($previews[0]['no_telepon_valid']);
        $this->assertSame('44123456789', $previews[0]['no_telepon']);
    }

    #[Test]
    public function extract_rows_dukung_file_xlsx(): void
    {
        $rows = (new KontakImportService)->extractRows(
            $this->makeXlsx([['PT Gamma', 'Diagnostik', 'Rudi', '628211122211', 'PIC', '']])
        );

        $this->assertSame('PT Gamma', $rows[0]['nama_perusahaan']);
        $this->assertSame('Rudi', $rows[0]['nama']);
        $this->assertSame('628211122211', $rows[0]['no_telepon_mentah']);
    }

    #[Test]
    public function save_import_menghasilkan_tanpa_duplikat_baru_end_to_end(): void
    {
        $admin = User::factory()->admin()->create();
        $alfa = Perusahaan::factory()->create(['nama_standar' => 'PT Alfa Medika']);
        Kontak::factory()->create(['perusahaan_id' => $alfa->id, 'no_telepon' => '628111465133']);

        $service = new KontakImportService;
        $path = $this->makeCsv([
            // duplikat nomor dgn Alfa yang sudah ada -> dilewati
            ['PT Alfa Medika', 'Farmasi', 'Budi', '0811-1465-133', 'PIC', ''],
            // perusahaan sama, nomor baru -> kontak baru
            ['pt alfa medika', 'Farmasi', 'Rina', '6281291018454', 'Sales', ''],
            // perusahaan & kontak baru
            ['PT Beta Farmasi', 'Farmasi', 'Andi', '81291018454', 'Dirut', 'cat'],
            // baris tak lengkap -> dilewati
            ['', 'Farmasi', 'TanpaPerusahaan', '081234567890', '', ''],
        ]);

        $ex = $service->extractRows($path);
        $previews = $service->classify($ex);

        $this->assertSame('duplikat_telepon', $previews[0]['status_kontak']);
        $this->assertSame('dibuat', $previews[1]['status_kontak']);
        $this->assertSame('dibuat', $previews[2]['status_kontak']);
        $this->assertSame('data_tidak_lengkap', $previews[3]['status_kontak']);

        $result = $service->save((int) $admin->id, $previews);

        $this->assertSame(1, $result['perusahaan_dibuat']);
        $this->assertSame(2, $result['kontak_dibuat']);
        $this->assertSame(2, $result['dilewati']);

        $this->assertDatabaseCount('perusahaans', 2);
        $this->assertDatabaseCount('kontaks', 3);

        $this->assertDatabaseHas('kontaks', [
            'perusahaan_id' => $alfa->id,
            'nama' => 'Rina',
            'no_telepon' => '6281291018454',
            'status_format_valid' => true,
            'status_verifikasi' => 'perlu_dicek',
        ]);
        $this->assertDatabaseHas('kontaks', [
            'nama' => 'Andi',
            'no_telepon' => '6281291018454',
            'updated_by' => $admin->id,
        ]);
        $this->assertDatabaseMissing('kontaks', ['nama' => 'Budi']);
        $this->assertDatabaseMissing('kontaks', ['nama' => 'TanpaPerusahaan']);
    }

    #[Test]
    public function page_import_preview_lalu_simpan(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $uploaded = UploadedFile::fake()->createWithContent('data.csv', $this->csvContent([
            ['PT Beta Farmasi', 'Farmasi', 'Andi', '81291018454', 'Dirut', ''],
        ]));

        Livewire::test(ImportKontaks::class)
            ->upload('file', [$uploaded])
            ->call('preview')
            ->assertNotSet('rows', null)
            ->assertNotSet('previews', null)
            ->assertSet('counts.baru', 1)
            ->call('saveImport')
            ->assertSet('saved', true);

        $this->assertDatabaseCount('perusahaans', 1);
        $this->assertDatabaseCount('kontaks', 1);
        $this->assertDatabaseHas('kontaks', [
            'nama' => 'Andi',
            'no_telepon' => '6281291018454',
            'updated_by' => $admin->id,
        ]);
    }

    #[Test]
    public function dua_orang_dengan_dua_nomor_dipecah_menjadi_dua_baris(): void
    {
        $rows = (new KontakImportService)->extractRows(
            $this->makeCsv([['PT. Herca Cipta Dermal Perdana', '', 'Pak Carman / Leni', '62 812-8276-303 / +62 819-1111-1095', '', '']])
        );

        $this->assertCount(2, $rows);
        $this->assertSame('Pak Carman', $rows[0]['nama']);
        $this->assertSame('62 812-8276-303', $rows[0]['no_telepon_mentah']);
        $this->assertSame('Leni', $rows[1]['nama']);
        $this->assertSame('+62 819-1111-1095', $rows[1]['no_telepon_mentah']);
        $this->assertSame('', $rows[0]['catatan']);
    }

    #[Test]
    public function dua_orang_satu_nomor_digabung_menjadi_satu_baris(): void
    {
        $rows = (new KontakImportService)->extractRows(
            $this->makeCsv([['PT. Meprofarm', '', 'Mira/Nelly', '62812-2388-839', '', '']])
        );

        $this->assertCount(1, $rows);
        $this->assertSame('Mira / Nelly', $rows[0]['nama']);
        $this->assertSame('62812-2388-839', $rows[0]['no_telepon_mentah']);
    }

    #[Test]
    public function dua_orang_tanpa_nomor_tetap_dipecah_dua_baris(): void
    {
        $rows = (new KontakImportService)->extractRows(
            $this->makeCsv([['PT. Meprofarm', '', 'Mira/Nelly', '', '', '']])
        );

        $this->assertCount(2, $rows);
        $this->assertSame('Mira', $rows[0]['nama']);
        $this->assertSame('Nelly', $rows[1]['nama']);
    }

    #[Test]
    public function tiga_orang_satu_nomor_digabung_menjadi_satu_baris(): void
    {
        $rows = (new KontakImportService)->extractRows(
            $this->makeCsv([['PT. X', '', 'Budi/Cici/Dodi', '0811-1465-133', '', '']])
        );

        $this->assertCount(1, $rows);
        $this->assertSame('Budi / Cici / Dodi', $rows[0]['nama']);
        $this->assertSame('0811-1465-133', $rows[0]['no_telepon_mentah']);
    }

    #[Test]
    public function nomor_ekstra_tanpa_orang_menjadi_baris_pendamping_bukan_catatan(): void
    {
        $rows = (new KontakImportService)->extractRows(
            $this->makeCsv([['PT. Sunthi Sepuri', '', 'Nurdhin', '62 812-8266-5004 / +62 812-9537-5810', '', 'Catatan asli']])
        );

        $this->assertCount(2, $rows);
        $this->assertSame('Nurdhin', $rows[0]['nama']);
        $this->assertSame('62 812-8266-5004', $rows[0]['no_telepon_mentah']);
        $this->assertSame('Catatan asli', $rows[0]['catatan']);
        $this->assertSame('', $rows[1]['nama']);
        $this->assertSame('+62 812-9537-5810', $rows[1]['no_telepon_mentah']);

        $previews = (new KontakImportService)->classify($rows);
        $this->assertSame('dibuat', $previews[0]['status_kontak']);
        // Baris pendamping: tanpa nama PIC tapi punya perusahaan + nomor
        // -> valid sejak aturan "PIC opsional".
        $this->assertSame('dibuat', $previews[1]['status_kontak']);
    }

    #[Test]
    public function pic_kosong_tapi_perusahaan_dan_nomor_ada_dianggap_valid(): void
    {
        $rows = (new KontakImportService)->extractRows(
            $this->makeCsv([['PT Alfa Medika', 'Farmasi', '', '0811-1465-133', '', '']])
        );

        $previews = (new KontakImportService)->classify($rows);

        $this->assertSame('dibuat', $previews[0]['status_kontak']);
        $this->assertSame('', $previews[0]['nama']);
        $this->assertSame('', $previews[0]['alasan']);
    }

    #[Test]
    public function perusahaan_ada_tapi_nomor_kosong_dianggap_tidak_lengkap(): void
    {
        $rows = (new KontakImportService)->extractRows(
            $this->makeCsv([['PT Alfa Medika', 'Farmasi', 'Budi', '', '', '']])
        );

        $previews = (new KontakImportService)->classify($rows);

        $this->assertSame('data_tidak_lengkap', $previews[0]['status_kontak']);
        $this->assertSame('Nomor telepon kosong (tidak lengkap)', $previews[0]['alasan']);
    }

    #[Test]
    public function perusahaan_dan_nomor_kosong_dianggap_tidak_lengkap(): void
    {
        $rows = (new KontakImportService)->extractRows(
            $this->makeCsv([['', 'Farmasi', 'Budi', '-', '', '']])
        );

        $previews = (new KontakImportService)->classify($rows);

        $this->assertSame('data_tidak_lengkap', $previews[0]['status_kontak']);
        $this->assertSame('Baris tanpa nama perusahaan dan nomor telepon', $previews[0]['alasan']);
    }

    #[Test]
    public function nomor_ada_tapi_perusahaan_kosong_tetap_tidak_lengkap(): void
    {
        $rows = (new KontakImportService)->extractRows(
            $this->makeCsv([['', 'Farmasi', 'TanpaPerusahaan', '081234567890', '', '']])
        );

        $previews = (new KontakImportService)->classify($rows);

        $this->assertSame('data_tidak_lengkap', $previews[0]['status_kontak']);
        $this->assertSame('Baris tanpa nama perusahaan', $previews[0]['alasan']);
    }

    #[Test]
    public function dua_baris_tanpa_pic_di_perusahaan_sama_tidak_tertangkap_duplikat_nama(): void
    {
        $rows = (new KontakImportService)->extractRows(
            $this->makeCsv([
                ['PT Alfa Medika', 'Farmasi', '', '0811-1465-133', '', ''],
                ['PT Alfa Medika', 'Farmasi', '', '0812-9101-8454', '', ''],
            ])
        );

        $previews = (new KontakImportService)->classify($rows);

        $this->assertSame('dibuat', $previews[0]['status_kontak']);
        $this->assertSame('dibuat', $previews[1]['status_kontak']);
    }

    #[Test]
    public function pencarian_dan_filter_status_tersedia_pada_pratinjau_dan_analisis(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $uploaded = UploadedFile::fake()->createWithContent('data.csv', $this->csvContent([
            ['PT Alfa Medika', 'Farmasi', 'Budi', '0811-1465-133', 'PIC', ''],
            ['PT Beta Farmasi', 'Farmasi', 'Andi', '0812-9101-8454', 'Dirut', ''],
            ['PT Gamma Sehat', 'Distributor', 'Cici', '0813-2222-3333', 'Sales', ''],
        ]));

        $component = Livewire::test(ImportKontaks::class);
        $component->upload('file', [$uploaded]);
        $component->call('preview');

        $component->set('searchRows', 'alfa');
        $this->assertCount(1, $component->instance()->filteredRows());

        $component->set('searchRows', '0812-9101');
        $this->assertCount(1, $component->instance()->filteredRows());

        $component->set('searchRows', '');
        $this->assertCount(3, $component->instance()->filteredRows());

        $component->call('analyze');

        $component->set('statusFilterPreviews', 'dibuat');
        $this->assertCount(3, $component->instance()->filteredPreviews());

        $component->set('companyFilterPreviews', 'cocok');
        $this->assertCount(0, $component->instance()->filteredPreviews());

        $component->set('companyFilterPreviews', '');
        $component->set('statusFilterPreviews', 'duplikat_telepon');
        $this->assertCount(0, $component->instance()->filteredPreviews());

        $component->set('statusFilterPreviews', '');
        $component->set('searchPreviews', 'gamma');
        $this->assertCount(1, $component->instance()->filteredPreviews());

        $component->call('resetFilters');
        $this->assertSame('', $component->instance()->searchPreviews);
        $this->assertCount(3, $component->instance()->filteredPreviews());
    }

    #[Test]
    public function hapus_baris_hasil_analisis_mengurangi_kandidat_yang_disimpan(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $uploaded = UploadedFile::fake()->createWithContent('data.csv', $this->csvContent([
            ['PT Alfa Medika', 'Farmasi', 'Budi', '0811-1465-133', 'PIC', ''],
            ['PT Beta Farmasi', 'Farmasi', 'Andi', '0812-9101-8454', 'Dirut', ''],
            ['PT Gamma Sehat', 'Distributor', 'Cici', '0813-2222-3333', 'Sales', ''],
        ]));

        $component = Livewire::test(ImportKontaks::class);
        $component->upload('file', [$uploaded]);
        $component->call('preview');
        $component->call('analyze');
        $this->assertCount(3, $component->instance()->previews);

        $component->call('deletePreview', 1);
        $this->assertCount(2, $component->instance()->previews);
        $this->assertCount(2, $component->instance()->filteredPreviews());
        $this->assertSame(2, $component->instance()->counts['baru']);
        $this->assertSame('PT Alfa Medika', $component->instance()->previews[0]['nama_perusahaan']);
        $this->assertSame('PT Gamma Sehat', $component->instance()->previews[1]['nama_perusahaan']);

        $component->call('saveImport');
        $this->assertDatabaseCount('perusahaans', 2);
        $this->assertDatabaseCount('kontaks', 2);
    }

    #[Test]
    public function hapus_baris_duplikat_batch_membuat_baris_serupa_menjadi_dibuat(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $uploaded = UploadedFile::fake()->createWithContent('data.csv', $this->csvContent([
            ['PT Alfa Medika', 'Farmasi', 'Budi', '0811-1465-133', 'PIC', ''],
            ['PT Alfa Medika', 'Farmasi', 'Cici', '0811-1465-133', 'PIC', ''],
        ]));

        $component = Livewire::test(ImportKontaks::class);
        $component->upload('file', [$uploaded]);
        $component->call('preview');
        $component->call('analyze');
        $this->assertSame('dibuat', $component->instance()->previews[0]['status_kontak']);
        $this->assertSame('duplikat_batch', $component->instance()->previews[1]['status_kontak']);

        $component->call('deletePreview', 1);

        $this->assertCount(1, $component->instance()->previews);
        $this->assertSame('dibuat', $component->instance()->previews[0]['status_kontak']);
    }

    #[Test]
    public function edit_baris_analisis_mengubah_data_sebelum_disimpan(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $uploaded = UploadedFile::fake()->createWithContent('data.csv', $this->csvContent([
            ['PT Alfa Medika', 'Farmasi', 'Budi', '0811-1465-133', 'PIC', 'Opsional'],
        ]));

        $component = Livewire::test(ImportKontaks::class);
        $component->upload('file', [$uploaded]);
        $component->call('preview');
        $component->call('analyze');

        $component->call('startEdit', 0);
        $this->assertSame('Budi', $component->instance()->editNama);
        $this->assertSame('0811-1465-133', $component->instance()->editNoTelepon);

        $component->set('editNama', 'Andi');
        $component->set('editNoTelepon', '0812-9101-8454');
        $component->set('editCatatan', 'Edit manual');
        $component->call('saveEdit');

        $this->assertNull($component->instance()->editingIndex);
        $this->assertSame('Andi', $component->instance()->previews[0]['nama']);
        $this->assertSame('6281291018454', $component->instance()->previews[0]['no_telepon']);

        $component->call('saveImport');
        $this->assertDatabaseHas('kontaks', ['nama' => 'Andi', 'no_telepon' => '6281291018454']);
    }

    #[Test]
    public function edit_nomor_membebaskan_baris_dari_status_duplikat(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $alfa = Perusahaan::factory()->create(['nama_standar' => 'PT Alfa Medika']);
        Kontak::factory()->create(['perusahaan_id' => $alfa->id, 'no_telepon' => '628111465133']);

        $uploaded = UploadedFile::fake()->createWithContent('data.csv', $this->csvContent([
            ['pt alfa medika', 'Farmasi', 'Budi', '0811-1465-133', 'PIC', ''],
        ]));

        $component = Livewire::test(ImportKontaks::class);
        $component->upload('file', [$uploaded]);
        $component->call('preview');
        $component->call('analyze');
        $this->assertSame('duplikat_telepon', $component->instance()->previews[0]['status_kontak']);

        $component->call('startEdit', 0);
        $component->set('editNoTelepon', '0812-9101-8454');
        $component->call('saveEdit');

        $this->assertSame('dibuat', $component->instance()->previews[0]['status_kontak']);
        $this->assertSame('6281291018454', $component->instance()->previews[0]['no_telepon']);
    }

    #[Test]
    public function nama_duplikat_di_perusahaan_sama_deteteksi_walau_nomor_berbeda(): void
    {
        $rows = (new KontakImportService)->classify(
            (new KontakImportService)->extractRows($this->makeCsv([
                ['PT. Meprofarm', 'Farmasi', 'Mira', '62812-2388-839', '', ''],
                ['PT. Meprofarm', 'Farmasi', 'Mira', '62813-2003-2632', '', ''],
            ]))
        );

        $this->assertSame('dibuat', $rows[0]['status_kontak']);
        $this->assertSame('duplikat_batch', $rows[1]['status_kontak']);
        $this->assertStringContainsString('Mira', $rows[1]['alasan']);
    }

    #[Test]
    public function nama_sudah_ada_di_database_diteteksi_walau_nomor_baru(): void
    {
        $alfa = Perusahaan::factory()->create(['nama_standar' => 'PT Alfa Medika']);
        Kontak::factory()->create([
            'perusahaan_id' => $alfa->id,
            'no_telepon' => '628111465133',
            'nama' => 'Budi',
        ]);

        $rows = (new KontakImportService)->classify(
            (new KontakImportService)->extractRows($this->makeCsv([
                ['pt alfa medika', 'Farmasi', 'Budi', '0812-9101-8454', 'PIC', ''],
            ]))
        );

        $this->assertSame('duplikat_nama', $rows[0]['status_kontak']);
    }

    #[Test]
    public function duplikat_nomor_tetap_lebin_diprioritaskan_daripada_duplikat_nama(): void
    {
        $alfa = Perusahaan::factory()->create(['nama_standar' => 'PT Alfa Medika']);
        Kontak::factory()->create([
            'perusahaan_id' => $alfa->id,
            'no_telepon' => '628111465133',
            'nama' => 'Budi',
        ]);

        $rows = (new KontakImportService)->classify(
            (new KontakImportService)->extractRows($this->makeCsv([
                ['pt alfa medika', 'Farmasi', 'Budi', '0811-1465-133', 'PIC', ''],
            ]))
        );

        $this->assertSame('duplikat_telepon', $rows[0]['status_kontak']);
        $this->assertStringContainsString('628111465133', $rows[0]['alasan']);
    }

    #[Test]
    public function pratinjau_tanpa_file_memberi_pesan_jelas_bukan_kunci_terjemahan(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $component = Livewire::test(ImportKontaks::class);
        $component->call('preview');

        if (method_exists($component, 'errors')) {
            $message = implode(' ', (array) $component->errors()->get('file'));
        } else {
            $message = '';
        }

        $this->assertStringContainsString('Pilih file terlebih dahulu', $message);
    }

    #[Test]
    public function ekstraksi_mengatasi_file_berencoding_tidak_valid(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'kontak_bad_enc').'.csv';
        // Byte ANSI/Windows-1252 (0xE9 = é) = bukan UTF-8 valid.
        file_put_contents($path, "nama_perusahaan,nama,no_telepon\nPT. Bien\xE9,Doctors,0811-1465-133\n");

        $rows = (new KontakImportService)->extractRows($path);
        $this->assertCount(1, $rows);
        $this->assertTrue(mb_check_encoding($rows[0]['nama_perusahaan'], 'UTF-8'));
        $this->assertSame('PT. Biené', $rows[0]['nama_perusahaan']);
        $this->assertSame('0811-1465-133', $rows[0]['no_telepon_mentah']);

        // Klasifikasi + serialisasi JSON (persis langkah Livewire) tidak boleh error.
        $previews = (new KontakImportService)->classify($rows);
        $json = json_encode($previews, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('Biené', $json);
    }

    #[Test]
    public function livewire_tidak_meletup_dengan_state_utf8_tak_valid(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $component = new ImportKontaks;
        $component->previews = [
            [
                'sheet' => 'Daftar Kontak',
                'baris' => 2,
                'nama_perusahaan' => "PT. Bien\xE9",
                'perusahaan_status' => 'cocok',
                'nama' => 'Doctors',
                'no_telepon' => '0811-1465-133',
                'no_telepon_valid' => true,
                'status_kontak' => 'baru',
                'alasan' => '',
            ],
        ];
        $component->searchRows = "Jos\xE9";

        // (a) Hook langsung: byte korup pada properti publik disanitasi.
        $hook = new SanitizeUtf8State;
        $hook->setComponent($component);
        $hook->dehydrate();

        $this->assertTrue(mb_check_encoding($component->previews[0]['nama_perusahaan'], 'UTF-8'));
        $this->assertTrue(mb_check_encoding($component->searchRows, 'UTF-8'));

        // (b) Integrasi: lewat pipeline Livewire penuh, tidak crash saat dehydrate.
        Livewire::test(ImportKontaks::class)
            ->set('searchRows', "Jos\xE9")
            ->call('$refresh')
            ->assertSet('searchRows', 'José');
    }

    #[Test]
    public function nomor_telpon_dengan_karakter_rtl_tidak_mengkorup_utf8(): void
    {
        // Sel asli: U+202A (LTR-embedding) + "+62 " + NBSP + U+2011 (hyphen NB) + U+202C.
        $phone = "\u{202A}+62 \u{00A0}852\u{2011}3233\u{2011}9561\u{202C}";

        $rows = (new KontakImportService)->extractRows(
            $this->makeCsv([['PT IndAAC', 'Farmasi', 'PIC', $phone, 'Sales', '']])
        );

        $this->assertNotEmpty($rows);
        $this->assertTrue(mb_check_encoding($rows[0]['no_telepon_mentah'], 'UTF-8'));

        // Pipeline penuh (classify + JSON) tidak boleh meledak (byte-wise trim
        // lama memotong e2/80 -> byte 0xAA tak valid).
        $json = json_encode(
            (new KontakImportService)->classify($rows),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
        );
        $this->assertStringContainsString('3233', $json);
    }

    #[Test]
    public function tombol_pratinjau_dan_analisis_hanya_memiliki_satu_loading_indicator(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        // Step 2 (Pratinjau) memuat tombol "Analisis"; Filament otomatis
        // menambahkan loading indicator sendiri. Tidak boleh dobel.
        $html = Livewire::test(ImportKontaks::class)
            ->set('rows', [[
                'sheet' => 'S1',
                'nama_perusahaan' => 'PT Alfa',
                'nama' => 'Budi',
                'no_telepon_mentah' => '0811',
                'catatan' => '',
            ]])
            ->html();

        $this->assertSame(1, $this->countIndicatorsInButton($html, 'analyze'));

        // Step 1 (upload) memuat tombol "Pratinjau file".
        $html1 = Livewire::test(ImportKontaks::class)->html();
        $this->assertSame(1, $this->countIndicatorsInButton($html1, 'preview'));
    }

    protected function countIndicatorsInButton(string $html, string $wireClick): int
    {
        $pos = strpos($html, 'wire:click="'.$wireClick.'"');
        if ($pos === false) {
            return -1;
        }

        $start = strrpos(substr($html, 0, $pos), '<button');
        $segment = substr($html, $start, 1500);

        return substr_count($segment, 'fi-loading-indicator');
    }

    #[Test]
    public function hook_menyanyitasi_properti_dan_effects_sewaktu_dehydrate(): void
    {
        $component = new ImportKontaks;
        $component->previews = [
            [
                'sheet' => 'Daftar Kontak',
                'baris' => 2,
                'nama_perusahaan' => "PT. Bien\xE9",
                'perusahaan_status' => 'cocok',
                'nama' => 'Doctors',
                'no_telepon_mentah' => '0811-1465-133',
                'no_telepon' => '628111465133',
                'no_telepon_valid' => true,
                'status_kontak' => 'baru',
                'alasan' => '',
            ],
        ];

        $context = new ComponentContext($component);
        $context->effects['html'] = "<table><td>PT. Bien\xE9</td></table>";

        $hook = new SanitizeUtf8State;
        $hook->setComponent($component);
        $hook->dehydrate($context);

        $this->assertTrue(mb_check_encoding($component->previews[0]['nama_perusahaan'], 'UTF-8'));
        $this->assertTrue(mb_check_encoding($context->effects['html'], 'UTF-8'));

        json_encode([$component->previews, $context->effects], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $this->assertTrue(true);
    }
}
