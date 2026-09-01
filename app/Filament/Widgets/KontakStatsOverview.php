<?php

namespace App\Filament\Widgets;

use App\Models\KategoriKegiatan;
use App\Models\Kegiatan;
use App\Models\Kontak;
use App\Models\Perusahaan;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class KontakStatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Ringkasan Database';

    protected static ?int $sort = -100;

    protected string|int|array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Cache 60 detik untuk 50 user baca bersamaan — hindari 4 query agregat tiap F5
        $cached = Cache::remember('icm:stats_overview', 60, function (): array {
            $totalPerusahaan = Perusahaan::count();
            $totalKegiatan = Kegiatan::count();
            $totalKategori = KategoriKegiatan::count();

            $aggr = Kontak::query()->selectRaw(implode(', ', [
                'count(*) as total',
                'sum(case when status_format_valid = 1 then 1 else 0 end) as valid',
                "sum(case when status_verifikasi = 'terverifikasi' then 1 else 0 end) as terverifikasi",
                "sum(case when status_verifikasi = 'perlu_dicek' then 1 else 0 end) as perlu_dicek",
                "sum(case when status_verifikasi = 'tidak_aktif' then 1 else 0 end) as tidak_aktif",
            ]))->first();

            return [
                'totalPerusahaan' => $totalPerusahaan,
                'totalKegiatan' => $totalKegiatan,
                'totalKategori' => $totalKategori,
                'aggr' => $aggr,
            ];
        });

        $totalPerusahaan = $cached['totalPerusahaan'];
        $totalKegiatan = $cached['totalKegiatan'];
        $totalKategori = $cached['totalKategori'];
        $aggr = $cached['aggr'];

        $totalKontak = (int) ($aggr->total ?? 0);
        $validHp = (int) ($aggr->valid ?? 0);
        $terverifikasi = (int) ($aggr->terverifikasi ?? 0);
        $perluDicek = (int) ($aggr->perlu_dicek ?? 0);
        $tidakAktif = (int) ($aggr->tidak_aktif ?? 0);

        $pct = fn (int $v): int => $totalKontak > 0 ? (int) round($v / $totalKontak * 100) : 0;

        return [
            // app/Filament/Widgets/KontakStatsOverview.php — Balok ringkasan KPI utama
            Stat::make('Total Perusahaan', number_format($totalPerusahaan, 0, ',', '.'))
                ->description($totalKategori.' kategori · '.$totalKegiatan.' kegiatan')
                ->descriptionIcon(Heroicon::BuildingOffice)
                ->icon(Heroicon::OutlinedBuildingOffice2)
                ->color('primary')
                ->chart([2, 4, 3, 6, 5, 7, 6]),

            Stat::make('Total Kontak', number_format($totalKontak, 0, ',', '.'))
                ->description($validHp.' nomor valid ('.$pct($validHp).'%)')
                ->descriptionIcon(Heroicon::UserGroup)
                ->icon(Heroicon::OutlinedUsers)
                ->color('primary')
                ->chart([3, 5, 4, 7, 6, 9, 8]),

            Stat::make('Terverifikasi', number_format($terverifikasi, 0, ',', '.'))
                ->description($pct($terverifikasi).'% dari total kontak')
                ->descriptionIcon(Heroicon::CheckBadge)
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success')
                ->chart([1, 3, 2, 5, 4, 6, 5])
                ->extraAttributes(['class' => 'dark:!bg-success-950/20']),

            Stat::make('Perlu Dicek', number_format($perluDicek, 0, ',', '.'))
                ->description($pct($perluDicek).'% menunggu verifikasi')
                ->descriptionIcon(Heroicon::Clock)
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('warning')
                ->chart([4, 3, 5, 4, 6, 5, 7]),

            Stat::make('Tidak Aktif', number_format($tidakAktif, 0, ',', '.'))
                ->description($pct($tidakAktif).'% non-aktif')
                ->descriptionIcon(Heroicon::NoSymbol)
                ->icon(Heroicon::OutlinedNoSymbol)
                ->color('danger'),

            Stat::make('Total Kegiatan', number_format($totalKegiatan, 0, ',', '.'))
                ->description($totalKategori.' kategori kegiatan')
                ->descriptionIcon(Heroicon::CalendarDays)
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color('primary'),
        ];
    }
}
