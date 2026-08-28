<?php

namespace App\Filament\Resources\Perusahaans\Pages;

use App\Filament\Resources\Perusahaans\PerusahaanResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePerusahaan extends CreateRecord
{
    protected static string $resource = PerusahaanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
