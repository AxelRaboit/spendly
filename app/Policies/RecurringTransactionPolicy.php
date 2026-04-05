<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RecurringTransaction;
use App\Models\User;

class RecurringTransactionPolicy
{
    public function update(User $user, RecurringTransaction $rt): bool
    {
        return $user->id === $rt->user_id;
    }

    public function delete(User $user, RecurringTransaction $rt): bool
    {
        return $user->id === $rt->user_id;
    }
}
