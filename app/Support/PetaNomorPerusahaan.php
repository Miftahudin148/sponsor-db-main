<?php

namespace App\Support;

use App\Models\Kontak;
use App\Models\Perusahaan;

/**
 * Peta nomor telepon yang dipakai lebih dari satu perusahaan.
 *
 * Dipakai tabel kontak untuk mengganti pemanggilan per-baris
 * `Kontak::perusahaanLainDenganNomorSama()` (N+1) dengan SATU kali
 * komputasi per render, lalu tiap baris cukup membaca dari peta.
 */
class PetaNomorPerusahaan
{
    /**
     * @return array<string, array<int, string>> nomor => [id_perusahaan => nama_standar]
     */
    public function ambil(): array
    {
        $nomorDuplikat = Kontak::query()
            ->select('no_telepon')
            ->whereNotNull('no_telepon')
            ->where('no_telepon', '!=', '')
            ->groupBy('no_telepon')
            ->havingRaw('count(distinct perusahaan_id) > 1')
            ->pluck('no_telepon');

        if ($nomorDuplikat->isEmpty()) {
            return [];
        }

        $baris = Perusahaan::query()
            ->join('kontaks', 'kontaks.perusahaan_id', '=', 'perusahaans.id')
            ->whereIn('kontaks.no_telepon', $nomorDuplikat->all())
            ->orderBy('perusahaans.nama_standar')
            ->get(['kontaks.no_telepon as telepon', 'perusahaans.id as id_perusahaan', 'perusahaans.nama_standar']);

        /** @var array<string, array<int, string>> $peta */
        $peta = [];

        foreach ($baris as $data) {
            $peta[$data->telepon][$data->id_perusahaan] ??= $data->nama_standar;
        }

        ksort($peta);

        return $peta;
    }

    /**
     * Nama perusahaan LAIN yang memakai nomor yang sama dengan rekaman.
     *
     * @param  array<string, array<int, string>>  $peta  hasil {@see ambil()}
     * @return array<int, string>
     */
    public static function untukKontak(Kontak $record, array $peta): array
    {
        $telepon = $record->no_telepon;

        if ($telepon === null || $telepon === '') {
            return [];
        }

        $semua = $peta[$telepon] ?? [];
        unset($semua[$record->perusahaan_id]);

        return array_values($semua);
    }
}
