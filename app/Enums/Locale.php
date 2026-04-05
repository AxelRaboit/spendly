<?php

declare(strict_types=1);

namespace App\Enums;

enum Locale: string
{
    case Fr = 'fr';
    case En = 'en';
    case Es = 'es';
    case De = 'de';

    public const DEFAULT = self::Fr;

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
