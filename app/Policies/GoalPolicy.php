<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Goal;
use App\Models\User;
use App\Models\Wallet;

class GoalPolicy
{
    public function update(User $user, Goal $goal): bool
    {
        if ($user->id === $goal->user_id) {
            return true;
        }

        return $goal->wallet_id
            && (Wallet::find($goal->wallet_id)?->roleFor($user)?->canEdit() ?? false);
    }

    public function delete(User $user, Goal $goal): bool
    {
        if ($user->id === $goal->user_id) {
            return true;
        }

        return $goal->wallet_id
            && (Wallet::find($goal->wallet_id)?->roleFor($user)?->canEdit() ?? false);
    }
}
