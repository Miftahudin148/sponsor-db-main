<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'karyawan',
            'avatar_url' => null,
            'nip' => null,
            'phone' => null,
            'divisi_id' => null,
            'is_active' => true,
            'joined_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            $role = Role::firstOrCreate(['name' => $user->role, 'guard_name' => 'web']);
            if (! $user->hasRole($user->role)) {
                $user->assignRole($user->role);
            }
            // Seed permissions jika tabel kosong (testing :memory:)
            if (Permission::count() === 0) {
                $perms = ['kontak.view_any', 'kontak.view', 'kontak.create', 'kontak.update', 'kontak.delete', 'kontak.export', 'kontak.import', 'perusahaan.view_any', 'perusahaan.view', 'perusahaan.create', 'perusahaan.update', 'perusahaan.delete', 'kegiatan.view_any', 'kegiatan.view', 'kegiatan.create', 'kegiatan.update', 'kegiatan.delete', 'kategori_kegiatan.view_any', 'kategori_kegiatan.view', 'kategori_kegiatan.create', 'kategori_kegiatan.update', 'kategori_kegiatan.delete'];
                foreach ($perms as $p) {
                    Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
                }
                Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'])->givePermissionTo(Permission::all());
                Role::firstOrCreate(['name' => 'karyawan', 'guard_name' => 'web'])->syncPermissions(['kontak.view_any', 'kontak.view', 'kontak.export', 'perusahaan.view_any', 'perusahaan.view', 'kegiatan.view_any', 'kegiatan.view', 'kategori_kegiatan.view_any', 'kategori_kegiatan.view']);
            }
        });
    }

    public function admin(): static
    {
        return $this->state(['role' => 'admin']);
    }

    public function karyawan(): static
    {
        return $this->state(['role' => 'karyawan']);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
