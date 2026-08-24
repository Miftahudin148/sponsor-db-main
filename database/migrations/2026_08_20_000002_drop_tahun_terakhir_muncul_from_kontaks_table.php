<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('kontaks', 'tahun_terakhir_muncul')) {
            Schema::table('kontaks', function (Blueprint $table) {
                $table->dropColumn('tahun_terakhir_muncul');
            });
        }
    }

    public function down(): void
    {
        Schema::table('kontaks', function (Blueprint $table) {
            $table->year('tahun_terakhir_muncul')->nullable();
        });
    }
};