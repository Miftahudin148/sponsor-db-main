<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

// app/Filament/Resources/Roles/Tables/RolesTable.php
class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Peran')
                    ->searchable()
                    ->weight('medium')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'admin' => 'primary',
                        'karyawan' => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('permissions_count')
                    ->label('Jumlah Hak')
                    ->counts('permissions')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn ($record) => ! in_array($record->name, ['admin', 'karyawan'])),
            ])
            ->defaultSort('name');
    }
}
