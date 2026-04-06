<?php

declare(strict_types=1);

namespace App\Enums;

enum WalletRole: string
{
    case Owner = 'owner';
    case Editor = 'editor';
    case Viewer = 'viewer';

    public function canEdit(): bool
    {
        return $this === self::Owner || $this === self::Editor;
    }

    public function canManageMembers(): bool
    {
        return $this === self::Owner;
    }

    public function canDelete(): bool
    {
        return $this === self::Owner;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Roles available for invitations (cannot invite as owner). */
    public static function invitableValues(): array
    {
        return [self::Editor->value, self::Viewer->value];
    }
}
