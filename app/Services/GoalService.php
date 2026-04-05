<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Goal;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Collection;

class GoalService
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {}

    public function list(User $user): Collection
    {
        return $user->goals()
            ->with(['wallet', 'category'])
            ->orderByRaw('saved_amount < target_amount DESC')
            ->orderBy('deadline')
            ->get()
            ->append('progress');
    }

    public function listForWallet(User $user, Wallet $wallet): Collection
    {
        return $user->goals()
            ->where('wallet_id', $wallet->id)
            ->get()
            ->append('progress');
    }

    /**
     * Deposit an amount toward a goal.
     * Creates a real expense transaction on the linked wallet (if any),
     * then increments saved_amount — unless the goal has a category_id,
     * in which case the TransactionObserver already recalculates it.
     */
    public function deposit(User $user, Goal $goal, array $data): void
    {
        if ($goal->wallet_id) {
            $this->transactionService->create($user, [
                'wallet_id' => $goal->wallet_id,
                'type' => TransactionType::Expense->value,
                'amount' => $data['amount'],
                'category_id' => $data['category_id'],
                'date' => $data['date'],
                'description' => $data['description'] ?? $goal->name,
                'tags' => null,
            ]);
        }

        // If the goal has no linked category, the TransactionObserver won't sync saved_amount,
        // so we increment it manually. If a category IS linked and the deposit uses it,
        // the observer handles the sync automatically.
        if (! $goal->category_id) {
            $goal->increment('saved_amount', $data['amount']);
        }
    }
}
