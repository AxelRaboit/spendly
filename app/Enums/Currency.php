<?php

declare(strict_types=1);

namespace App\Enums;

enum Currency: string
{
    case EUR = 'EUR';
    case USD = 'USD';
    case GBP = 'GBP';
    case CHF = 'CHF';
    case CAD = 'CAD';
    case JPY = 'JPY';
    case KRW = 'KRW';
    case TWD = 'TWD';
    case CNY = 'CNY';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
