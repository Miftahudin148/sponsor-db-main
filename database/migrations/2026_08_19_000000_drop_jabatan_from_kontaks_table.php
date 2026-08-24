<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('kontaks', 'jabatan')) {
            Schema::table('kontaks', function (Blueprint $table) {
                $table->dropColumn('jabatan');
            });
        }
    }

    public function down(): void
    {
        Schema::table('kontaks', function (Blueprint $table) {
            $table->string('jabatan')->nullable();
        });
    }
};