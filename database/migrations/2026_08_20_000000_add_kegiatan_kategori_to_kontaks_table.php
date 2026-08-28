<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kontaks', function (Blueprint $table) {
            $table->foreignId('kegiatan_id')->nullable()->after('perusahaan_id')->constrained('kegiatans')->nullOnDelete();
            $table->foreignId('kategori_kegiatan_id')->nullable()->after('kegiatan_id')->constrained('kategori_kegiatans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kontaks', function (Blueprint $table) {
            $table->dropForeign(['kegiatan_id']);
            $table->dropForeign(['kategori_kegiatan_id']);
            $table->dropColumn(['kegiatan_id', 'kategori_kegiatan_id']);
        });
    }
};
