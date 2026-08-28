<?php

namespace App\Policies;

use App\Models\Perusahaan;
use App\Models\User;

class PerusahaanPolicy
{
    /**
     * Karyawan setara admin, kecuali tidak boleh menghapus (design.md §4).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Perusahaan $perusahaan): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Perusahaan $perusahaan): bool
    {
        return true;
    }

    public function delete(User $user, Perusahaan $perusahaan): bool
    {
        return $user->isAdmin();
    }
}
