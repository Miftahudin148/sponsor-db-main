<?php

namespace Tests\Feature;

use App\Filament\Widgets\KontakStatsOverview;
use App\Filament\Widgets\StatusVerifikasiChart;
use App\Models\Kegiatan;
use App\Models\Kontak;
use App\Models\Perusahaan;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class DashboardWidgetTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function halaman_dashboard_renders_untuk_admin_dan_karyawan(): void
    {
        $admin = User::factory()->admin()->create();
        $karyawan = User::factory()->karyawan()->create();

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($karyawan)->get('/admin')->assertOk();
    }

    #[Test]
    public function widget_dashboard_tersedia_dan_ditemukan_otomatis(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $widgets = array_map(
            fn ($widget): string => is_string($widget) ? $widget : get_class($widget),
            Filament::getWidgets()
        );

        $this->assertContains(KontakStatsOverview::class, $widgets);
        $this->assertContains(StatusVerifikasiChart::class, $widgets);
    }

    #[Test]
    public function stat_cards_menampilkan_angka_yang_benar(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $perusahaan = Perusahaan::factory()->create();
        Perusahaan::factory()->count(3)->create();
        Kontak::factory()->count(2)->create(['perusahaan_id' => $perusahaan->id]);
        Kontak::factory()->create(['perusahaan_id' => $perusahaan->id, 'status_verifikasi' => 'perlu_dicek']);
        Kegiatan::factory()->count(2)->create();

        $widget = new KontakStatsOverview();
        $getStats = new ReflectionMethod($widget, 'getStats');
        $stats = $getStats->invoke($widget);

        $this->assertSame('4', (string) $stats[0]->getValue());
        $this->assertSame('3', (string) $stats[1]->getValue());
        $this->assertSame('2', (string) $stats[2]->getValue());
        $this->assertSame('1', (string) $stats[3]->getValue());
        $this->assertSame('2', (string) $stats[4]->getValue());

        Livewire::test(KontakStatsOverview::class)->assertOk();
    }

    #[Test]
    public function status_verifikasi_chart_mengelompokkan_per_status(): void
    {
        Kontak::factory()->create(['status_verifikasi' => 'terverifikasi']);
        Kontak::factory()->count(2)->create(['status_verifikasi' => 'perlu_dicek']);
        Kontak::factory()->count(3)->create(['status_verifikasi' => 'tidak_aktif']);

        $widget = new StatusVerifikasiChart();
        $getData = new ReflectionMethod($widget, 'getData');
        $data = $getData->invoke($widget);

        $this->assertSame(['terverifikasi', 'perlu_dicek', 'tidak_aktif'], $data['labels']);
        $this->assertSame([1, 2, 3], $data['datasets'][0]['data']);

        Livewire::test(StatusVerifikasiChart::class)->assertOk();
    }
}