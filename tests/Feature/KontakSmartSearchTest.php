<?php

namespace Tests\Feature;

use App\Filament\Resources\Kontaks\Pages\ListKontaks;
use App\Models\KategoriKegiatan;
use App\Models\Kegiatan;
use App\Models\Kontak;
use App\Models\Perusahaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KontakSmartSearchTest extends TestCase
{
    use RefreshDatabase;

    private Perusahaan $perusahaanA;

    private Perusahaan $perusahaanB;

    private Kontak $kontakA;

    private Kontak $kontakB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->perusahaanA = Perusahaan::factory()->create(['nama_standar' => 'PT Alfa Medika']);
        $this->perusahaanB = Perusahaan::factory()->create(['nama_standar' => 'CV Sinar Sehat']);

        $kategoriAnak = KategoriKegiatan::factory()->create(['nama_kategori' => 'Spesialis Anak']);
        $kategoriUmum = KategoriKegiatan::factory()->create(['nama_kategori' => 'Dokter Umum']);
        $pikab = Kegiatan::factory()->create(['nama_event' => 'PIKAB 2026', 'kategori_kegiatan_id' => $kategoriAnak->id]);
        $konas = Kegiatan::factory()->create(['nama_event' => 'KONAS 2026', 'kategori_kegiatan_id' => $kategoriUmum->id]);

        $this->kontakA = Kontak::factory()->create([
            'perusahaan_id' => $this->perusahaanA->id,
            'nama' => 'Budi Santoso',
            'no_telepon' => '628111465133',
            'kegiatan_id' => $pikab->id,
            'kategori_kegiatan_id' => $kategoriAnak->id,
        ]);

        $this->kontakB = Kontak::factory()->create([
            'perusahaan_id' => $this->perusahaanB->id,
            'nama' => 'Siti Aminah',
            'no_telepon' => '6281291018454',
            'kegiatan_id' => $konas->id,
            'kategori_kegiatan_id' => $kategoriUmum->id,
        ]);

        $this->actingAs(User::factory()->admin()->create());
    }

    #[Test]
    public function cari_berdasarkan_nama_pic(): void
    {
        Livewire::test(ListKontaks::class)
            ->filterTable('cari', ['q' => 'Budi'])
            ->assertCanSeeTableRecords([$this->kontakA])
            ->assertCanNotSeeTableRecords([$this->kontakB]);
    }

    #[Test]
    public function cari_berdasarkan_nama_perusahaan(): void
    {
        Livewire::test(ListKontaks::class)
            ->filterTable('cari', ['q' => 'Alfa Medika'])
            ->assertCanSeeTableRecords([$this->kontakA])
            ->assertCanNotSeeTableRecords([$this->kontakB]);
    }

    #[Test]
    public function cari_berdasarkan_potongan_nomor_telepon(): void
    {
        Livewire::test(ListKontaks::class)
            ->filterTable('cari', ['q' => '0811'])
            ->assertCanSeeTableRecords([$this->kontakA])
            ->assertCanNotSeeTableRecords([$this->kontakB]);
    }

    #[Test]
    public function cari_berdasarkan_nama_kegiatan(): void
    {
        Livewire::test(ListKontaks::class)
            ->filterTable('cari', ['q' => 'PIKAB'])
            ->assertCanSeeTableRecords([$this->kontakA])
            ->assertCanNotSeeTableRecords([$this->kontakB]);
    }

    #[Test]
    public function cari_berdasarkan_nama_kategori(): void
    {
        Livewire::test(ListKontaks::class)
            ->filterTable('cari', ['q' => 'Spesialis Anak'])
            ->assertCanSeeTableRecords([$this->kontakA])
            ->assertCanNotSeeTableRecords([$this->kontakB]);
    }

    #[Test]
    public function cari_multi_kata_harus_cocok_semua_tokens(): void
    {
        Livewire::test(ListKontaks::class)
            ->filterTable('cari', ['q' => 'Medika 0811'])
            ->assertCanSeeTableRecords([$this->kontakA])
            ->assertCanNotSeeTableRecords([$this->kontakB]);
    }

    #[Test]
    public function kolom_klasifikasi_tampil_saat_search_aktif(): void
    {
        Livewire::test(ListKontaks::class)
            ->assertDontSee('Cocok pada')
            ->filterTable('cari', ['q' => 'Budi 0811'])
            ->assertSee('Cocok pada')
            ->assertSee('PIC + No. Telepon')
            ->filterTable('cari', ['q' => ''])
            ->assertDontSee('Cocok pada');
    }

    #[Test]
    public function cari_yang_tidak_ada_mengembalikan_kosong(): void
    {
        Livewire::test(ListKontaks::class)
            ->filterTable('cari', ['q' => 'XYZ Tidak Ada'])
            ->assertCanNotSeeTableRecords([$this->kontakA, $this->kontakB]);
    }
}
