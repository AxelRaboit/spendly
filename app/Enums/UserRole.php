<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case User = 'ROLE_USER';
    case Dev = 'ROLE_DEV';

    public function label(): string
    {
        return match ($this) {
            self::User => __('enums.user_role.user'),
            self::Dev => __('enums.user_role.dev'),
        };
    }
}
