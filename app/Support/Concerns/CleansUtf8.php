<?php

declare(strict_types=1);

namespace App\Support\Concerns;

// app/Support/Concerns/CleansUtf8.php
trait CleansUtf8
{
    protected function cleanUtf8(string $value): string
    {
        if ($value === '' || mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }
        foreach (['Windows-1252', 'ISO-8859-1'] as $encoding) {
            $converted = @mb_convert_encoding($value, 'UTF-8', $encoding);
            if (is_string($converted) && mb_check_encoding($converted, 'UTF-8')) {
                return $converted;
            }
        }

        $stripped = preg_replace('/[^\x09\x0A\x0D\x20-\x7E\x80-\xFF]/', '', $value);

        return is_string($stripped) && mb_check_encoding($stripped, 'UTF-8') ? $stripped : '';
    }
}
