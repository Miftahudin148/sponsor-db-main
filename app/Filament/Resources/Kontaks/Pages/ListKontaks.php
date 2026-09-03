<?php

namespace App\Filament\Resources\Kontaks\Pages;

use App\Filament\Pages\ImportKontaks;
use App\Filament\Resources\Kontaks\KontakResource;
use App\Models\Kontak;
use App\Services\PetaNomorPerusahaan;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListKontaks extends ListRecords
{
    protected static string $resource = KontakResource::class;

    /**
     * Cache runtime peta nomor telepon lintas perusahaan. Properti protected
     * sehingga TIDAK di-dehydrate Livewire: segar kembali setiap request.
     *
     * @var array<string, array<int, string>>|null
     */
    protected ?array $petaNomorCache = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importData')
                ->label('Import Data')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color('gray')
                ->url(ImportKontaks::getUrl())
                ->visible(fn () => auth()->user()?->can('import', Kontak::class) ?? false),
            CreateAction::make(),
        ];
    }

    /**
     * Peta nomor telepon yang dipakai lebih dari satu perusahaan; dihitung
     * sekali per render (anti N+1 pada recordClasses & kolom admin).
     *
     * @return array<string, array<int, string>>
     */
    public function petaNomorDipakai(): array
    {
        $this->petaNomorCache ??= app(PetaNomorPerusahaan::class)->ambil();

        return $this->petaNomorCache;
    }
}
