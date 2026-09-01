<?php

namespace App\Providers;

use App\Livewire\SanitizeUtf8State;
use App\Models\KategoriKegiatan;
use App\Models\Kegiatan;
use App\Models\Kontak;
use App\Models\Perusahaan;
use App\Models\User;
use App\Policies\ActivityPolicy;
use App\Policies\KategoriKegiatanPolicy;
use App\Policies\KegiatanPolicy;
use App\Policies\KontakPolicy;
use App\Policies\PerusahaanPolicy;
use App\Policies\UserPolicy;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Daftarkan SEBELUM LivewireServiceProvider::boot() memanggil
        // ComponentHookRegistry::boot(). Hook yang didaftarkan di boot()
        // justru muncul SETELAH library membuat listener mount/hydrate/dehydrate
        // sehingga hook TIDAK PERNAH terpasang pada komponen saat runtime.
        Livewire::componentHook(new SanitizeUtf8State);
    }

    public function boot(): void
    {
        FilamentColor::register([
            'success' => Color::hex('#1F8A70'),
            'warning' => Color::hex('#D98E04'),
            'danger' => Color::hex('#C0392B'),
        ]);
        Gate::policy(Activity::class, ActivityPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(KategoriKegiatan::class, KategoriKegiatanPolicy::class);
        Gate::policy(Kegiatan::class, KegiatanPolicy::class);
        Gate::policy(Kontak::class, KontakPolicy::class);
        Gate::policy(Perusahaan::class, PerusahaanPolicy::class);

        // Invalidate cache 60 detik untuk 50 reader — stale max 60s masih aman
        $forgetIcm = fn (): \Closure => function (): void {
            Cache::forget('icm:stats_overview');
            Cache::forget('icm:chart_kategori');
            Cache::forget('icm:chart_top_event');
            Cache::forget('icm:peta_nomor_perusahaan');
        };
        foreach ([Kontak::class, Perusahaan::class, Kegiatan::class, KategoriKegiatan::class] as $model) {
            $model::saved($forgetIcm());
            $model::deleted($forgetIcm());
        }
    }
}
