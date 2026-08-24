<?php

namespace Database\Seeders;

use App\Models\KategoriKegiatan;
use App\Models\Kegiatan;
use App\Support\KlasifikasiTabel;
use Illuminate\Database\Seeder;

/**
 * Taksonomi master (kategori & kegiatan/event) yang diturunkan dari
 * data latih sponsor. Data riil perusahaan + kontak diisi lewat
 * `php artisan app:muat-data-pelatihan`.
 */
class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $kategoriCache = [];

        foreach (KlasifikasiTabel::KATEGORI as $nama) {
            $kategoriCache[$nama] = KategoriKegiatan::firstOrCreate(['nama_kategori' => $nama]);
        }

        foreach (KlasifikasiTabel::KEGIATAN as $namaEvent => $meta) {
            $kategoriKey = $meta['key'] ?? KlasifikasiTabel::KATEGORI_FALLBACK;
            $namaKategori = KlasifikasiTabel::kategoriNama($kategoriKey);

            if (! isset($kategoriCache[$namaKategori])) {
                $kategoriCache[$namaKategori] = KategoriKegiatan::firstOrCreate(['nama_kategori' => $namaKategori]);
            }

            Kegiatan::updateOrCreate(
                ['nama_event' => $namaEvent],
                [
                    'kategori_kegiatan_id' => $kategoriCache[$namaKategori]->id,
                    'tanggal_mulai' => $meta['mulai'] ?? null,
                    'tanggal_selesai' => $meta['selesai'] ?? null,
                    'venue' => $meta['venue'] ?? null,
                    'catatan' => 'Diturunkan dari data latih sponsor (MasterDataSeeder).',
                ]
            );
        }
    }
}
