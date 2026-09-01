<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

// app/Models/Divisi.php
class Divisi extends Model
{
    use HasFactory;

    protected $table = 'divisis';

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    protected static function booted(): void
    {
        static::creating(function (Divisi $divisi): void {
            if (empty($divisi->slug)) {
                $divisi->slug = Str::slug($divisi->name);
            }
        });

        static::updating(function (Divisi $divisi): void {
            if ($divisi->isDirty('name')) {
                $divisi->slug = Str::slug($divisi->name);
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'divisi_id');
    }
}
