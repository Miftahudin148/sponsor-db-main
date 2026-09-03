<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Permission;

// app/Filament/Resources/Roles/Schemas/RoleForm.php
class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Peran')
                ->description('Nama peran yang mudah dipahami admin (contoh: Viewer, Manager)')
                ->icon('heroicon-o-shield-check')
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Peran')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50)
                        ->placeholder('viewer'),
                    TextInput::make('guard_name')
                        ->label('Guard')
                        ->default('web')
                        ->disabled()
                        ->dehydrated(false),
                ]),

            Section::make('Hak Akses per Modul (Per-Role)')
                ->description('Centang hak yang diberikan untuk peran ini — karyawan hanya dapat sesuai role, tidak per-orang. Admin bypass semua.')
                ->icon('heroicon-o-adjustments-horizontal')
                ->columns(1)
                ->schema([
                    CheckboxList::make('permissions')
                        ->label('Hak Akses')
                        ->relationship('permissions', 'name')
                        ->getOptionLabelFromRecordUsing(fn (Permission $record): string => match ($record->name) {
                            'kontak.view_any' => 'Kontak — Lihat Daftar',
                            'kontak.view' => 'Kontak — Lihat Detail',
                            'kontak.create' => 'Kontak — Tambah',
                            'kontak.update' => 'Kontak — Ubah',
                            'kontak.delete' => 'Kontak — Hapus',
                            'kontak.export' => 'Kontak — Export CSV',
                            'kontak.import' => 'Kontak — Import Excel',
                            'perusahaan.view_any' => 'Perusahaan — Lihat Daftar',
                            'perusahaan.view' => 'Perusahaan — Lihat Detail',
                            'perusahaan.create' => 'Perusahaan — Tambah',
                            'perusahaan.update' => 'Perusahaan — Ubah',
                            'perusahaan.delete' => 'Perusahaan — Hapus',
                            'kegiatan.view_any' => 'Kegiatan — Lihat Daftar',
                            'kegiatan.view' => 'Kegiatan — Lihat Detail',
                            'kegiatan.create' => 'Kegiatan — Tambah',
                            'kegiatan.update' => 'Kegiatan — Ubah',
                            'kegiatan.delete' => 'Kegiatan — Hapus',
                            'kategori_kegiatan.view_any' => 'Kategori — Lihat Daftar',
                            'kategori_kegiatan.view' => 'Kategori — Lihat Detail',
                            'kategori_kegiatan.create' => 'Kategori — Tambah',
                            'kategori_kegiatan.update' => 'Kategori — Ubah',
                            'kategori_kegiatan.delete' => 'Kategori — Hapus',
                            default => $record->name,
                        })
                        ->searchable()
                        ->bulkToggleable()
                        ->columns(2)
                        ->gridDirection('row')
                        ->helperText('Gunakan cari untuk filter, bulk toggle untuk pilih semua per modul'),
                ]),
        ]);
    }
}
