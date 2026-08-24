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

/**
 * Fitur warna master data (kategori & kegiatan): kolom warna, pewarnaan
 * badge pada daftar kontak (kegiatan mewarisi warna kategori bila kosong),
 * dan sortir menurut warna.
 */
class WarnaKegiatanKategoriTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function warna_tersimpan_pada_kategori_dan_kegiatan(): void
    {
        $kategori = KategoriKegiatan::factory()->create(['nama_kategori' => 'Dokter Paru', 'warna' => '#0ea5e9']);
        $kegiatan = Kegiatan::factory()->for($kategori, 'kategoriKegiatan')->create([
            'nama_event' => 'PIR PDPI Jateng',
            'warna' => '#ff0000',
        ]);

        $this->assertSame('#0ea5e9', $kategori->fresh()->warna);
        $this->assertSame('#ff0000', $kegiatan->fresh()->warna);
    }

    #[Test]
    public function badge_kontak_memakai_warna_dan_warisi_warna_kategori(): void
    {
        $user = User::factory()->admin()->create();

        $kategori = KategoriKegiatan::factory()->create(['nama_kategori' => 'Dokter Paru', 'warna' => '#0ea5e9']);
        $kegiatanBerwarna = Kegiatan::factory()->for($kategori, 'kategoriKegiatan')->create([
            'nama_event' => 'Event Sendiri',
            'warna' => '#ff0000',
        ]);
        $kegiatanWarisan = Kegiatan::factory()->for($kategori, 'kategoriKegiatan')->create([
            'nama_event' => 'Event Warisan',
            'warna' => null,
        ]);

        Kontak::factory()->create(['kegiatan_id' => $kegiatanBerwarna->id, 'kategori_kegiatan_id' => $kategori->id]);
        Kontak::factory()->create(['kegiatan_id' => $kegiatanWarisan->id, 'kategori_kegiatan_id' => $kategori->id]);

        $html = (string) $this->actingAs($user)->get('/admin/kontaks')->getContent();

        // Badge kegiatan memakai warna sendiri dan warna warisan kategori.
        $this->assertStringContainsString('ff0000', $html);
        $this->assertStringContainsString('0ea5e9', $html);
    }

    #[Test]
    public function sortir_kolom_kegiatan_mengurutkan_menurut_warna(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $kategori = KategoriKegiatan::factory()->create(['nama_kategori' => 'Multi', 'warna' => '#64748b']);
        $biru = Kegiatan::factory()->for($kategori, 'kategoriKegiatan')->create(['nama_event' => 'Event Biru', 'warna' => null]); // warisan #64748b
        $hijau = Kegiatan::factory()->for($kategori, 'kategoriKegiatan')->create(['nama_event' => 'Event Hijau', 'warna' => '#10b981']);
        $merah = Kegiatan::factory()->for($kategori, 'kategoriKegiatan')->create(['nama_event' => 'Event Merah', 'warna' => '#ff0000']);

        // Sengaja berlawanan dengan urutan warna agar sort benar-benar teruji.
        Kontak::factory()->create(['nama' => 'Ani', 'kegiatan_id' => $merah->id, 'kategori_kegiatan_id' => $kategori->id]);
        Kontak::factory()->create(['nama' => 'Budi', 'kegiatan_id' => $hijau->id, 'kategori_kegiatan_id' => $kategori->id]);
        Kontak::factory()->create(['nama' => 'Cici', 'kegiatan_id' => $biru->id, 'kategori_kegiatan_id' => $kategori->id]);

        $komponen = Livewire::test(ListKontaks::class)->assertOk();
        $komponen->instance()->tableSort = 'kegiatan.nama_event:asc';

        $urutan = $komponen->instance()
            ->getFilteredSortedTableQuery()
            ->pluck('kontaks.nama')
            ->all();

        // Urutan naik mengikuti nilai hex secara leksikografis;
        // warna kegiatan kosong mewarisi warna kategori (#64748b).
        $this->assertSame(['Budi', 'Cici', 'Ani'], $urutan);
    }

    #[Test]
    public function baris_tabel_berwarna_menurut_kegiatan_dan_kategori(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $kategoriBiru = KategoriKegiatan::factory()->create(['nama_kategori' => 'Dokter Paru', 'warna' => '#0ea5e9']);
        $kegiatanMerah = Kegiatan::factory()->for($kategoriBiru, 'kategoriKegiatan')->create([
            'nama_event' => 'Event Merah',
            'warna' => '#ff0000',
        ]);

        // Kontak berkegiatan memakai warna kegiatan; kontak tanpa kegiatan
        // memakai warna kategorinya.
        Kontak::factory()->create(['nama' => 'Ani', 'kegiatan_id' => $kegiatanMerah->id, 'kategori_kegiatan_id' => $kategoriBiru->id]);
        Kontak::factory()->create(['nama' => 'Budi', 'kategori_kegiatan_id' => $kategoriBiru->id]);

        $html = (string) $this->get('/admin/kontaks')->getContent();

        $this->assertStringContainsString('baris-warna-ff0000', $html);
        $this->assertStringContainsString('baris-warna-0ea5e9', $html);
        // Aturan <style> tint baris ter-render di header tabel.
        $this->assertStringContainsString('#ff00001f', $html);
    }

    #[Test]
    public function baris_anomali_nomor_telepon_tetap_prioritas_danger(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $kategori = KategoriKegiatan::factory()->create(['warna' => '#10b981']);
        $nomorSama = '081234567890';

        Kontak::factory()->create(['perusahaan_id' => Perusahaan::factory(), 'nama' => 'Ani', 'no_telepon' => $nomorSama, 'kategori_kegiatan_id' => $kategori->id]);
        Kontak::factory()->create(['perusahaan_id' => Perusahaan::factory(), 'nama' => 'Budi', 'no_telepon' => $nomorSama, 'kategori_kegiatan_id' => $kategori->id]);

        $html = (string) $this->get('/admin/kontaks')->getContent();

        // Ambil segmen baris yang mengandung nama PIC kedua.
        $segmenBaris = collect(explode('<tr', $html))
            ->first(fn (string $potongan): bool => str_contains($potongan, 'Budi'));

        $this->assertNotNull($segmenBaris);
        $this->assertStringContainsString('bg-danger-500/10', '<tr'.$segmenBaris);
        $this->assertStringNotContainsString('baris-warna-', '<tr'.$segmenBaris);
    }

    #[Test]
    public function sortir_kolom_kategori_mengurutkan_menurut_warna(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $merah = KategoriKegiatan::factory()->create(['nama_kategori' => 'Kejiwaan', 'warna' => '#ef4444']);
        $hijau = KategoriKegiatan::factory()->create(['nama_kategori' => 'Gizi Klinik', 'warna' => '#10b981']);

        Kontak::factory()->create(['nama' => 'Ani', 'kategori_kegiatan_id' => $merah->id]);
        Kontak::factory()->create(['nama' => 'Budi', 'kategori_kegiatan_id' => $hijau->id]);

        $komponen = Livewire::test(ListKontaks::class)->assertOk();
        $komponen->instance()->tableSort = 'kategoriKegiatan.nama_kategori:desc';

        $urutan = $komponen->instance()
            ->getFilteredSortedTableQuery()
            ->pluck('kontaks.nama')
            ->all();

        // Desc: warna tertinggi (#ef4444) lebih dulu.
        $this->assertSame(['Ani', 'Budi'], $urutan);
    }
}
