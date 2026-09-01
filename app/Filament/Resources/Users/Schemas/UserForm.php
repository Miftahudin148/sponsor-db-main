<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

// app/Filament/Resources/Users/Schemas/UserForm.php
class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Avatar & Status')
                    ->description(fn () => auth()->user()?->isAdmin() ? 'Foto profil dan status keaktifan akun' : 'Foto profil Anda — status hanya bisa diubah Admin')
                    ->icon('heroicon-o-photo')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('avatar_url')
                            ->label('Avatar')
                            ->avatar()
                            ->image()
                            ->disk('public')
                            ->directory('avatars')
                            ->maxSize(2048)
                            ->imageEditor()
                            ->helperText('Maks 2 MB. Format: jpg, png, webp.')
                            ->columnSpan(1),

                        Toggle::make('is_active')
                            ->label('Akun Aktif')
                            ->helperText(fn () => auth()->user()?->isAdmin() ? 'Nonaktifkan untuk mencegah login tanpa menghapus data' : 'Hanya Admin yang bisa mengubah status')
                            ->default(true)
                            ->inline(false)
                            ->disabled(fn () => ! (bool) auth()->user()?->isAdmin())
                            ->dehydrated(fn () => (bool) auth()->user()?->isAdmin())
                            ->columnSpan(1),
                    ]),

                Section::make('Informasi Pribadi')
                    ->description('Data karyawan untuk administrasi internal')
                    ->icon('heroicon-o-user')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Budi Santoso'),

                        TextInput::make('nip')
                            ->label('NIP')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('198001012005011001')
                            ->helperText(fn () => auth()->user()?->isAdmin() ? 'Nomor Induk Pegawai — unik' : 'Hubungi Admin untuk ubah NIP')
                            ->disabled(fn () => ! (bool) auth()->user()?->isAdmin())
                            ->dehydrated(fn () => (bool) auth()->user()?->isAdmin()),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('No. Telepon')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('0812-3456-7890'),

                        DatePicker::make('joined_at')
                            ->label('Tanggal Bergabung')
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->placeholder('Pilih tanggal')
                            ->disabled(fn () => ! (bool) auth()->user()?->isAdmin())
                            ->dehydrated(fn () => (bool) auth()->user()?->isAdmin()),

                        Select::make('divisi_id')
                            ->label('Divisi')
                            ->relationship('divisi', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Pilih divisi')
                            ->disabled(fn () => ! (bool) auth()->user()?->isAdmin())
                            ->dehydrated(fn () => (bool) auth()->user()?->isAdmin())
                            ->createOptionForm([
                                TextInput::make('name')->label('Nama Divisi')->required()->maxLength(255),
                                TextInput::make('description')->label('Deskripsi')->maxLength(255),
                            ]),
                    ]),

                Section::make('Autentikasi & Akses')
                    ->description('Kredensial login dan hak akses berbasis peran')
                    ->icon('heroicon-o-shield-check')
                    ->columns(2)
                    ->schema([
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->helperText('Kosongkan jika tidak ingin mengubah password')
                            ->maxLength(255),

                        Select::make('roles')
                            ->label('Peran (Role)')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->native(false)
                            ->helperText(fn () => auth()->user()?->isAdmin() ? 'Pilih satu atau lebih peran: Admin, karyawan' : 'Hanya Admin yang bisa mengubah peran')
                            ->disabled(fn () => ! (bool) auth()->user()?->isAdmin())
                            ->dehydrated(fn () => (bool) auth()->user()?->isAdmin())
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
