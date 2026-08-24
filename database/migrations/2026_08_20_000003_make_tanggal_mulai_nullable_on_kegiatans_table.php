<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kegiatans', function (Blueprint $table) {
            $table->date('tanggal_mulai')->nullable()->change();
            $table->foreignId('kategori_kegiatan_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('kegiatans', function (Blueprint $table) {
            $table->date('tanggal_mulai')->change();
            $table->foreignId('kategori_kegiatan_id')->change();
        });
    }
};
