<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin ICM',
            'email' => 'admin@icm.test',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Karyawan Contoh',
            'email' => 'karyawan@icm.test',
            'password' => 'password123',
            'role' => 'karyawan',
        ]);

        $this->call(MasterDataSeeder::class);
    }
}
