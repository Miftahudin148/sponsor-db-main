<?php

namespace Tests\Feature;

use App\Models\KategoriKegiatan;
use App\Models\Kegiatan;
use App\Models\Kontak;
use App\Models\Perusahaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dan_karyawan_akses_kontak_dan_perusahaan(): void
    {
        $admin = User::factory()->admin()->create();
        $karyawan = User::factory()->karyawan()->create();

        $this->actingAs($admin);
        $this->assertTrue(auth()->user()->can('viewAny', Kontak::class));
        $this->assertTrue(auth()->user()->can('create', Kontak::class));
        $this->assertTrue(auth()->user()->can('create', Perusahaan::class));

        $this->actingAs($karyawan);
        $this->assertTrue(auth()->user()->can('viewAny', Kontak::class));
        $this->assertTrue(auth()->user()->can('create', Kontak::class));
        $this->assertTrue(auth()->user()->can('create', Perusahaan::class));
    }

    public function test_karyawan_tidak_bisa_hapus_perusahaan(): void
    {
        $karyawan = User::factory()->karyawan()->create();
        $perusahaan = Perusahaan::factory()->create();

        $this->actingAs($karyawan);
        $this->assertFalse(auth()->user()->can('delete', $perusahaan));

        $this->actingAs(User::factory()->admin()->create());
        $this->assertTrue(auth()->user()->can('delete', $perusahaan));
    }

    public function test_kategori_kegiatan_hanya_admin(): void
    {
        $kategori = KategoriKegiatan::factory()->create();

        $this->actingAs(User::factory()->karyawan()->create());
        $this->assertFalse(auth()->user()->can('viewAny', KategoriKegiatan::class));
        $this->assertFalse(auth()->user()->can('create', KategoriKegiatan::class));

        $this->actingAs(User::factory()->admin()->create());
        $this->assertTrue(auth()->user()->can('viewAny', KategoriKegiatan::class));
        $this->assertTrue(auth()->user()->can('view', $kategori));
    }

    public function test_kegiatan_karyawan_hanya_lihat_admin_bisa_tulis(): void
    {
        $kegiatan = Kegiatan::factory()->create();

        $this->actingAs(User::factory()->karyawan()->create());
        $this->assertTrue(auth()->user()->can('viewAny', Kegiatan::class));
        $this->assertTrue(auth()->user()->can('view', $kegiatan));
        $this->assertFalse(auth()->user()->can('create', Kegiatan::class));
        $this->assertFalse(auth()->user()->can('update', $kegiatan));
        $this->assertFalse(auth()->user()->can('delete', $kegiatan));

        $this->actingAs(User::factory()->admin()->create());
        $this->assertTrue(auth()->user()->can('create', Kegiatan::class));
        $this->assertTrue(auth()->user()->can('update', $kegiatan));
    }

    public function test_user_dan_kategori_hanya_admin(): void
    {
        $target = User::factory()->create();

        $this->actingAs(User::factory()->karyawan()->create());
        $this->assertFalse(auth()->user()->can('viewAny', User::class));
        $this->assertFalse(auth()->user()->can('view', $target));

        $this->actingAs(User::factory()->admin()->create());
        $this->assertTrue(auth()->user()->can('viewAny', User::class));
    }
}