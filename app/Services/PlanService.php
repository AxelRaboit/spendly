<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PlanType;
use App\Models\User;

class PlanService
{
    public const FREE_WALLET_LIMIT = 1;

    public const FREE_GOAL_LIMIT = 2;

    public const FREE_RECURRING_LIMIT = 5;

    public const FREE_STATS_MONTHS = 1;

    public const FREE_TRANSACTION_HISTORY_DAYS = 90;

    public function isPro(User $user): bool
    {
        /** @var PlanType $plan */
        $plan = $user->plan;

        if ($plan === PlanType::Pro && $user->trial_ends_at !== null && $user->trial_ends_at->isPast()) {
            $user->update(['plan' => PlanType::Free, 'trial_ends_at' => null]);

            return false;
        }

        return $plan === PlanType::Pro;
    }

    public function isFree(User $user): bool
    {
        /** @var PlanType $plan */
        $plan = $user->plan;

        return $plan === PlanType::Free;
    }

    public function canCreateWallet(User $user): bool
    {
        if ($this->isPro($user)) {
            return true;
        }

        return $user->wallets()->count() < self::FREE_WALLET_LIMIT;
    }

    public function canCreateGoal(User $user): bool
    {
        if ($this->isPro($user)) {
            return true;
        }

        return $user->goals()->count() < self::FREE_GOAL_LIMIT;
    }

    public function canCreateRecurring(User $user): bool
    {
        if ($this->isPro($user)) {
            return true;
        }

        return $user->recurringTransactions()->count() < self::FREE_RECURRING_LIMIT;
    }

    public function canEditBudget(User $user): bool
    {
        return $this->isPro($user);
    }

    public function canExportImport(User $user): bool
    {
        return $this->isPro($user);
    }

    public function statsMonthLimit(User $user): int
    {
        return $this->isPro($user) ? 6 : self::FREE_STATS_MONTHS;
    }

    public function isTrialing(User $user): bool
    {
        return $user->trial_ends_at !== null && $user->trial_ends_at->isFuture();
    }

    public function upgrade(User $user): void
    {
        $user->update(['plan' => PlanType::Pro, 'trial_ends_at' => null]);
    }

    public function downgrade(User $user): void
    {
        $user->update(['plan' => PlanType::Free, 'trial_ends_at' => null]);
    }
}
