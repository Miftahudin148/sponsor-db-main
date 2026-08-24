<?php

namespace Tests\Unit;

use App\Support\PhoneNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNormalizerTest extends TestCase
{
    #[DataProvider('normalisasiProvider')]
    public function test_normalisasi_ke_format_628(string $input, string $expected): void
    {
        $this->assertSame($expected, PhoneNormalizer::normalize($input));
    }

    #[DataProvider('keabsahanProvider')]
    public function test_validitas_pola_hp_indonesia(string $input, bool $expected): void
    {
        $this->assertSame($expected, PhoneNormalizer::isValid(PhoneNormalizer::normalize($input)));
    }

    public static function normalisasiProvider(): array
    {
        return [
            '08 tanpa dash' => ['0811-1465-133', '628111465133'],
            '+62 dengan spasi' => ['+62 812-1234-5678', '6281212345678'],
            '62 mulai dengan spasi' => ['62 812-9424-8314', '6281294248314'],
            '6208 ganda nol diperbaiki' => ['6208112345678', '628112345678'],
            '8xx tanpa awalan' => ['81291018454', '6281291018454'],
            'sudah 628 valid tetap' => ['6281111111111', '6281111111111'],
        ];
    }

    public static function keabsahanProvider(): array
    {
        return [
            'HP valid' => ['0811-1465-133', true],
            'HP valid 13 digit' => ['+62 812-8765-4321', true],
            'fixtel 0272' => ['6272038321', false],
            'nomor luar negeri' => ['+1 234 567 890', false],
            'terlalu pendek' => ['08123', false],
            'ada teks sisa' => ['0811 (kantor)', false],
        ];
    }
}