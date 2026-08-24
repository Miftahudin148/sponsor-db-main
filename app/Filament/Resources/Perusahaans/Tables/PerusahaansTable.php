<?php

namespace App\Filament\Resources\Perusahaans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PerusahaansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_standar')
                    ->label('Nama Standar')
                    ->searchable()
                    ->weight('semibold'),
                TextColumn::make('industri')
                    ->label('Industri')
                    ->searchable(),
                TextColumn::make('kontaks_count')
                    ->label('Jumlah Kontak')
                    ->counts('kontaks')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updatedBy.name')
                    ->label('Diperbarui oleh')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
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
            ->defaultSort('nama_standar');
    }
}
