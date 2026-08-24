<?php

namespace Database\Factories;

use App\Models\KategoriKegiatan;
use App\Models\Kegiatan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kegiatan>
 */
class KegiatanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kategori_kegiatan_id' => KategoriKegiatan::factory(),
            'nama_event' => fake()->unique()->words(3, true),
            'tanggal_mulai' => fake()->dateTimeBetween('now', '+6 months')->format('Y-m-d'),
            'tanggal_selesai' => fn (array $a) => \Illuminate\Support\Carbon::parse($a['tanggal_mulai'])->addDays(rand(1, 3))->format('Y-m-d'),
            'venue' => fake()->company(),
            'catatan' => null,
        ];
    }
}