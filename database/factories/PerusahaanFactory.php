<?php

namespace Database\Factories;

use App\Models\Perusahaan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Perusahaan>
 */
class PerusahaanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama_standar' => fake()->unique()->company(),
            'industri' => fake()->randomElement(['Farmasi', 'Alat Kesehatan', 'Rumah Sakit', 'Hospitality', 'FMCG']),
            'catatan' => null,
            'updated_by' => null,
        ];
    }
}
