<?php

namespace App\Filament\Widgets;

use App\Models\Kontak;
use Filament\Widgets\ChartWidget;

class StatusVerifikasiChart extends ChartWidget
{
    protected ?string $heading = 'Status Verifikasi Kontak';

    protected ?string $description = 'Kontak dikelompokkan per status verifikasi';

    protected ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'donut';
    }

    protected function getData(): array
    {
        $labels = ['terverifikasi', 'perlu_dicek', 'tidak_aktif'];
        $colors = ['#1F8A70', '#D98E04', '#C0392B'];

        $data = [];
        foreach ($labels as $index => $status) {
            $data[] = (int) Kontak::query()->where('status_verifikasi', $status)->count();
        }

        $present = array_filter($data, static fn (int $count): bool => $count > 0);

        if ($present === []) {
            return ['labels' => [], 'datasets' => [['data' => []]]];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'legend' => ['position' => 'bottom'],
            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'labels' => ['show' => true, 'total' => ['show' => true]],
                    ],
                ],
            ],
        ];
    }
}

