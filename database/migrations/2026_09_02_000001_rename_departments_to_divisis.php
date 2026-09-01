<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// database/migrations/2026_09_02_000001_rename_departments_to_divisis.php
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('departments') && ! Schema::hasTable('divisis')) {
            Schema::rename('departments', 'divisis');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('divisis') && ! Schema::hasTable('departments')) {
            Schema::rename('divisis', 'departments');
        }
    }
};
