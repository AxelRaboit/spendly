<?php

declare(strict_types=1);

namespace App\Enums;

enum PlanLimitKey: string
{
    case Wallet = 'wallet';
    case Goal = 'goal';
    case Recurring = 'recurring';
}
