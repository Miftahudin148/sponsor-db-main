<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tata letak navigasi panel admin.
 *
 * Kontak adalah item utama pertama setelah Dashboard; Import Data tidak
 * punya entri navigasi sendiri dan diakses dari tombol header fitur Kontak.
 */
class SidebarNavigasiTest extends TestCase
{
    use RefreshDatabase;

    private function nav(string $html): string
    {
        $start = strpos($html, 'fi-sidebar-nav');
        if ($start === false) {
            return '';
        }

        $end = strpos($html, '</nav>', $start);

        return substr($html, $start, ($end === false ? $start + 25000 : $end) - $start);
    }

    /**
     * Posisi offset label item sidebar di dalam markup nav.
     */
    private function posisi(string $nav, string $label): int
    {
        preg_match('/fi-sidebar-item-label"\s*>\s*'.preg_quote($label, '/').'\s*</', $nav, $m, PREG_OFFSET_CAPTURE);

        return $m[0][1] ?? -1;
    }

    #[Test]
    public function kontak_tepat_di_bawah_dashboard_dan_di_atas_master_data(): void
    {
        $user = User::factory()->admin()->create();
        $html = (string) $this->actingAs($user)->get('/admin')->getContent();

        $nav = $this->nav($html);
        $posDashboard = $this->posisi($nav, 'Dashboard');
        $posKontak = $this->posisi($nav, 'Kontak');
        preg_match('/data-group-label="Master Data"/', $nav, $m, PREG_OFFSET_CAPTURE);
        $posMasterData = $m[0][1] ?? -1;

        $this->assertGreaterThan(0, $posDashboard);
        $this->assertGreaterThan($posDashboard, $posKontak, 'Kontak harus tepat di bawah Dashboard.');
        $this->assertGreaterThan($posKontak, $posMasterData, 'Master Data harus di bawah klaster Kontak.');
    }

    #[Test]
    public function import_data_tidak_muncul_di_sidebar_tapi_terhubung_dari_kontak(): void
    {
        $user = User::factory()->admin()->create();
        $html = (string) $this->actingAs($user)->get('/admin/kontaks')->getContent();
        $nav = $this->nav($html);

        // Tidak ada item navigasi Import Data maupun sub-item di sidebar.
        $this->assertSame(-1, $this->posisi($nav, 'Import Data'), 'Import Data tidak boleh jadi item sidebar.');
        $this->assertFalse(strpos($nav, 'fi-sidebar-sub-group-items'), 'Sub-item tidak dirender.');
        $this->assertFalse(strpos($nav, '/admin/import-data'), 'Tautan import tidak ada di sidebar.');

        // Tombol header "Import Data" tersedia di dalam fitur Kontak.
        $this->assertStringContainsString('href="'.url('/admin/import-data').'"', $html);
    }

    #[Test]
    public function lebar_sidebar_kompak_terpasang(): void
    {
        $user = User::factory()->admin()->create();
        $html = (string) $this->actingAs($user)->get('/admin')->getContent();

        $this->assertStringContainsString('--sidebar-width: 16.5rem', $html);
    }

    #[Test]
    public function item_master_data_tidak_hilang(): void
    {
        $user = User::factory()->admin()->create();
        $html = (string) $this->actingAs($user)->get('/admin/perusahaans')->getContent();
        $nav = $this->nav($html);

        foreach (['Perusahaan', 'Kegiatan / Event', 'Kategori Kegiatan'] as $label) {
            $this->assertNotSame(-1, $this->posisi($nav, $label), "{$label} hilang dari sidebar.");
        }
    }
}
