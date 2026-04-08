<?php

declare(strict_types=1);

namespace App\Enums;

enum PolicyAction: string
{
    case View = 'view';
    case Update = 'update';
    case Delete = 'delete';
    case ManageMembers = 'manageMembers';
}
