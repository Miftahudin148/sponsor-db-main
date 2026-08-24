<?php

namespace App\Filament\Resources\Kontaks\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KontakInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Utama')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('perusahaan.nama_standar')
                            ->label('Perusahaan'),
                        TextEntry::make('perusahaan.industri')
                            ->label('Industri')
                            ->placeholder('-'),
                        TextEntry::make('nama')
                            ->label('Nama PIC')
                            ->weight('semibold'),
                        TextEntry::make('no_telepon')
                            ->label('No. Telepon')
                            ->copyable()
                            ->icon('heroicon-o-phone'),
                    ]),
                Section::make('Kegiatan')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('kegiatan.nama_event')
                            ->label('Kegiatan')
                            ->placeholder('-'),
                        TextEntry::make('kegiatan.tanggal_mulai')
                            ->label('Tanggal Mulai')
                            ->date('d M Y')
                            ->placeholder('-'),
                        TextEntry::make('kategoriKegiatan.nama_kategori')
                            ->label('Kategori')
                            ->placeholder('-'),
                    ]),
                Section::make('Status & Validasi')
                    ->columns(2)
                    ->schema([
                        IconEntry::make('status_format_valid')
                            ->label('Format Nomor Valid')
                            ->boolean(),
                        TextEntry::make('status_verifikasi')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'terverifikasi' => 'success',
                                'perlu_dicek' => 'warning',
                                'tidak_aktif' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('updatedBy.name')
                            ->label('Diperbarui oleh')
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->label('Dibuat pada')
                            ->dateTime('d M Y H:i'),
                    ]),
            ]);
    }
}
