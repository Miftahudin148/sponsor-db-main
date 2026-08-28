<?php

namespace App\Console\Commands;

use App\Models\Kontak;
use Illuminate\Console\Command;

class NormalizeKontakNomor extends Command
{
    protected $signature = 'kontak:normalisasi-nomor';

    protected $description = 'Normalisasi ulang & validasi format nomor semua kontak ke 628xxxxxxxxxx';

    public function handle(): int
    {
        $diubah = 0;

        Kontak::query()
            ->chunkById(500, function ($kontaks) use (&$diubah): void {
                foreach ($kontaks as $kontak) {
                    $kontak->no_telepon = $kontak->no_telepon;

                    if (! $kontak->isDirty(['no_telepon', 'status_format_valid'])) {
                        continue;
                    }

                    $kontak->saveQuietly();
                    $diubah++;
                }
            });

        $this->info(sprintf('Selesai: %d kontak dinormalisasi/diperbarui.', $diubah));

        return self::SUCCESS;
    }
}
