<?php

declare(strict_types=1);

namespace App\Enums;

enum AllowedMime: string
{
    case Jpeg = 'image/jpeg';
    case Png = 'image/png';
    case Webp = 'image/webp';
    case Pdf = 'application/pdf';

    /** Extensions accepted by the 'mimes' validation rule. */
    public static function imageExtensions(): string
    {
        return 'jpg,jpeg,png,webp';
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
