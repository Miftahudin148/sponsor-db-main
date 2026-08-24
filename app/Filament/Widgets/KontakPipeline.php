<?php

namespace App\Filament\Widgets;

use App\Models\Kontak;
use Filament\Widgets\Widget;

class KontakPipeline extends Widget
{
    protected static ?int $sort = -99;

    protected string|int|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.kontak-pipeline';

    protected function getViewData(): array
    {
        $total = (int) Kontak::count();

        $rows = [];
        foreach ([
            'terverifikasi' => ['Terverifikasi', '#1F8A70'],
            'perlu_dicek' => ['Perlu dicek', '#D98E04'],
            'tidak_aktif' => ['Tidak aktif', '#C0392B'],
        ] as $key => [$label, $color]) {
            $count = (int) Kontak::query()->where('status_verifikasi', $key)->count();
            $rows[] = [
                'key' => $key,
                'label' => $label,
                'color' => $color,
                'count' => $count,
                'percent' => $total > 0 ? (int) round($count / $total * 100) : 0,
            ];
        }

        return [
            'total' => $total,
            'rows' => $rows,
        ];
    }
}
