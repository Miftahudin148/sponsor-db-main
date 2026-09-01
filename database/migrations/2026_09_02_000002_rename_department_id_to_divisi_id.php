<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// database/migrations/2026_09_02_000002_rename_department_id_to_divisi_id.php
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'department_id') && ! Schema::hasColumn('users', 'divisi_id')) {
            Schema::table('users', function (Blueprint $table) {
                // Drop FK sebelum rename (nama FK berbeda di sqlite/mysql)
                try {
                    $table->dropForeign(['department_id']);
                } catch (Throwable $e) {
                }
            });
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('department_id', 'divisi_id');
            });
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('divisi_id')->references('id')->on('divisis')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'divisi_id') && ! Schema::hasColumn('users', 'department_id')) {
            Schema::table('users', function (Blueprint $table) {
                try {
                    $table->dropForeign(['divisi_id']);
                } catch (Throwable $e) {
                }
            });
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('divisi_id', 'department_id');
            });
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            });
        }
    }
};
