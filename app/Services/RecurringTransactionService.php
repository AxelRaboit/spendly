<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use Illuminate\Support\Carbon;

class RecurringTransactionService
{
    public function toggle(RecurringTransaction $recurring): void
    {
        $newActive = ! $recurring->active;
        $recurring->update(['active' => $newActive]);

        if ($newActive) {
            $this->generateIfDue($recurring);
        }
    }

    public function generateIfDue(RecurringTransaction $rule): void
    {
        $today = Carbon::today();

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
