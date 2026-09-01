<?php

namespace App\Policies;

use App\Models\KategoriKegiatan;
use App\Models\User;

class KategoriKegiatanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('kategori_kegiatan.view_any');
    }

    public function view(User $user, KategoriKegiatan $kategoriKegiatan): bool
    {
        return $user->can('kategori_kegiatan.view');
    }

    public function create(User $user): bool
    {
        return $user->can('kategori_kegiatan.create');
    }

    public function update(User $user, KategoriKegiatan $kategoriKegiatan): bool
    {
        return $user->can('kategori_kegiatan.update');
    }

    public function delete(User $user, KategoriKegiatan $kategoriKegiatan): bool
    {
        return $user->can('kategori_kegiatan.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('kategori_kegiatan.delete');
    }
}
