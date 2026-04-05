<?php

declare(strict_types=1);

namespace App\Support;

class Text
{
    public static function normalize(string $value): string
    {
        return mb_ucfirst(mb_strtolower(trim($value)));
    }
}
