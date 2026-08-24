<?php

namespace App\Filament\Widgets;

use App\Models\Kegiatan;
use App\Models\Kontak;
use App\Models\Perusahaan;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KontakStatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Ringkasan Database';

    protected static ?int $sort = -100;

    protected string|int|array $columnSpan = 'full';

    protected function getStats(): array
    {
        return [
            Stat::make('Total Perusahaan', number_format(Perusahaan::count(), 0, ',', '.'))
                ->description('Tercatat di database')
                ->descriptionIcon(Heroicon::BuildingOffice)
                ->icon(Heroicon::OutlinedBuildingOffice)
                ->color('primary'),

            Stat::make('Total Kontak', number_format(Kontak::count(), 0, ',', '.'))
                ->description('Kontak / PIC tercatat')
                ->descriptionIcon(Heroicon::UserGroup)
                ->icon(Heroicon::OutlinedUsers)
                ->color('primary'),

            Stat::make('Kontak Terverifikasi', number_format(Kontak::query()->where('status_verifikasi', 'terverifikasi')->count(), 0, ',', '.'))
                ->description('Nomor & data terkonfirmasi')
                ->descriptionIcon(Heroicon::CheckBadge)
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success'),

            Stat::make('Kontak Perlu Dicek', number_format(Kontak::query()->where('status_verifikasi', 'perlu_dicek')->count(), 0, ',', '.'))
                ->description('Menunggu konfirmasi')
                ->descriptionIcon(Heroicon::Clock)
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('warning'),

            Stat::make('Total Kegiatan', number_format(Kegiatan::count(), 0, ',', '.'))
                ->description('Referensi kegiatan / event')
                ->descriptionIcon(Heroicon::CalendarDays)
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color('primary'),
        ];
    }
}