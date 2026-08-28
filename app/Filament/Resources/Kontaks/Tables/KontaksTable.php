<?php

namespace App\Filament\Resources\Kontaks\Tables;

use App\Filament\Pages\ImportKontaks;
use App\Models\Kontak;
use App\Support\KontakSmartSearch;
use App\Support\PetaNomorPerusahaan;
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
use Illuminate\Contracts\Pagination\Paginator as ContractsPaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class KontaksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['perusahaan', 'kegiatan', 'kategoriKegiatan', 'updatedBy']))
            ->searchable(false)
            ->striped()
            ->header(fn (HasTable $livewire): View => view('filament.tables.kontak-summary', [
                'cards' => self::summaryCards($livewire),
                'warnaBaris' => self::warnaBarisAktif($livewire),
            ]))
            ->columnManagerLayout(ColumnManagerLayout::Modal)
            ->deferColumnManager(false)
            ->columnManagerColumns(2)
            ->reorderableColumns()
            ->recordClasses(function (Kontak $record, HasTable $livewire): ?string {
                // Sorotan nomor ganda (admin) lebih penting daripada pewarnaan
                // baris menurut kegiatan/kategori.
                if (auth()->user()?->isAdmin()
                    && PetaNomorPerusahaan::untukKontak($record, $livewire->petaNomorDipakai()) !== []) {
                    return 'bg-danger-500/10 dark:bg-danger-500/20';
                }

                return self::kelasWarnaBaris(self::warnaEfektifBaris($record));
            })
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
                    ->badge()
                    ->color(fn (Kontak $record): ?string => $record->kegiatan?->warna ?? $record->kategoriKegiatan?->warna)
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        // Sortir menurut warna lewat JOIN 1:1 (jauh lebih murah
                        // daripada correlated subquery per baris); arah di-whitelist.
                        $arah = $direction === 'desc' ? 'desc' : 'asc';

                        return $query
                            ->leftJoin('kegiatans as sort_kegiatan', 'sort_kegiatan.id', '=', 'kontaks.kegiatan_id')
                            ->leftJoin('kategori_kegiatans as sort_kategori', 'sort_kategori.id', '=', 'sort_kegiatan.kategori_kegiatan_id')
                            ->orderByRaw('coalesce(sort_kegiatan.warna, sort_kategori.warna) '.$arah)
                            ->orderBy('sort_kegiatan.nama_event', $arah);
                    })
                    ->placeholder('-')
                    ->limit(26)
                    ->tooltip(fn (Kontak $record): ?string => $record->kegiatan?->nama_event)
                    ->description(fn (Kontak $record): ?string => $record->kegiatan?->tanggal_mulai?->format('Y'))
                    ->toggleable(),
                TextColumn::make('kategoriKegiatan.nama_kategori')
                    ->label('Kategori')
                    ->size(TextSize::Small)
                    ->searchable()
                    ->badge()
                    ->color(fn (Kontak $record): ?string => $record->kategoriKegiatan?->warna ?? 'primary')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        // Sortir menurut warna kategori, nama kategori sebagai tie-breaker.
                        $arah = $direction === 'desc' ? 'desc' : 'asc';

                        return $query
                            ->leftJoin('kategori_kegiatans as sort_kategori', 'sort_kategori.id', '=', 'kontaks.kategori_kegiatan_id')
                            ->orderBy('sort_kategori.warna', $arah)
                            ->orderBy('sort_kategori.nama_kategori', $arah);
                    })
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
                    ->state(fn (Kontak $record, HasTable $livewire): ?array => PetaNomorPerusahaan::untukKontak($record, $livewire->petaNomorDipakai()))
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
     * Warna efektif pewarnaan baris: warna kegiatan, bila kosong mewarisi
     * warna kategorinya. Baris dengan kegiatan/kategori yang sama otomatis
     * berbagi warna yang sama.
     */
    public static function warnaEfektifBaris(Kontak $record): ?string
    {
        return $record->kegiatan?->warna ?? $record->kategoriKegiatan?->warna;
    }

    /**
     * Kelas CSS baris untuk nilai warna hex (#RRGGBB). Null bila format tidak
     * dikenal agar nilai liar tidak pernah bocor ke stylesheet.
     */
    public static function kelasWarnaBaris(?string $hex): ?string
    {
        if ($hex === null || preg_match('/^#[0-9a-fA-F]{6}$/', $hex) !== 1) {
            return null;
        }

        return 'baris-warna-'.strtolower(substr($hex, 1));
    }

    /**
     * Daftar warna unik dari records halaman aktif (relasi sudah eager-load),
     * dipakai header tabel untuk menghasilkan aturan <style> pewarnaan baris.
     *
     * @return array<int, string>
     */
    public static function warnaBarisAktif(HasTable $livewire): array
    {
        $records = $livewire->getTableRecords();

        $items = collect($records instanceof ContractsPaginator ? $records->items() : $records);

        return $items
            ->map(fn ($record): ?string => self::warnaEfektifBaris($record))
            ->filter(fn (?string $hex): bool => $hex !== null && preg_match('/^#[0-9a-fA-F]{6}$/', $hex) === 1)
            ->map(fn (string $hex): string => strtolower($hex))
            ->unique()
            ->values()
            ->all();
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

        // Satu query agregat menggantikan lima COUNT terpisah.
        $ringkas = (clone $query)
            ->selectRaw(implode(', ', [
                'count(*) as total',
                'sum(case when status_format_valid = 1 then 1 else 0 end) as valid',
                "sum(case when status_verifikasi = 'terverifikasi' then 1 else 0 end) as terverifikasi",
                "sum(case when status_verifikasi = 'perlu_dicek' then 1 else 0 end) as perlu_dicek",
                "sum(case when status_verifikasi = 'tidak_aktif' then 1 else 0 end) as tidak_aktif",
            ]))
            ->first();

        if ($ringkas === null) {
            return [];
        }

        return [
            [
                'key' => 'total',
                'label' => 'Total kontak',
                'icon' => 'heroicon-o-user-group',
                'color' => '#1E2A4A',
                'count' => (int) $ringkas->total,
            ],
            [
                'key' => 'valid',
                'label' => 'Nomor valid',
                'icon' => 'heroicon-o-check-badge',
                'color' => '#1F8A70',
                'count' => (int) $ringkas->valid,
            ],
            [
                'key' => 'terverifikasi',
                'label' => 'Terverifikasi',
                'icon' => 'heroicon-o-check-circle',
                'color' => '#2E7D32',
                'count' => (int) $ringkas->terverifikasi,
            ],
            [
                'key' => 'perlu_dicek',
                'label' => 'Perlu dicek',
                'icon' => 'heroicon-o-clock',
                'color' => '#D98E04',
                'count' => (int) $ringkas->perlu_dicek,
            ],
            [
                'key' => 'tidak_aktif',
                'label' => 'Tidak aktif',
                'icon' => 'heroicon-o-x-circle',
                'color' => '#C0392B',
                'count' => (int) $ringkas->tidak_aktif,
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
