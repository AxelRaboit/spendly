<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PlanLimitKey;
use App\Exceptions\PlanLimitException;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class RecurringTransactionService
{
    public function __construct(
        private readonly PlanService $planService,
    ) {}

    public function list(User $user): Collection
    {
        return $user->recurringTransactions()->with(['wallet', 'category'])->orderBy('day_of_month')->get();
    }

    public function listScheduled(User $user): Collection
    {
        return $user->scheduledTransactions()->with(['wallet', 'category'])->where('is_generated', false)->orderBy('scheduled_date')->get();
    }

    public function create(User $user, array $data): RecurringTransaction
    {
        if (! $this->planService->canCreateRecurring($user)) {
            throw new PlanLimitException(PlanLimitKey::Recurring);
        }

        /** @var RecurringTransaction $recurring */
        $recurring = $user->recurringTransactions()->create($data);

        return $recurring;
    }

    public function toggle(RecurringTransaction $recurring): void
    {
        $newActive = ! $recurring->active;
        $recurring->update(['active' => $newActive]);

        if ($newActive) {
            $this->generateIfDue($recurring);
        }
    }

    public function update(RecurringTransaction $recurring, array $data): void
    {
        $recurring->update($data);
    }

    public function delete(RecurringTransaction $recurring): void
    {
        $recurring->delete();
    }

    public function generateIfDue(RecurringTransaction $rule): void
    {
        $today = today();

        $alreadyGeneratedThisMonth = $rule->last_generated_at
            && Carbon::parse($rule->last_generated_at)->format('Y-m') === $today->format('Y-m');

        if ($alreadyGeneratedThisMonth || $rule->day_of_month > $today->day) {
            return;
        }

        Transaction::create([
            'user_id' => $rule->user_id,
            'wallet_id' => $rule->wallet_id,
            'category_id' => $rule->category_id,
            'type' => $rule->type,
            'amount' => $rule->amount,
            'description' => $rule->description,
            'date' => $today->toDateString(),
        ]);

        $rule->update(['last_generated_at' => $today->toDateString()]);
    }
}
