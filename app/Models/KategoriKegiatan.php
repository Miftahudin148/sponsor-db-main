<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class KategoriKegiatan extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'nama_kategori',
        'deskripsi',
        'warna',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama_kategori', 'deskripsi', 'warna'])
            ->logOnlyDirty()
            ->useLogName('kategori_kegiatan')
            ->dontSubmitEmptyLogs();
    }

    public function kegiatans(): HasMany
    {
        return $this->hasMany(Kegiatan::class);
    }
}
