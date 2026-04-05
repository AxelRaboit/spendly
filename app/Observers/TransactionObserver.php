<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Goal;
use App\Models\Transaction;

class TransactionObserver
{
    public function saved(Transaction $transaction): void
    {
        $this->syncGoal((int) $transaction->user_id, $transaction->category_id ? (int) $transaction->category_id : null);

        if ($transaction->wasChanged('category_id') && $transaction->getOriginal('category_id')) {
            $this->syncGoal((int) $transaction->user_id, (int) $transaction->getOriginal('category_id'));
        }
    }

    public function deleted(Transaction $transaction): void
    {
        $this->syncGoal((int) $transaction->user_id, $transaction->category_id ? (int) $transaction->category_id : null);
    }

    private function syncGoal(int $userId, ?int $categoryId): void
    {
        if (! $categoryId) {
            return;
        }

        $goal = Goal::where('user_id', $userId)
            ->where('category_id', $categoryId)
            ->first();

        if (! $goal) {
            return;
        }

        $total = Transaction::where('user_id', $userId)
            ->where('category_id', $categoryId)
            ->sum('amount');

        $goal->updateQuietly(['saved_amount' => $total]);
    }
}
