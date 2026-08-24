<?php

namespace App\Filament\Resources\Kontaks\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class KontakForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('perusahaan_id')
                    ->label('Perusahaan')
                    ->relationship('perusahaan', 'nama_standar')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        TextInput::make('nama_standar')
                            ->label('Nama Standar')
                            ->required()
                            ->unique()
                            ->maxLength(255),
                        TextInput::make('industri')
                            ->label('Industri')
                            ->maxLength(255),
                    ]),
                Select::make('kegiatan_id')
                    ->label('Kegiatan')
                    ->relationship('kegiatan', 'nama_event')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        $kategoriId = $state ? \App\Models\Kegiatan::find($state)?->kategori_kegiatan_id : null;
                        $set('kategori_kegiatan_id', $kategoriId);
                    }),
                Select::make('kategori_kegiatan_id')
                    ->label('Kategori')
                    ->relationship('kategoriKegiatan', 'nama_kategori')
                    ->searchable()
                    ->preload(),
                TextInput::make('nama')
                    ->label('Nama PIC')
                    ->required()
                    ->maxLength(255),
                TextInput::make('no_telepon')
                    ->label('No. Telepon')
                    ->tel()
                    ->placeholder('contoh: 0811-1465-133')
                    ->helperText('Otomatis disimpan sebagai 628xxxxxxxxxx (tanpa + / 0 di depan).')
                    ->maxLength(50),
                Toggle::make('status_format_valid')
                    ->label('Format Nomor Valid')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Dihitung otomatis dari format nomor.'),
                Select::make('status_verifikasi')
                    ->label('Status Verifikasi')
                    ->options([
                        'terverifikasi' => 'Terverifikasi',
                        'perlu_dicek' => 'Perlu dicek',
                        'tidak_aktif' => 'Tidak aktif',
                    ])
                    ->default('terverifikasi')
                    ->required(),
            ])
            ->columns(2);
    }
}