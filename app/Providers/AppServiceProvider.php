<?php

namespace App\Providers;

use App\Livewire\SanitizeUtf8State;
use App\Models\KategoriKegiatan;
use App\Models\Kegiatan;
use App\Models\Kontak;
use App\Models\Perusahaan;
use App\Models\User;
use App\Policies\KategoriKegiatanPolicy;
use App\Policies\KegiatanPolicy;
use App\Policies\KontakPolicy;
use App\Policies\PerusahaanPolicy;
use App\Policies\UserPolicy;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(KategoriKegiatan::class, KategoriKegiatanPolicy::class);
        Gate::policy(Kegiatan::class, KegiatanPolicy::class);
        Gate::policy(Kontak::class, KontakPolicy::class);
        Gate::policy(Perusahaan::class, PerusahaanPolicy::class);
    }
}
