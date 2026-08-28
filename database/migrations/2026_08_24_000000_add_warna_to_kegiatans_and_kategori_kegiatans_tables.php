<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kategori_kegiatans', function (Blueprint $table): void {
            $table->string('warna', 7)->nullable()->after('deskripsi');
        });

        Schema::table('kegiatans', function (Blueprint $table): void {
            $table->string('warna', 7)->nullable()->after('nama_event');
        });
    }

    public function down(): void
    {
        Schema::table('kategori_kegiatans', function (Blueprint $table): void {
            $table->dropColumn('warna');
        });

        Schema::table('kegiatans', function (Blueprint $table): void {
            $table->dropColumn('warna');
        });
    }
};
