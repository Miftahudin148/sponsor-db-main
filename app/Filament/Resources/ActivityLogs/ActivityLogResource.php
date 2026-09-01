<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

// app/Filament/Resources/ActivityLogs/ActivityLogResource.php
class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Histori';

    protected static ?string $pluralModelLabel = 'Histori Aktivitas';

    protected static ?string $modelLabel = 'Histori';

    protected static string|\UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 100;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i')
                    ->description(fn (Activity $record) => $record->created_at->diffForHumans(), position: 'below')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('causer.name')
                    ->label('Pelaku')
                    ->placeholder('Sistem')
                    ->icon('heroicon-o-user-circle')
                    ->searchable()
                    ->visible(fn (): bool => (bool) auth()->user()?->isAdmin())
                    ->toggleable(),

                TextColumn::make('event')
                    ->label('Aksi')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'created' => 'Ditambahkan',
                        'updated' => 'Diperbarui',
                        'deleted' => 'Dihapus',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'created' => 'heroicon-o-plus-circle',
                        'updated' => 'heroicon-o-pencil-square',
                        'deleted' => 'heroicon-o-trash',
                        default => 'heroicon-o-clock',
                    })
                    ->sortable(),

                TextColumn::make('subject_type')
                    ->label('Data')
                    ->formatStateUsing(fn (?string $state, Activity $record): string => self::labelModel($state, $record))
                    ->badge()
                    ->color(fn (string $state, Activity $record): string => match (class_basename($record->subject_type ?? '')) {
                        'Perusahaan' => 'primary',
                        'Kontak' => 'success',
                        'Kegiatan' => 'warning',
                        'KategoriKegiatan' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('ringkasan')
                    ->label('Ringkasan Perubahan')
                    ->state(fn (Activity $record): string => self::ringkasanPerubahan($record))
                    ->html()
                    ->wrap()
                    ->limit(80)
                    ->toggleable(),

                TextColumn::make('description')
                    ->label('Keterangan')
                    ->placeholder('-')
                    ->limit(40)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('event')
                    ->label('Aksi')
                    ->options([
                        'created' => 'Ditambahkan',
                        'updated' => 'Diperbarui',
                        'deleted' => 'Dihapus',
                    ])
                    ->native(false),
                SelectFilter::make('subject_type')
                    ->label('Jenis Data')
                    ->options([
                        'App\Models\Perusahaan' => 'Perusahaan',
                        'App\Models\Kontak' => 'Kontak',
                        'App\Models\Kegiatan' => 'Kegiatan',
                        'App\Models\KategoriKegiatan' => 'Kategori',
                    ])
                    ->native(false),
                Filter::make('created_at')
                    ->label('Tanggal')
                    ->schema([
                        DatePicker::make('from')->label('Dari')->native(false),
                        DatePicker::make('until')->label('Sampai')->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat')
                    ->modalHeading(fn (Activity $record) => self::judulModal($record))
                    ->modalWidth('2xl')
                    ->schema([
                        TextEntry::make('created_at')->label('Waktu')->dateTime('d F Y, H:i:s')->icon('heroicon-o-clock'),
                        TextEntry::make('causer.name')->label('Pelaku')->placeholder('Sistem')->icon('heroicon-o-user'),
                        TextEntry::make('event')->label('Aksi')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                            'created' => 'Ditambahkan', 'updated' => 'Diperbarui', 'deleted' => 'Dihapus', default => $state,
                        })->color(fn (string $state): string => match ($state) {
                            'created' => 'success', 'updated' => 'warning', 'deleted' => 'danger', default => 'gray',
                        }),
                        TextEntry::make('subject_type')->label('Jenis Data')->formatStateUsing(fn (?string $state, $record) => self::labelModel($state, $record))->badge(),
                        TextEntry::make('subject_id')->label('ID Data')->placeholder('-'),
                        ViewEntry::make('properties')->label('Detail Perubahan')->view('filament.infolists.entries.activity-properties'),
                    ]),
            ])
            ->emptyStateHeading('Belum ada histori')
            ->emptyStateDescription('Histori akan muncul otomatis setiap ada penambahan, perubahan, atau penghapusan data. Untuk karyawan, hanya histori Anda sendiri yang tampil.')
            ->emptyStateIcon('heroicon-o-clock')
            ->poll('30s')
            ->toolbarActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['causer', 'subject']);

        $user = auth()->user();

        if ($user && ! $user->isAdmin()) {
            $query->where('causer_id', $user->getKey())
                ->where('causer_type', $user::class);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    // === Helper ramah pengguna — tanpa istilah kode ===

    private static function labelModel(?string $type, ?Activity $record = null): string
    {
        $base = $type ? class_basename($type) : '-';

        return match ($base) {
            'Perusahaan' => 'Perusahaan',
            'Kontak' => 'Kontak',
            'Kegiatan' => 'Kegiatan',
            'KategoriKegiatan' => 'Kategori',
            'User' => 'Pengguna',
            default => $base,
        };
    }

    private static function judulModal(Activity $record): string
    {
        $aksi = match ($record->event) {
            'created' => 'Menambahkan',
            'updated' => 'Memperbarui',
            'deleted' => 'Menghapus',
            default => 'Aktivitas',
        };

        $model = self::labelModel($record->subject_type);

        return $aksi.' '.$model.' #'.$record->subject_id;
    }

    private static function ringkasanPerubahan(Activity $record): string
    {
        $props = $record->properties?->toArray() ?? [];
        $old = $props['old'] ?? [];
        $attributes = $props['attributes'] ?? [];

        if ($record->event === 'created' && ! empty($attributes)) {
            $fields = array_map(fn ($k) => self::labelField($k), array_keys($attributes));

            return '<span class="text-success-600 dark:text-success-400">Menambahkan: '.e(implode(', ', $fields)).'</span>';
        }

        if ($record->event === 'deleted') {
            return '<span class="text-danger-600">Menghapus data</span>';
        }

        if (empty($old) && empty($attributes)) {
            return '<span class="text-gray-400">-</span>';
        }

        $changed = array_unique(array_merge(array_keys($old), array_keys($attributes)));
        $labels = array_map(fn ($k) => self::labelField($k), $changed);

        return e(implode(', ', $labels)).' <span class="text-gray-400">('.count($changed).' field diubah)</span>';
    }

    public static function labelField(string $key): string
    {
        return match ($key) {
            'nama_standar' => 'Nama Perusahaan',
            'industri' => 'Industri',
            'catatan' => 'Catatan',
            'nama' => 'Nama PIC',
            'no_telepon' => 'No. Telepon',
            'perusahaan_id' => 'Perusahaan',
            'kegiatan_id' => 'Kegiatan',
            'kategori_kegiatan_id' => 'Kategori',
            'status_verifikasi' => 'Status Verifikasi',
            'status_format_valid' => 'Validitas Nomor',
            'nama_event' => 'Nama Event',
            'nama_kategori' => 'Nama Kategori',
            'deskripsi' => 'Deskripsi',
            'warna' => 'Warna',
            'tanggal_mulai' => 'Tanggal Mulai',
            'tanggal_selesai' => 'Tanggal Selesai',
            'venue' => 'Lokasi',
            'email' => 'Email',
            'role' => 'Peran',
            default => str_replace('_', ' ', ucfirst($key)),
        };
    }
}
