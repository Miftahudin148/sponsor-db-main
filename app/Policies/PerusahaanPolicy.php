<?php

namespace App\Policies;

use App\Models\Perusahaan;
use App\Models\User;

class PerusahaanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('perusahaan.view_any');
    }

    public function view(User $user, Perusahaan $perusahaan): bool
    {
        return $user->can('perusahaan.view');
    }

    public function create(User $user): bool
    {
        return $user->can('perusahaan.create');
    }

    public function update(User $user, Perusahaan $perusahaan): bool
    {
        return $user->can('perusahaan.update');
    }

    public function delete(User $user, Perusahaan $perusahaan): bool
    {
        return $user->can('perusahaan.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('perusahaan.delete');
    }
}
