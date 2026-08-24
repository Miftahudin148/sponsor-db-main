<?php

namespace App\Support;

class PhoneNormalizer
{
    /**
     * Normalisasi nomor HP Indonesia ke format polos 628xxxxxxxxxx:
     * buang tanda +, spasi, dash, kurung, titik, dan 0 di depan;
     * perbaiki kode 6208.../620... menjadi 628...
     * (lihat prd.md §4.3 & project-constitution.md §5).
     */
    public static function normalize(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $digits = preg_replace('/\D/', '', $value);

        if (str_starts_with($digits, '620')) {
            $digits = '62'.substr($digits, 3);
        }

        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (! str_starts_with($digits, '62') && str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return $digits;
    }

    /**
     * Pola HP Indonesia yang valid: 628 + 7-10 digit (total 10-13 digit).
     */
    public static function isValid(string $normalized): bool
    {
        return preg_match('/^628\d{7,10}$/', $normalized) === 1;
    }
}