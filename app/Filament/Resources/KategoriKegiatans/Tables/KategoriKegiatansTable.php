<?php

namespace App\Filament\Resources\KategoriKegiatans\Tables;

use App\Models\KategoriKegiatan;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KategoriKegiatansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_kategori')
                    ->label('Nama Kategori')
                    ->searchable()
                    ->weight('semibold'),
                ColorColumn::make('warna')
                    ->label('Warna')
                    ->copyable()
                    ->placeholder('-'),
                TextColumn::make('deskripsi')
                    ->label('Deskripsi')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('kegiatans_count')
                    ->label('Jumlah Kegiatan')
                    ->counts('kegiatans')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->recordActions([
                EditAction::make()
                    ->visible(fn (KategoriKegiatan $record) => auth()->user()?->can('update', $record) ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('deleteAny', KategoriKegiatan::class) ?? false),
                ]),
            ])
            ->defaultSort('nama_kategori');
    }
}
