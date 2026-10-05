<?php

namespace App\Support;

final class Money
{
    public static function add(string $a, string $b): string
    {
        return bcadd($a, $b, 2);
    }

    public static function sub(string $a, string $b): string
    {
        return bcsub($a, $b, 2);
    }

    public static function mul(string $a, int $b): string
    {
        return bcmul($a, (string) $b, 2);
    }

    public static function format($value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}
