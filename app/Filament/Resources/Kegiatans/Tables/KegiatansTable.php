<?php

namespace App\Filament\Resources\Kegiatans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KegiatansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_event')
                    ->label('Nama Event')
                    ->searchable()
                    ->weight('semibold'),
                TextColumn::make('kategoriKegiatan.nama_kategori')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal_mulai')
                    ->label('Tanggal Mulai')
                    ->date()
                    ->sortable(),
                TextColumn::make('tanggal_selesai')
                    ->label('Tanggal Selesai')
                    ->date()
                    ->sortable(),
                TextColumn::make('venue')
                    ->label('Venue')
                    ->searchable(),
                TextColumn::make('kontaks_count')
                    ->label('Jumlah Kontak')
                    ->counts('kontaks')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('kategori_kegiatan_id')
                    ->label('Kategori')
                    ->relationship('kategoriKegiatan', 'nama_kategori'),
            ])
            ->paginated([15, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('tanggal_mulai');
    }
}
