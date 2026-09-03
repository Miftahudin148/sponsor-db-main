<?php

namespace App\Livewire;

use App\Support\Concerns\CleansUtf8;
use Livewire\ComponentHook;
use Livewire\Mechanisms\HandleComponents\ComponentContext;
use ReflectionObject;
use ReflectionProperty;

/**
 * Pengaman global: jamin semua properti publik komponen Livewire berisi
 * UTF-8 yang valid saat dehydrate, sehingga json_encode snapshot tidak
 * pernah gagal karena byte korup dari file/dataset impor yang lama.
 */
class SanitizeUtf8State extends ComponentHook
{
    use CleansUtf8;

    public function dehydrate(?ComponentContext $context = null): void
    {
        $reflection = new ReflectionObject($this->component);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();
            $value = $this->component->{$name} ?? null;

            if (is_string($value)) {
                $clean = $this->cleanUtf8($value);
                if ($clean !== $value) {
                    $this->component->{$name} = $clean;
                }
            } elseif (is_array($value)) {
                $clean = $this->sanitizeArray($value);
                if ($clean !== $value) {
                    $this->component->{$name} = $clean;
                }
            }
        }

        // HTML hasil render (effects) dibuat SEBELUM hook ini berjalan;
        // bersihkan juga agar json_encode seluruh respons tidak pernah gagal.
        if ($context && is_array($context->effects)) {
            $clean = $this->sanitizeArray($context->effects);
            if ($clean !== $context->effects) {
                $context->effects = $clean;
            }
        }
    }

    /**
     * Tahan panggilan __lazyLoad ganda dari sisi klien.
     *
     * Livewire v4.4.x hanya memproses __lazyLoad saat komponen sedang dalam
     * fase hydrate lazy (isLazyLoadHydrating = true, memo lazyLoaded=false).
     * Saat widget LAZY sudah ter-load, race di klien (mis. x-intersect yang
     * terpicu ulang sewaktu respons pertama masih diproses) dapat mengirim
     * panggilan __lazyLoad KEDUA dengan memo lazyLoaded=true. SupportLazyLoading
     * mengabaikan kasus itu lalu method __lazyLoad tidak ditemukan pada komponen
     * sehingga seluruh request update berakhir 500 (MethodNotFoundException).
     * Kita hentikan dispatch duplikat ini agar tidak membawa error tersebut.
     */
    public function call($method, $params, $returnEarly, $metadata = [], $componentContext = null): void
    {
        if ($method === '__lazyLoad' && $this->storeGet('isLazyLoadHydrating') !== true) {
            $returnEarly();
        }
    }

    protected function sanitizeArray(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_string($item)) {
                $clean = $this->cleanUtf8($item);
                if ($clean !== $item) {
                    $value[$key] = $clean;
                }
            } elseif (is_array($item)) {
                $value[$key] = $this->sanitizeArray($item);
            }
        }

        return $value;
    }
}
