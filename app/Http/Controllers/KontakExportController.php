<?php

namespace App\Http\Controllers;

use App\Models\Kontak;
use App\Support\KontakSmartSearch;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KontakExportController extends Controller
{
    public function __invoke(Request $request, KontakSmartSearch $smartSearch): StreamedResponse
    {
        if (! $request->user()?->can('viewAny', Kontak::class)) {
            abort(403);
        }

        $query = Kontak::query()
            ->with(['perusahaan', 'kegiatan', 'kategoriKegiatan'])
            ->orderBy('nama');

        $q = trim((string) $request->query('q'));
        if ($q !== '') {
            $smartSearch->applyTo($query, $q);
        }

        if (filled($status = $request->query('status'))) {
            $query->where('status_verifikasi', $status);
        }

        if (filled($kegiatanId = $request->query('kegiatan_id'))) {
            $query->where('kegiatan_id', $kegiatanId);
        }

        if (filled($kategoriId = $request->query('kategori_kegiatan_id'))) {
            $query->where('kategori_kegiatan_id', $kategoriId);
        }

        $filename = 'kontak-'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Nama PIC',
                'Nama Perusahaan',
                'Industri',
                'No. Telepon',
                'Kegiatan',
                'Tahun',
                'Kategori',
                'Status Verifikasi',
            ]);

            $query->chunk(500, function ($kontaks) use ($handle): void {
                foreach ($kontaks as $kontak) {
                    fputcsv($handle, [
                        $kontak->nama,
                        $kontak->perusahaan?->nama_standar,
                        $kontak->perusahaan?->industri,
                        $kontak->no_telepon,
                        $kontak->kegiatan?->nama_event,
                        $kontak->kegiatan?->tanggal_mulai?->format('Y'),
                        $kontak->kategoriKegiatan?->nama_kategori,
                        $kontak->status_verifikasi,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
