<?php

namespace App\Policies;

use App\Models\Kontak;
use App\Models\User;

class KontakPolicy
{
    /**
     * Karyawan setara admin untuk Kontak.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Kontak $kontak): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Kontak $kontak): bool
    {
        return true;
    }

    public function delete(User $user, Kontak $kontak): bool
    {
        return true;
    }
}
