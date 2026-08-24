<?php

namespace App\Filament\Resources\KategoriKegiatans\Pages;

use App\Filament\Resources\KategoriKegiatans\KategoriKegiatanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKategoriKegiatans extends ListRecords
{
    protected static string $resource = KategoriKegiatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
