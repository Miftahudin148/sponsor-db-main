<?php

namespace Tests\Feature;

use App\Filament\Resources\Kontaks\Pages\ListKontaks;
use App\Models\Kontak;
use App\Models\Perusahaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KontakMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_normalisasi_nomor_command_mengonversi_format_lama(): void
    {
        $perusahaan = Perusahaan::factory()->create();

        $legacy = [
            '0811-1465-133' => ['628111465133', true],
            '+62 811 1465 133' => ['628111465133', true],
            '81291018454' => ['6281291018454', true],
            '620811222333' => ['62811222333', true],
            '44123456789' => ['44123456789', false],
            '' => [null, false],
        ];

        foreach ($legacy as $nomor => [$_, $_]) {
            DB::table('kontaks')->insert([
                'perusahaan_id' => $perusahaan->id,
                'nama' => 'Kontak '.substr((string) $nomor, -3),
                'no_telepon' => $nomor,
                'status_format_valid' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->artisan('kontak:normalisasi-nomor')->assertSuccessful();

        foreach (['628111465133', '6281291018454', '62811222333'] as $harusAda) {
            $this->assertDatabaseHas('kontaks', ['no_telepon' => $harusAda, 'status_format_valid' => true]);
        }
        $this->assertDatabaseHas('kontaks', ['no_telepon' => '44123456789', 'status_format_valid' => false]);
        $this->assertDatabaseHas('kontaks', ['no_telepon' => null, 'status_format_valid' => false]);
    }

    #[Test]
    public function deteksi_nomor_sama_untuk_perusahaan_berbeda(): void
    {
        $perusahaanA = Perusahaan::factory()->create(['nama_standar' => 'PT Alfa']);
        $perusahaanB = Perusahaan::factory()->create(['nama_standar' => 'PT Bravo']);

        $kontakA = Kontak::factory()->create(['perusahaan_id' => $perusahaanA->id, 'no_telepon' => '628111465133']);
        $kontakB = Kontak::factory()->create(['perusahaan_id' => $perusahaanB->id, 'no_telepon' => '628111465133']);
        $kontakUnik = Kontak::factory()->create(['perusahaan_id' => $perusahaanA->id, 'no_telepon' => '628211122211']);

        $this->assertSame(['PT Bravo'], $kontakA->perusahaanLainDenganNomorSama());
        $this->assertSame(['PT Alfa'], $kontakB->perusahaanLainDenganNomorSama());
        $this->assertSame([], $kontakUnik->perusahaanLainDenganNomorSama());
    }

    #[Test]
    public function kolom_sortir_kontak_tersedia(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Kontak::factory()->count(2)->create();

        Livewire::test(ListKontaks::class)
            ->assertTableColumnExists('No')
            ->assertTableColumnExists('perusahaan.nama_standar')
            ->assertTableColumnExists('nama')
            ->assertTableColumnExists('no_telepon')
            ->assertTableColumnExists('kegiatan.nama_event')
            ->assertTableColumnExists('kategoriKegiatan.nama_kategori');
    }

    #[Test]
    public function kolom_flag_nomor_terpadu_ada_di_list_admin_dan_tersembunyi(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        Kontak::factory()->count(2)->create();

        Livewire::test(ListKontaks::class)
            ->assertTableColumnExists('nomor_dipakai_perusahaan_lain');

        $karyawan = User::factory()->karyawan()->create();
        $this->actingAs($karyawan);
        Livewire::test(ListKontaks::class)->assertSuccessful();
    }
}
