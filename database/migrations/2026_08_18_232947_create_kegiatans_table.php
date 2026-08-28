<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_kegiatan_id')->constrained('kategori_kegiatans')->cascadeOnDelete();
            $table->string('nama_event');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->string('venue')->nullable();
            $table->unsignedInteger('target_jumlah_sponsor')->nullable();
            $table->decimal('target_nominal', 15, 2)->nullable();
            $table->enum('status', ['perencanaan', 'aktif_mencari_sponsor', 'selesai'])->default('perencanaan');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatans');
    }
};
