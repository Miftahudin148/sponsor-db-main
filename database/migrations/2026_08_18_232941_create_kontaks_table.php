<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kontaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perusahaan_id')->constrained()->cascadeOnDelete();
            $table->string('nama');
            $table->string('no_telepon')->nullable();
            $table->boolean('status_format_valid')->default(false);
            $table->enum('status_verifikasi', ['terverifikasi', 'perlu_dicek', 'tidak_aktif'])->default('terverifikasi');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kontaks');
    }
};