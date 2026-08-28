<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kegiatans', function (Blueprint $table) {
            foreach (['target_jumlah_sponsor', 'target_nominal', 'status'] as $kolom) {
                if (Schema::hasColumn('kegiatans', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('kegiatans', function (Blueprint $table) {
            $table->unsignedInteger('target_jumlah_sponsor')->nullable();
            $table->decimal('target_nominal', 15, 2)->nullable();
            $table->enum('status', ['perencanaan', 'aktif_mencari_sponsor', 'selesai'])->default('perencanaan');
        });
    }
};
