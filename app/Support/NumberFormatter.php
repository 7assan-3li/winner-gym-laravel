<?php

namespace App\Support;

final class NumberFormatter
{
    public static function money(int|float|string|null $value): string
    {
        $clean = is_string($value) ? str_replace([',', ' '], '', $value) : $value;
        $formatted = number_format((float) ($clean ?? 0), 2, '.', ',');

        return rtrim(rtrim($formatted, '0'), '.');
    }

    public static function clean(int|float|string|null $value): string
    {
        if ($value === null) {
            return '';
        }

        return str_replace([',', ' '], '', (string) $value);
    }

    public static function unformat(int|float|string|null $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $clean = str_replace([',', ' '], '', (string) $value);

        return is_numeric($clean) ? (float) $clean : 0.0;
    }
}
