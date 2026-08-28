<?php

namespace Database\Factories;

use App\Models\KategoriKegiatan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KategoriKegiatan>
 */
class KategoriKegiatanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama_kategori' => fake()->unique()->words(3, true),
            'deskripsi' => fake()->sentence(),
        ];
    }
}
