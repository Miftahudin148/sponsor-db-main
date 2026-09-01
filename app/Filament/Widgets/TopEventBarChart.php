<?php

namespace App\Filament\Widgets;

use App\Models\Kegiatan;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TopEventBarChart extends ChartWidget
{
    protected ?string $heading = 'Top 5 Event Terbesar';

    protected ?string $description = 'Berdasarkan jumlah kontak/peserta';

    protected ?string $maxHeight = '340px';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -97;

    protected static bool $isLazy = false;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        return Cache::remember('icm:chart_top_event', 60, function (): array {
            // Satu agregat: Top 5 event dengan kontak terbanyak
            $rows = Kegiatan::query()
                ->leftJoin('kontaks', 'kontaks.kegiatan_id', '=', 'kegiatans.id')
                ->select('kegiatans.id', 'kegiatans.nama_event', DB::raw('count(kontaks.id) as kontak_count'))
                ->groupBy('kegiatans.id', 'kegiatans.nama_event')
                ->orderByDesc('kontak_count')
                ->orderBy('kegiatans.nama_event')
                ->limit(5)
                ->get();

            if ($rows->isEmpty()) {
                return [
                    'labels' => [],
                    'datasets' => [['label' => 'Jumlah Kontak', 'data' => []]],
                ];
            }

            $labels = $rows->pluck('nama_event')->all();
            $data = $rows->pluck('kontak_count')->map(fn ($v) => (int) $v)->all();

            // Warna bervariatif per bar (kolom vertikal) — palet berbeda tiap event agar insight cepat
            $paletteBg = ['#1E2A4A', '#0EA5E9', '#8B5CF6', '#EC4899', '#10B981'];
            $paletteBd = ['#162035', '#0284C7', '#7C3AED', '#DB2777', '#059669'];
            $colors = [];
            $borders = [];
            foreach ($rows as $idx => $row) {
                $hexBg = $row->warna ?? $paletteBg[$idx % count($paletteBg)];
                // validasi hex
                if (preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $hexBg) !== 1) {
                    $hexBg = $paletteBg[$idx % count($paletteBg)];
                }
                $colors[] = $hexBg;
                $borders[] = $paletteBd[$idx % count($paletteBd)];
            }

            return [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Jumlah Kontak',
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
            'indexAxis' => 'x',
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => ['mode' => 'index', 'intersect' => false],
            ],
            'scales' => [
                'x' => [
                    'grid' => ['display' => false],
                    'ticks' => [
                        'color' => '#334155',
                        'font' => ['family' => 'Sora', 'size' => 11],
                        'maxRotation' => 0,
                        'autoSkip' => false,
                        'callback' => null,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0, 'color' => '#64748B', 'font' => ['family' => 'IBM Plex Mono']],
                    'grid' => ['color' => 'rgba(0,0,0,0.06)'],
                ],
            ],
        ];
    }
}
