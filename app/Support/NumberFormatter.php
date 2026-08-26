<?php

namespace App\Support;

final class NumberFormatter
{
    public static function money(int|float|string|null $value): string
    {
        $formatted = number_format((float) ($value ?? 0), 2, '.', ',');

        return rtrim(rtrim($formatted, '0'), '.');
    }
}
