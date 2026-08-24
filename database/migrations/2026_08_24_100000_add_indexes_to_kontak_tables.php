<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indeks pendukung sortir/filter/pencarian tabel kontak: SQLite tidak
     * membuat indeks FK secara otomatis, dan kolom no_telepon/nama/
     * status_verifikasi/warna dipakai pada ORDER BY & WHERE yang panas.
     */
    public function up(): void
    {
        Schema::table('kontaks', function (Blueprint $table): void {
            $table->index('perusahaan_id');
            $table->index('kegiatan_id');
            $table->index('kategori_kegiatan_id');
            $table->index('no_telepon');
            $table->index('nama');
            $table->index('status_verifikasi');
        });

        Schema::table('kegiatans', function (Blueprint $table): void {
            $table->index('kategori_kegiatan_id');
            $table->index('warna');
        });

        Schema::table('kategori_kegiatans', function (Blueprint $table): void {
            $table->index('warna');
        });
    }

    public function down(): void
    {
        Schema::table('kontaks', function (Blueprint $table): void {
            foreach (['perusahaan_id', 'kegiatan_id', 'kategori_kegiatan_id', 'no_telepon', 'nama', 'status_verifikasi'] as $kolom) {
                $table->dropIndex([$kolom]);
            }
        });

        Schema::table('kegiatans', function (Blueprint $table): void {
            $table->dropIndex(['kategori_kegiatan_id']);
            $table->dropIndex(['warna']);
        });

        Schema::table('kategori_kegiatans', function (Blueprint $table): void {
            $table->dropIndex(['warna']);
        });
    }
};
