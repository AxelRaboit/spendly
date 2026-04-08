<?php

declare(strict_types=1);

namespace App\Enums;

enum WalletMode: string
{
    case Budget = 'budget';
    case Simple = 'simple';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
