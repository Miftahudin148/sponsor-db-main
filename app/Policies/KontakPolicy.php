<?php

namespace App\Policies;

use App\Models\Kontak;
use App\Models\User;

class KontakPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('kontak.view_any');
    }

    public function view(User $user, Kontak $kontak): bool
    {
        return $user->can('kontak.view');
    }

    public function create(User $user): bool
    {
        return $user->can('kontak.create');
    }

    public function update(User $user, Kontak $kontak): bool
    {
        return $user->can('kontak.update');
    }

    public function delete(User $user, Kontak $kontak): bool
    {
        return $user->can('kontak.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('kontak.delete');
    }

    public function export(User $user): bool
    {
        return $user->can('kontak.export');
    }

    public function import(User $user): bool
    {
        return $user->can('kontak.import');
    }
}
