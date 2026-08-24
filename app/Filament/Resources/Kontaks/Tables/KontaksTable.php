<?php

namespace App\Filament\Resources\Kontaks\Tables;

use App\Filament\Pages\ImportKontaks;
use App\Models\Kontak;
use App\Support\KontakSmartSearch;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\ColumnManagerLayout;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class KontaksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchable(false)
            ->striped()
            ->header(fn (HasTable $livewire): View => view('filament.tables.kontak-summary', [
                'cards' => self::summaryCards($livewire),
            ]))
            ->columnManagerLayout(ColumnManagerLayout::Modal)
            ->deferColumnManager(false)
            ->columnManagerColumns(2)
            ->reorderableColumns()
            ->recordClasses(fn (Kontak $record): ?string => (auth()->user()?->isAdmin() && $record->perusahaanLainDenganNomorSama() !== [])
                ? 'bg-danger-500/10 dark:bg-danger-500/20'
                : null)
            ->emptyStateHeading('Belum ada kontak')
            ->emptyStateDescription('Mulai dengan mengimpor file kontak sponsor (Excel/CSV) atau membuat kontak baru satu per satu.')
            ->emptyStateIcon(Heroicon::OutlinedInbox)
            ->emptyStateActions([
                Action::make('importKontak')
                    ->label('Import Data')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->url(ImportKontaks::getUrl())
                    ->button(),
            ])
            ->columns([
                TextColumn::make('No')
                    ->label('No')
                    ->rowIndex()
                    ->size(TextSize::Small)
                    ->alignCenter()
                    ->extraAttributes(['style' => 'min-width: 3rem'])
                    ->toggleable(),
                TextColumn::make('perusahaan.nama_standar')
                    ->label('Nama Perusahaan')
                    ->size(TextSize::Small)
                    ->weight('medium')
                    ->searchable()
                    ->sortable()
                    ->limit(42)
                    ->tooltip(fn (Kontak $record): string => $record->perusahaan?->nama_standar ?? '')
                    ->toggleable(),
                TextColumn::make('nama')
                    ->label('PIC')
                    ->size(TextSize::Small)
                    ->description(fn (Kontak $record): ?string => $record->perusahaan?->industri)
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->limit(28)
                    ->tooltip(fn (Kontak $record): string => $record->nama ?? '')
                    ->toggleable(),
                TextColumn::make('no_telepon')
                    ->label('No. Telepon')
                    ->size(TextSize::Small)
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon(Heroicon::OutlinedPhone)
                    ->iconPosition(IconPosition::After)
                    ->extraAttributes(['style' => 'white-space: nowrap'])
                    ->toggleable(),
                TextColumn::make('kegiatan.nama_event')
                    ->label('Kegiatan')
                    ->size(TextSize::Small)
                    ->searchable()
                    ->sortable()
                    ->placeholder('-')
                    ->limit(26)
                    ->tooltip(fn (Kontak $record): ?string => $record->kegiatan?->nama_event)
                    ->description(fn (Kontak $record): ?string => $record->kegiatan?->tanggal_mulai?->format('Y'))
                    ->toggleable(),
                TextColumn::make('kategoriKegiatan.nama_kategori')
                    ->label('Kategori')
                    ->size(TextSize::Small)
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->placeholder('-')
                    ->limit(28)
                    ->tooltip(fn (Kontak $record): ?string => $record->kategoriKegiatan?->nama_kategori)
                    ->toggleable(),
                TextColumn::make('klasifikasi_cari')
                    ->label('Cocok pada')
                    ->badge()
                    ->color('primary')
                    ->size(TextSize::ExtraSmall)
                    ->placeholder('-')
                    ->state(function (Kontak $record, HasTable $livewire): ?string {
                        $state = $livewire->getTableFilterState('cari') ?? [];
                        $q = trim((string) ($state['q'] ?? ''));

                        if ($q === '') {
                            return null;
                        }

                        return implode(' + ', app(KontakSmartSearch::class)->matchColumns($record, $q));
                    })
                    ->visible(fn (HasTable $livewire): bool => filled(trim((string) ($livewire->getTableFilterState('cari')['q'] ?? ''))))
                    ->toggleable(),
                IconColumn::make('status_format_valid')
                    ->label('Valid')
                    ->boolean()
                    ->size(IconSize::ExtraSmall)
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->extraAttributes(['style' => 'min-width: 3rem'])
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('status_verifikasi')
                    ->label('Status')
                    ->badge()
                    ->size(TextSize::ExtraSmall)
                    ->color(fn (string $state): string => match ($state) {
                        'terverifikasi' => 'success',
                        'perlu_dicek' => 'warning',
                        'tidak_aktif' => 'danger',
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('updatedBy.name')
                    ->label('Diperbarui oleh')
                    ->size(TextSize::ExtraSmall)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nomor_dipakai_perusahaan_lain')
                    ->label('Nomor Dipakai Perusahaan Lain')
                    ->badge()
                    ->color('danger')
                    ->size(TextSize::ExtraSmall)
                    ->state(fn (Kontak $record): ?array => $record->perusahaanLainDenganNomorSama())
                    ->formatStateUsing(fn (?array $state): ?string => $state ? implode('; ', $state) : null)
                    ->placeholder('-')
                    ->visible(fn (): bool => (bool) auth()->user()?->isAdmin())
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('cari')
                    ->label('Pencarian')
                    ->schema([
                        TextInput::make('q')
                            ->label('Kata kunci')
                            ->placeholder('Cari perusahaan, PIC, nomor, kegiatan, kategori...')
                            ->live()
                            ->debounce(400),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => filled(trim((string) ($data['q'] ?? '')))
                        ? app(KontakSmartSearch::class)->applyTo($query, trim((string) $data['q']))
                        : $query),
                SelectFilter::make('kegiatan_id')
                    ->label('Kegiatan')
                    ->relationship('kegiatan', 'nama_event'),
                SelectFilter::make('kategori_kegiatan_id')
                    ->label('Kategori')
                    ->relationship('kategoriKegiatan', 'nama_kategori'),
                SelectFilter::make('status_verifikasi')
                    ->label('Status')
                    ->options([
                        'terverifikasi' => 'Terverifikasi',
                        'perlu_dicek' => 'Perlu dicek',
                        'tidak_aktif' => 'Tidak aktif',
                    ]),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->filtersFormColumns(['sm' => 1, 'md' => 2, 'lg' => 4, 'xl' => 4, '2xl' => 4])
            ->paginated([25, 50, 100, 250, 500])
            ->defaultPaginationPageOption(50)
            ->recordActions([
                ViewAction::make()
                    ->slideOver(),
                Action::make('whatsapp')
                    ->label('Kirim WhatsApp')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->color('success')
                    ->url(fn (Kontak $record): string => self::whatsappUrl($record))
                    ->openUrlInNewTab()
                    ->visible(fn (Kontak $record): bool => filled($record->no_telepon)),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
                Action::make('export')
                    ->label('Ekspor CSV')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->openUrlInNewTab()
                    ->url(fn (HasTable $livewire): string => route('kontaks.export', self::exportParams($livewire))),
            ])
            ->defaultSort('nama');
    }

    /**
     * Ringkasan yang mengikuti filter aktif pada tabel. Ditampilkan sebagai
     * strip statistik di atas tabel (bukan footer bawaan Filament).
     *
     * @return array<int, array{key: string, label: string, icon: string, color: string, count: int}>
     */
    public static function summaryCards(HasTable $livewire): array
    {
        $query = $livewire->getFilteredTableQuery();

        if (! $query) {
            return [];
        }

        $count = static fn (callable $scope): int => (int) $scope(clone $query)->count();

        return [
            [
                'key' => 'total',
                'label' => 'Total kontak',
                'icon' => 'heroicon-o-user-group',
                'color' => '#1E2A4A',
                'count' => $count(fn (Builder $q): Builder => $q),
            ],
            [
                'key' => 'valid',
                'label' => 'Nomor valid',
                'icon' => 'heroicon-o-check-badge',
                'color' => '#1F8A70',
                'count' => $count(fn (Builder $q): Builder => $q->where('status_format_valid', true)),
            ],
            [
                'key' => 'terverifikasi',
                'label' => 'Terverifikasi',
                'icon' => 'heroicon-o-check-circle',
                'color' => '#2E7D32',
                'count' => $count(fn (Builder $q): Builder => $q->where('status_verifikasi', 'terverifikasi')),
            ],
            [
                'key' => 'perlu_dicek',
                'label' => 'Perlu dicek',
                'icon' => 'heroicon-o-clock',
                'color' => '#D98E04',
                'count' => $count(fn (Builder $q): Builder => $q->where('status_verifikasi', 'perlu_dicek')),
            ],
            [
                'key' => 'tidak_aktif',
                'label' => 'Tidak aktif',
                'icon' => 'heroicon-o-x-circle',
                'color' => '#C0392B',
                'count' => $count(fn (Builder $q): Builder => $q->where('status_verifikasi', 'tidak_aktif')),
            ],
        ];
    }

    protected static function exportParams(HasTable $livewire): array
    {
        $params = [];

        $q = trim((string) (data_get($livewire->getTableFilterState('cari'), 'q') ?? ''));
        if ($q !== '') {
            $params['q'] = $q;
        }

        foreach ([
            'status' => 'status_verifikasi',
            'kegiatan_id' => 'kegiatan_id',
            'kategori_kegiatan_id' => 'kategori_kegiatan_id',
        ] as $queryKey => $filterName) {
            $value = (string) (data_get($livewire->getTableFilterState($filterName), 'value') ?? '');
            if ($value !== '') {
                $params[$queryKey] = $value;
            }
        }

        return $params;
    }

    protected static function whatsappUrl(Kontak $record): string
    {
        $phone = preg_replace('/\D+/', '', (string) $record->no_telepon);

        return $phone === '' ? '#' : 'https://wa.me/'.$phone;
    }
}
