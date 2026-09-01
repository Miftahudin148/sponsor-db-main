<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Kegiatan extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'kategori_kegiatan_id',
        'nama_event',
        'warna',
        'tanggal_mulai',
        'tanggal_selesai',
        'venue',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['kategori_kegiatan_id', 'nama_event', 'warna', 'venue'])
            ->logOnlyDirty()
            ->useLogName('kegiatan')
            ->dontSubmitEmptyLogs();
    }

    public function kategoriKegiatan(): BelongsTo
    {
        return $this->belongsTo(KategoriKegiatan::class, 'kategori_kegiatan_id');
    }

    public function kontaks(): HasMany
    {
        return $this->hasMany(Kontak::class);
    }
}
