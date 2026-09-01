<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Perusahaan extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'nama_standar',
        'industri',
        'catatan',
        'updated_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama_standar', 'industri', 'catatan'])
            ->logOnlyDirty()
            ->useLogName('perusahaan')
            ->dontSubmitEmptyLogs();
    }

    public function kontaks(): HasMany
    {
        return $this->hasMany(Kontak::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
