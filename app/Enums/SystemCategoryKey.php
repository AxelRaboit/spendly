<?php

declare(strict_types=1);

namespace App\Enums;

enum SystemCategoryKey: string
{
    case TransferIncome = 'transfer_income';

    public static function transferExpenseKey(int $toWalletId): string
    {
        return 'transfer_expense_'.$toWalletId;
    }

    public static function isTransferExpenseKey(string $key): bool
    {
        return str_starts_with($key, 'transfer_expense_');
    }
}
