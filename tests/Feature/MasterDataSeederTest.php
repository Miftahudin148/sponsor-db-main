<?php

namespace Tests\Feature;

use App\Models\KategoriKegiatan;
use App\Models\Kegiatan;
use App\Support\KlasifikasiTabel;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MasterDataSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function seeder_mengisi_semua_kategori_dan_kegiatan_kanonik(): void
    {
        $this->seed(MasterDataSeeder::class);

        $this->assertSame(count(KlasifikasiTabel::KATEGORI), KategoriKegiatan::count());
        $this->assertSame(count(KlasifikasiTabel::KEGIATAN), Kegiatan::count());

        $pikab = Kegiatan::where('nama_event', 'PIKAB 2026')->first();
        $this->assertNotNull($pikab);
        $this->assertSame('Dokter Spesialis Anak', $pikab->kategoriKegiatan->nama_kategori);
        $this->assertSame('2026-04-24', $pikab->tanggal_mulai?->format('Y-m-d'));
        $this->assertSame('Holiday Inn Pasteur Bandung', $pikab->venue);

        $pdp = Kegiatan::where('nama_event', 'PIR PDPI JATENG 2025')->first();
        $this->assertNotNull($pdp);
        $this->assertSame('Dokter Spesialis Paru (Pulmonologi)', $pdp->kategoriKegiatan->nama_kategori);
    }

    #[Test]
    public function seeder_idempoten(): void
    {
        $this->seed(MasterDataSeeder::class);
        $this->seed(MasterDataSeeder::class);

        $this->assertSame(count(KlasifikasiTabel::KATEGORI), KategoriKegiatan::count());
        $this->assertSame(count(KlasifikasiTabel::KEGIATAN), Kegiatan::count());
    }
}
