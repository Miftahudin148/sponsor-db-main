<?php

namespace App\Policies;

use App\Models\Kegiatan;
use App\Models\User;

class KegiatanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('kegiatan.view_any');
    }

    public function view(User $user, Kegiatan $kegiatan): bool
    {
        return $user->can('kegiatan.view');
    }

    public function create(User $user): bool
    {
        return $user->can('kegiatan.create');
    }

    public function update(User $user, Kegiatan $kegiatan): bool
    {
        return $user->can('kegiatan.update');
    }

    public function delete(User $user, Kegiatan $kegiatan): bool
    {
        return $user->can('kegiatan.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('kegiatan.delete');
    }
}
