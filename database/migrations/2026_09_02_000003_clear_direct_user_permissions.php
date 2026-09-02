<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

// database/migrations/2026_09_02_000003_clear_direct_user_permissions.php
return new class extends Migration
{
    public function up(): void
    {
        // Revert per-karyawan ke per-role: hapus hak langsung per-user, hanya via role
        if (DB::getSchemaBuilder()->hasTable('model_has_permissions')) {
            DB::table('model_has_permissions')->where('model_type', 'App\\Models\\User')->delete();
        }
        Cache::forget(config('permission.cache.key', 'spatie.permission.cache'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // tidak ada rollback
    }
};
