<?php

namespace Tests\Feature;

use App\Filament\Resources\Kontaks\Pages\ListKontaks;
use App\Filament\Resources\Kontaks\Tables\KontaksTable;
use App\Filament\Widgets\KontakPipeline;
use App\Models\Kontak;
use App\Models\Perusahaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class KontakEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ekspor_csv_mendownload_semua_kontak(): void
    {
        $user = User::factory()->admin()->create();
        $perusahaan = Perusahaan::factory()->create(['nama_standar' => 'PT Alfa Medika']);
        Kontak::factory()->create([
            'perusahaan_id' => $perusahaan->id,
            'nama' => 'Budi Santoso',
            'no_telepon' => '08111465133',
            'status_verifikasi' => 'terverifikasi',
        ]);

        $response = $this->actingAs($user)
            ->get(route('kontaks.export'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Budi Santoso', $csv);
        $this->assertStringContainsString('PT Alfa Medika', $csv);
        $this->assertStringContainsString('628111465133', $csv);
        $this->assertStringContainsString('terverifikasi', $csv);
    }

    #[Test]
    public function ekspor_csv_menghormati_filter_pencarian_dan_status(): void
    {
        $user = User::factory()->admin()->create();

        $perusahaanA = Perusahaan::factory()->create(['nama_standar' => 'PT Alfa Medika']);
        $perusahaanB = Perusahaan::factory()->create(['nama_standar' => 'CV Sinar Sehat']);

        Kontak::factory()->create([
            'perusahaan_id' => $perusahaanA->id,
            'nama' => 'Budi Santoso',
            'no_telepon' => '08111465133',
            'status_verifikasi' => 'terverifikasi',
        ]);
        Kontak::factory()->create([
            'perusahaan_id' => $perusahaanB->id,
            'nama' => 'Siti Aminah',
            'no_telepon' => '08120918231',
            'status_verifikasi' => 'perlu_dicek',
        ]);

        $csv = $this->actingAs($user)
            ->get(route('kontaks.export', ['status' => 'perlu_dicek', 'q' => 'Aminah']))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Siti Aminah', $csv);
        $this->assertStringNotContainsString('Budi Santoso', $csv);
    }

    #[Test]
    public function ekspor_csv_membutuhkan_login(): void
    {
        $this->get(route('kontaks.export'))->assertRedirect();
    }

    #[Test]
    public function list_kontak_menyediakan_aksi_baris_whatsapp_view_dan_ekspor(): void
    {
        $user = User::factory()->admin()->create();
        $perusahaan = Perusahaan::factory()->create();
        $kontak = Kontak::factory()->create([
            'perusahaan_id' => $perusahaan->id,
            'no_telepon' => '08111465133',
        ]);

        $this->actingAs($user);
        Livewire::test(ListKontaks::class)
            ->assertOk()
            ->assertTableColumnExists('nama')
            ->assertTableActionExists('whatsapp')
            ->assertTableActionExists('view')
            ->assertTableActionExists('edit')
            ->assertTableActionExists('export')
            ->assertSee('Ekspor CSV')
            ->assertSee((string) $kontak->id)
            ->assertSee('wa.me/628111465133');
    }

    #[Test]
    public function daftar_kontak_menampilkan_ringkasan_di_atas_mengikuti_filter(): void
    {
        $user = User::factory()->admin()->create();

        $perusahaan = Perusahaan::factory()->create();
        Kontak::factory()->create([
            'perusahaan_id' => $perusahaan->id,
            'no_telepon' => '08111465133',
            'status_format_valid' => true,
            'status_verifikasi' => 'terverifikasi',
        ]);
        Kontak::factory()->create([
            'perusahaan_id' => $perusahaan->id,
            'no_telepon' => '08120918231',
            'status_format_valid' => true,
            'status_verifikasi' => 'terverifikasi',
        ]);
        Kontak::factory()->create([
            'perusahaan_id' => $perusahaan->id,
            'no_telepon' => '08137788990',
            'status_format_valid' => true,
            'status_verifikasi' => 'perlu_dicek',
        ]);
        Kontak::factory()->create([
            'perusahaan_id' => $perusahaan->id,
            'no_telepon' => 'bukan-nomor',
            'status_format_valid' => false,
            'status_verifikasi' => 'tidak_aktif',
        ]);

        $this->actingAs($user);
        $component = Livewire::test(ListKontaks::class)->assertOk();

        $cards = collect(KontaksTable::summaryCards($component->instance()))->keyBy('key');
        $this->assertSame(4, $cards['total']['count']);
        $this->assertSame(3, $cards['valid']['count']);
        $this->assertSame(2, $cards['terverifikasi']['count']);
        $this->assertSame(1, $cards['perlu_dicek']['count']);
        $this->assertSame(1, $cards['tidak_aktif']['count']);

        $component->assertSee('Total kontak')->assertSee('Nomor valid');
    }

    #[Test]
    public function ringkasan_mengikuti_filter_status_yang_aktif(): void
    {
        $user = User::factory()->admin()->create();

        $perusahaan = Perusahaan::factory()->create();
        Kontak::factory()->create([
            'perusahaan_id' => $perusahaan->id,
            'status_verifikasi' => 'terverifikasi',
        ]);
        Kontak::factory()->create([
            'perusahaan_id' => $perusahaan->id,
            'status_verifikasi' => 'perlu_dicek',
        ]);

        $this->actingAs($user);
        $component = Livewire::test(ListKontaks::class)
            ->filterTable('status_verifikasi', 'perlu_dicek');

        $cards = collect(KontaksTable::summaryCards($component->instance()))->keyBy('key');
        $this->assertSame(1, $cards['total']['count']);
        $this->assertSame(1, $cards['perlu_dicek']['count']);
    }

    #[Test]
    public function semua_kolom_dapat_dipilih_lewat_pengelola_kolom(): void
    {
        $user = User::factory()->admin()->create();
        $perusahaan = Perusahaan::factory()->create();
        Kontak::factory()->create(['perusahaan_id' => $perusahaan->id]);

        $this->actingAs($user);

        $component = Livewire::test(ListKontaks::class)->assertOk();
        $table = $component->instance()->getTable();

        foreach ([
            'No',
            'perusahaan.nama_standar',
            'nama',
            'no_telepon',
            'kegiatan.nama_event',
            'kategoriKegiatan.nama_kategori',
            'status_format_valid',
            'status_verifikasi',
            'updatedBy.name',
        ] as $columnName) {
            $this->assertTrue(
                $table->getColumn($columnName)->isToggleable(),
                "Kolom [$columnName] seharusnya dapat dipilih di pengelola kolom."
            );
        }
    }

    #[Test]
    public function baris_duplikat_disorot_hanya_untuk_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $perusahaanA = Perusahaan::factory()->create(['nama_standar' => 'PT Alfa Medika']);
        $perusahaanB = Perusahaan::factory()->create(['nama_standar' => 'CV Sinar Sehat']);

        $kontakA = Kontak::factory()->create([
            'perusahaan_id' => $perusahaanA->id,
            'no_telepon' => '08111465133',
        ]);
        Kontak::factory()->create([
            'perusahaan_id' => $perusahaanB->id,
            'no_telepon' => '08111465133',
        ]);

        $this->assertSame(
            ['CV Sinar Sehat'],
            $kontakA->perusahaanLainDenganNomorSama()
        );

        $this->actingAs($admin);
        Livewire::test(ListKontaks::class)
            ->assertOk()
            ->assertSee('bg-danger-500/10');

        Livewire::actingAs(User::factory()->karyawan()->create())
            ->test(ListKontaks::class)
            ->assertOk()
            ->assertDontSee('bg-danger-500/10');
    }

    #[Test]
    public function list_kontak_kosong_menampilkan_cta_import(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user);
        Livewire::test(ListKontaks::class)
            ->assertOk()
            ->assertSee('Belum ada kontak')
            ->assertSee('Import Data');
    }

    #[Test]
    public function widget_pipeline_menghitung_jumlah_dan_persentase_per_status(): void
    {
        Kontak::factory()->create(['status_verifikasi' => 'terverifikasi']);
        Kontak::factory()->count(2)->create(['status_verifikasi' => 'perlu_dicek']);
        Kontak::factory()->count(3)->create(['status_verifikasi' => 'tidak_aktif']);

        $widget = new KontakPipeline();
        $getViewData = new ReflectionMethod($widget, 'getViewData');
        $data = $getViewData->invoke($widget);

        $this->assertSame(6, $data['total']);

        $byKey = collect($data['rows'])->keyBy('key');
        $this->assertSame(1, $byKey['terverifikasi']['count']);
        $this->assertSame(2, $byKey['perlu_dicek']['count']);
        $this->assertSame(3, $byKey['tidak_aktif']['count']);
        $this->assertSame(50, $byKey['tidak_aktif']['percent']);

        Livewire::test(KontakPipeline::class)->assertOk();
    }
}
