<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Lab404\Impersonate\Services\ImpersonateManager;

// app/Filament/Resources/Users/Tables/UsersTable.php
class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->circular()
                    ->disk('public')
                    ->extraImgAttributes(['alt' => 'Avatar']),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('divisi.name')
                    ->label('Divisi')
                    ->placeholder('-')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('roles.name')
                    ->label('Peran')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'primary',
                        'karyawan' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->searchable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Telepon')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('joined_at')
                    ->label('Bergabung')
                    ->date('d M Y')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('divisi_id')
                    ->label('Divisi')
                    ->relationship('divisi', 'name'),
                SelectFilter::make('roles')
                    ->label('Peran')
                    ->relationship('roles', 'name'),
                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif (Approved)')
                    ->falseLabel('Menunggu Persetujuan'),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Akun')
                    ->modalDescription(fn ($record) => 'Setujui '.$record->name.'? Akun akan aktif dan bisa login.')
                    ->visible(fn ($record) => ! $record->is_active && (bool) auth()->user()?->isAdmin())
                    ->action(function ($record) {
                        $record->update(['is_active' => true]);
                        Notification::make()->title('Akun disetujui')->success()->send();
                    }),
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Akun')
                    ->modalDescription(fn ($record) => 'Tolak dan hapus '.$record->name.'?')
                    ->visible(fn ($record) => ! $record->is_active && (bool) auth()->user()?->isAdmin())
                    ->action(function ($record) {
                        $record->delete();
                        Notification::make()->title('Akun ditolak & dihapus')->success()->send();
                    }),
                EditAction::make(),
                Action::make('impersonate')
                    ->label('Impersonate')
                    ->icon('heroicon-o-eye')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Impersonate Karyawan')
                    ->modalDescription(fn ($record) => 'Login sebagai '.$record->name.'? Anda akan beralih ke sesi karyawan tersebut.')
                    ->visible(fn ($record) => auth()->user()?->canImpersonate() && $record->canBeImpersonated())
                    ->action(function ($record) {
                        $manager = app(ImpersonateManager::class);
                        $manager->take(auth()->user(), $record);

                        return redirect()->to('/admin');
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}
