<?php

namespace Tests\Feature;

use App\Filament\Widgets\KontakKategoriBarChart;
use App\Filament\Widgets\KontakStatsOverview;
use App\Filament\Widgets\TopEventBarChart;
use App\Models\KategoriKegiatan;
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
        $this->assertContains(KontakKategoriBarChart::class, $widgets);
        $this->assertContains(TopEventBarChart::class, $widgets);
        $this->assertNotContains('App\Filament\Widgets\StatusVerifikasiChart', $widgets);
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

        $widget = new KontakStatsOverview;
        $getStats = new ReflectionMethod($widget, 'getStats');
        $stats = $getStats->invoke($widget);

        // Balok Ringkasan KPI — cari by label agar tahan terhadap urutan/tambahan stat
        $byLabel = [];
        foreach ($stats as $stat) {
            $byLabel[$stat->getLabel()] = (string) $stat->getValue();
        }

        $this->assertSame('4', $byLabel['Total Perusahaan']);
        $this->assertSame('3', $byLabel['Total Kontak']);
        $this->assertSame('2', $byLabel['Terverifikasi']);
        $this->assertSame('1', $byLabel['Perlu Dicek']);
        $this->assertSame('2', $byLabel['Total Kegiatan']);

        Livewire::test(KontakStatsOverview::class)->assertOk();
    }

    #[Test]
    public function horizontal_bar_chart_distribusi_event_per_kategori(): void
    {
        $katA = KategoriKegiatan::factory()->create(['nama_kategori' => 'Dokter Umum & Estetik']);
        $katB = KategoriKegiatan::factory()->create(['nama_kategori' => 'Gizi Klinik']);
        // KatA: 2 event, KatB: 1 event
        Kegiatan::factory()->create(['kategori_kegiatan_id' => $katA->id]);
        Kegiatan::factory()->create(['kategori_kegiatan_id' => $katA->id]);
        Kegiatan::factory()->create(['kategori_kegiatan_id' => $katB->id]);

        $widget = new KontakKategoriBarChart;
        $getData = new ReflectionMethod($widget, 'getData');
        $data = $getData->invoke($widget);
        $options = (new ReflectionMethod($widget, 'getOptions'))->invoke($widget);

        $this->assertSame('bar', (new ReflectionMethod($widget, 'getType'))->invoke($widget));
        $this->assertSame('y', $options['indexAxis']);
        $this->assertContains('Dokter Umum & Estetik', $data['labels']);
        $this->assertContains('Gizi Klinik', $data['labels']);
        // KatA memiliki 2 event, KatB 1
        $idxA = array_search('Dokter Umum & Estetik', $data['labels'], true);
        $idxB = array_search('Gizi Klinik', $data['labels'], true);
        $this->assertSame(2, $data['datasets'][0]['data'][$idxA]);
        $this->assertSame(1, $data['datasets'][0]['data'][$idxB]);
        $this->assertCount(count($data['labels']), $data['datasets'][0]['backgroundColor']);

        Livewire::test(KontakKategoriBarChart::class)->assertOk();
    }

    #[Test]
    public function vertical_bar_chart_top5_event_terbesar(): void
    {
        $events = collect(range(1, 6))->map(fn ($i) => Kegiatan::factory()->create(['nama_event' => 'Event '.$i]))->all();
        $perusahaan = Perusahaan::factory()->create();
        // Event1: 5 kontak, Event2: 3, Event3: 4, Event4: 2, Event5: 1, Event6: 0 -> Top5 harus exclude Event6
        foreach ([5, 3, 4, 2, 1, 0] as $idx => $count) {
            Kontak::factory()->count($count)->create(['perusahaan_id' => $perusahaan->id, 'kegiatan_id' => $events[$idx]->id]);
        }

        $widget = new TopEventBarChart;
        $getData = new ReflectionMethod($widget, 'getData');
        $data = $getData->invoke($widget);
        $options = (new ReflectionMethod($widget, 'getOptions'))->invoke($widget);

        $this->assertSame('bar', (new ReflectionMethod($widget, 'getType'))->invoke($widget));
        $this->assertSame('x', $options['indexAxis']);
        $this->assertCount(5, $data['labels']);
        $this->assertCount(5, $data['datasets'][0]['data']);
        // Data terurut desc: 5,4,3,2,1
        $this->assertSame([5, 4, 3, 2, 1], $data['datasets'][0]['data']);
        $this->assertCount(5, $data['datasets'][0]['backgroundColor']);

        Livewire::test(TopEventBarChart::class)->assertOk();
    }
}
