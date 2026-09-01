<?php

namespace Database\Seeders;

use App\Models\Divisi;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach (['admin', 'karyawan'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

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
