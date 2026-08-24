<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Widget Filament bersifat lazy default. Livewire v4.4.x memproses panggilan
 * __lazyLoad hanya saat komponen masih dalam fase hydrate lazy (memo
 * lazyLoaded=false). Race di sisi klien (x-intersect terpicu ulang sewaktu
 * respons pertama masih berjalan) dapat mengirim panggilan __lazyLoad KEDUA
 * dengan memo lazyLoaded=true; tanpa pengaman, request update berakhir 500
 * MethodNotFoundException. Hook SanitizeUtf8State::call() harus menahan
 * dispatch duplikat tersebut.
 */
class LazyWidgetDoubleLoadTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function panggilan_lazyload_kedua_tidak_melempar_method_not_found(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $html = $this->get('/admin')->assertOk()->getContent();

        preg_match(
            '/wire:snapshot="(?<snap>[^"]+)"[^>]*?wire:id="(?<id>[^"]+)"[^>]*?wire:name="(?<name>[^"]+)"[^>]*?x-intersect="\$wire\.__lazyLoad\(&#039;(?<enc>[^&]+)&#039;\)"/',
            $html,
            $hit,
        );

        $this->assertNotEmpty($hit, 'Widget lazy (placeholder) tidak ditemukan di halaman dashboard.');
        $this->assertStringContainsString('App\Filament\Widgets', $hit['name']);

        $snapshot = json_decode(html_entity_decode($hit['snap']), true);
        $encoded = $hit['enc'];

        $this->assertFalse(data_get($snapshot, 'memo.lazyLoaded'), 'Placeholder harus berstatus lazyLoaded=false.');

        // Pemanggilan pertama (sah): komponen di-mount dari placeholdernya.
        [$loaded, $effects] = app('livewire')->update(
            $snapshot,
            [],
            [['method' => '__lazyLoad', 'params' => [$encoded]]],
        );

        $this->assertTrue(data_get($loaded, 'memo.lazyLoaded'), 'Setelah load pertama, memo harus lazyLoaded=true.');

        // Pemanggilan kedua (duplikat, dari snapshot baru yang sudah termuat):
        // harus ditahan hook, bukan melempar MethodNotFoundException. Hasil
        // berupa update normal komponen yang sudah termuat (tanpa key lazyLoaded).
        [$again, $effects2] = app('livewire')->update(
            $loaded,
            [],
            [['method' => '__lazyLoad', 'params' => [$encoded]]],
        );

        $this->assertSame($loaded['memo']['id'], $again['memo']['id'], 'Identitas komponen tidak berubah.');
        $this->assertNotEmpty($effects2['html'] ?? null, 'Komponen tetap re-render sebagai widget yang dimuat.');
    }
}