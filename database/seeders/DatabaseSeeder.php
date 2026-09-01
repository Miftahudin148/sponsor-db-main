<?php

namespace Database\Seeders;

use App\Models\Divisi;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach (['admin', 'karyawan'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        $perms = [
            'kontak.view_any', 'kontak.view', 'kontak.create', 'kontak.update', 'kontak.delete', 'kontak.export', 'kontak.import',
            'perusahaan.view_any', 'perusahaan.view', 'perusahaan.create', 'perusahaan.update', 'perusahaan.delete',
            'kegiatan.view_any', 'kegiatan.view', 'kegiatan.create', 'kegiatan.update', 'kegiatan.delete',
            'kategori_kegiatan.view_any', 'kategori_kegiatan.view', 'kategori_kegiatan.create', 'kategori_kegiatan.update', 'kategori_kegiatan.delete',
        ];
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // Admin = semua, Karyawan default hanya baca + export
        Role::findByName('admin')->givePermissionTo(Permission::all());
        $karyawanDefaults = ['kontak.view_any', 'kontak.view', 'kontak.export', 'perusahaan.view_any', 'perusahaan.view', 'kegiatan.view_any', 'kegiatan.view', 'kategori_kegiatan.view_any', 'kategori_kegiatan.view'];
        Role::findByName('karyawan')->syncPermissions($karyawanDefaults);

        Divisi::firstOrCreate(['name' => 'Umum'], ['slug' => Str::slug('Umum'), 'description' => 'Divisi Umum']);

        $admin = User::factory()->create([
            'name' => 'Admin ICM',
            'email' => 'admin@icm.test',
            'password' => 'password123',
            'role' => 'admin',
            'divisi_id' => Divisi::where('name', 'Umum')->first()->id,
        ]);
        $admin->assignRole('admin');

        $karyawan = User::factory()->create([
            'name' => 'Karyawan Contoh',
            'email' => 'karyawan@icm.test',
            'password' => 'password123',
            'role' => 'karyawan',
            'divisi_id' => Divisi::where('name', 'Umum')->first()->id,
        ]);
        $karyawan->assignRole('karyawan');

        $this->call(MasterDataSeeder::class);
    }
}
