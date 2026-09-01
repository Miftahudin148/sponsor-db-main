<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\PhoneNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Kontak extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'perusahaan_id',
        'kegiatan_id',
        'kategori_kegiatan_id',
        'nama',
        'no_telepon',
        'status_format_valid',
        'status_verifikasi',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status_format_valid' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['perusahaan_id', 'kegiatan_id', 'kategori_kegiatan_id', 'nama', 'no_telepon', 'status_verifikasi'])
            ->logOnlyDirty()
            ->useLogName('kontak')
            ->dontSubmitEmptyLogs();
    }

    public function setNoTeleponAttribute($value): void
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            $this->attributes['no_telepon'] = null;
            $this->attributes['status_format_valid'] = false;

            return;
        }

        $normalized = PhoneNormalizer::normalize($raw);
        $this->attributes['no_telepon'] = PhoneNormalizer::isValid($normalized)
            ? $normalized
            : preg_replace('/\s+/', ' ', $raw);
        $this->attributes['status_format_valid'] = PhoneNormalizer::isValid($normalized);
    }

    public function perusahaan(): BelongsTo
    {
        return $this->belongsTo(Perusahaan::class);
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function kategoriKegiatan(): BelongsTo
    {
        return $this->belongsTo(KategoriKegiatan::class, 'kategori_kegiatan_id');
    }

    /**
     * Nama perusahaan lain yang memakai nomor telepon yang sama.
     * Dipakai untuk flag anomali di UI admin.
     *
     * @return array<int, string>
     */
    public function perusahaanLainDenganNomorSama(): array
    {
        if (blank($this->no_telepon)) {
            return [];
        }

        return Perusahaan::query()
            ->whereHas('kontaks', fn (Builder $query): Builder => $query->where('no_telepon', $this->no_telepon))
            ->whereKeyNot($this->perusahaan_id)
            ->pluck('nama_standar')
            ->all();
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
