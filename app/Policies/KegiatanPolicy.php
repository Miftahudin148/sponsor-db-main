<?php

namespace App\Policies;

use App\Models\Kegiatan;
use App\Models\User;

class KegiatanPolicy
{
    /**
     * Karyawan boleh melihat event (view only), admin penuh.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Kegiatan $kegiatan): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Kegiatan $kegiatan): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Kegiatan $kegiatan): bool
    {
        return $user->isAdmin();
    }
}