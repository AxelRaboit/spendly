<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApplicationParameter\SpendlyApplicationParameterEnum;
use App\Enums\PlanType;
use App\Models\User;

class PlanService
{
    public function __construct(private readonly ApplicationParameterService $applicationParameterService) {}

    public function freeWalletLimit(): int
    {
        return $this->applicationParameterService->getInt(
            SpendlyApplicationParameterEnum::WalletLimitFree->value,
            SpendlyApplicationParameterEnum::WalletLimitFree->getIntValue(),
        );
    }

    public function freeGoalLimit(): int
    {
        return $this->applicationParameterService->getInt(
            SpendlyApplicationParameterEnum::GoalLimitFree->value,
            SpendlyApplicationParameterEnum::GoalLimitFree->getIntValue(),
        );
    }

    public function freeRecurringLimit(): int
    {
        return $this->applicationParameterService->getInt(
            SpendlyApplicationParameterEnum::RecurringLimitFree->value,
            SpendlyApplicationParameterEnum::RecurringLimitFree->getIntValue(),
        );
    }

    public function freeStatsMonths(): int
    {
        return $this->applicationParameterService->getInt(
            SpendlyApplicationParameterEnum::StatsMonthsFree->value,
            SpendlyApplicationParameterEnum::StatsMonthsFree->getIntValue(),
        );
    }

    public function proStatsMonths(): int
    {
        return $this->applicationParameterService->getInt(
            SpendlyApplicationParameterEnum::StatsMonthsPro->value,
            SpendlyApplicationParameterEnum::StatsMonthsPro->getIntValue(),
        );
    }

    public function freeTransactionHistoryDays(): int
    {
        return $this->applicationParameterService->getInt(
            SpendlyApplicationParameterEnum::TransactionHistoryDaysFree->value,
            SpendlyApplicationParameterEnum::TransactionHistoryDaysFree->getIntValue(),
        );
    }

    public function proPrice(): float
    {
        return $this->applicationParameterService->getFloat(
            SpendlyApplicationParameterEnum::ProPrice->value,
            SpendlyApplicationParameterEnum::ProPrice->getFloatValue(),
        );
    }

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

        return $user->wallets()->where('is_demo', false)->count() < $this->freeWalletLimit();
    }

    public function canCreateGoal(User $user): bool
    {
        if ($this->isPro($user)) {
            return true;
        }

        return $user->goals()->count() < $this->freeGoalLimit();
    }

    public function canCreateRecurring(User $user): bool
    {
        if ($this->isPro($user)) {
            return true;
        }

        return $user->recurringTransactions()->count() < $this->freeRecurringLimit();
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
        return $this->isPro($user) ? $this->proStatsMonths() : $this->freeStatsMonths();
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
