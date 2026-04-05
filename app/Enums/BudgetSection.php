<?php

declare(strict_types=1);

namespace App\Enums;

enum BudgetSection: string
{
    case Income = 'income';
    case Savings = 'savings';
    case Bills = 'bills';
    case Expenses = 'expenses';
    case Debt = 'debt';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
