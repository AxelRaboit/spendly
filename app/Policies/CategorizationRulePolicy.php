<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CategorizationRule;
use App\Models\User;

class CategorizationRulePolicy
{
    public function update(User $user, CategorizationRule $rule): bool
    {
        return $user->id === $rule->user_id;
    }

    public function delete(User $user, CategorizationRule $rule): bool
    {
        return $user->id === $rule->user_id;
    }
}
