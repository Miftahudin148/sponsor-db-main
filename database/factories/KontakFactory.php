<?php

namespace Database\Factories;

use App\Models\Kontak;
use App\Models\Perusahaan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kontak>
 */
class KontakFactory extends Factory
{
    public function definition(): array
    {
        return [
            'perusahaan_id' => Perusahaan::factory(),
            'nama' => fake()->name(),
            'no_telepon' => '08'.fake()->numerify('#########'),
            'status_format_valid' => true,
            'status_verifikasi' => 'terverifikasi',
            'updated_by' => null,
        ];
    }
}
