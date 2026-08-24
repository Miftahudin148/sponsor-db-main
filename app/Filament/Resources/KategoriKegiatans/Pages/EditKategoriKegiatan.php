<?php

namespace App\Filament\Resources\KategoriKegiatans\Pages;

use App\Filament\Resources\KategoriKegiatans\KategoriKegiatanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKategoriKegiatan extends EditRecord
{
    protected static string $resource = KategoriKegiatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
