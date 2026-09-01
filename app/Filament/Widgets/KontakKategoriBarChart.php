<?php

namespace App\Filament\Widgets;

use App\Models\KategoriKegiatan;
use App\Support\KlasifikasiTabel;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class KontakKategoriBarChart extends ChartWidget
{
    protected ?string $heading = 'Distribusi Event per Kategori';

    protected ?string $description = 'Jumlah event pada setiap kategori';

    protected ?string $maxHeight = '340px';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -98;

    protected static bool $isLazy = false;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        // Cache 60 detik untuk 50 user baca bersamaan
        return Cache::remember('icm:chart_kategori', 60, function (): array {
            // Eager-free agregat: hitung event per kategori via LEFT JOIN agar kategori tanpa event tetap muncul
            $rows = KategoriKegiatan::query()
                ->leftJoin('kegiatans', 'kegiatans.kategori_kegiatan_id', '=', 'kategori_kegiatans.id')
                ->select('kategori_kegiatans.id', 'kategori_kegiatans.nama_kategori', DB::raw('count(kegiatans.id) as event_count'))
                ->groupBy('kategori_kegiatans.id', 'kategori_kegiatans.nama_kategori')
                ->orderByDesc('event_count')
                ->orderBy('kategori_kegiatans.nama_kategori')
                ->get();

            // Fallback bila belum ada kategori master
            if ($rows->isEmpty()) {
                return [
                    'labels' => [],
                    'datasets' => [['label' => 'Jumlah Event', 'data' => []]],
                ];
            }

            $labels = $rows->pluck('nama_kategori')->all();
            $data = $rows->pluck('event_count')->map(fn ($v) => (int) $v)->all();

            // Warna bervariatif — pakai palet kanonik KlasifikasiTabel agar konsisten dengan badge kategori
            $colors = [];
            $borders = [];
            foreach ($rows as $row) {
                $hex = KlasifikasiTabel::warnaKategori($row->nama_kategori);
                if ($hex === null || preg_match('/^#[0-9A-Fa-f]{6}$/', $hex) !== 1) {
                    // fallback palet Tailwind bila warna kategori tidak terdefinisi
                    $hex = ['#1E2A4A', '#0EA5E9', '#8B5CF6', '#EC4899', '#10B981', '#F59E0B', '#EF4444', '#14B8A6'][$row->id % 8];
                }
                $colors[] = $hex;
                $borders[] = $hex;
            }

            return [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Jumlah Event',
                        'data' => $data,
                        'backgroundColor' => $colors,
                        'borderColor' => $borders,
                        'borderWidth' => 1,
                        'borderRadius' => 6,
                        'borderSkipped' => false,
                    ],
                ],
            ];
        });
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'indexAxis' => 'y',
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => ['mode' => 'index', 'intersect' => false],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0, 'color' => '#64748B', 'font' => ['family' => 'IBM Plex Mono']],
                    'grid' => ['color' => 'rgba(0,0,0,0.06)'],
                ],
                'y' => [
                    'grid' => ['display' => false],
                    'ticks' => ['color' => '#334155', 'font' => ['family' => 'Sora', 'size' => 12], 'autoSkip' => false],
                ],
            ],
        ];
    }
}
