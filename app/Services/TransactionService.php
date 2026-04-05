<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Support\Text;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TransactionService
{
    public function __construct(private readonly CategorizationRuleService $categorizationService) {}

    public function create(User $user, array $data): Transaction
    {
        if (isset($data['description'])) {
            $data['description'] = Text::normalize($data['description']);
        }

        $transaction = Transaction::create([
            'user_id' => $user->id,
            ...$data,
        ]);

        Log::info('Transaction created', [
            'user_id' => $user->id,
            'transaction_id' => $transaction->id,
        ]);

        if (($data['description'] ?? null) && ($data['category_id'] ?? null)) {
            $this->categorizationService->learn($user, $data['description'], (int) $data['category_id']);
        }

        return $transaction;
    }

    public function update(Transaction $transaction, array $data): Transaction
    {
        if (isset($data['description'])) {
            $data['description'] = Text::normalize($data['description']);
        }

        $transaction->update($data);

        Log::info('Transaction updated', [
            'transaction_id' => $transaction->id,
        ]);

        $description = $data['description'] ?? $transaction->description;
        $categoryId = $data['category_id'] ?? $transaction->category_id;

        if ($description && $categoryId && $transaction->user instanceof User) {
            $this->categorizationService->learn($transaction->user, $description, (int) $categoryId);
        }

        return $transaction;
    }

    /**
     * @param  array{wallet_id: int, type: string, description?: string, date: string, tags?: array}  $data
     * @param  array<int, array{category_id: int, amount: float}>  $splits
     * @return Transaction[]
     */
    public function createSplit(User $user, array $data, array $splits): array
    {
        if (isset($data['description'])) {
            $data['description'] = Text::normalize($data['description']);
        }

        $splitId = (string) Str::uuid();
        $transactions = [];

        foreach ($splits as $split) {
            $transactions[] = Transaction::create([
                'user_id' => $user->id,
                'split_id' => $splitId,
                'category_id' => $split['category_id'],
                'amount' => $split['amount'],
                ...$data,
            ]);
        }

        Log::info('Split transaction created', [
            'user_id' => $user->id,
            'split_id' => $splitId,
            'parts' => count($splits),
        ]);

        if ($data['description'] ?? null) {
            foreach ($splits as $split) {
                $this->categorizationService->learn($user, $data['description'], (int) $split['category_id']);
            }
        }

        return $transactions;
    }

    public function deleteSplit(string $splitId, User $user): int
    {
        $count = Transaction::where('split_id', $splitId)
            ->where('user_id', $user->id)
            ->delete();

        Log::info('Split transaction deleted', [
            'user_id' => $user->id,
            'split_id' => $splitId,
            'parts' => $count,
        ]);

        return $count;
    }

    public function delete(Transaction $transaction): void
    {
        $transactionId = $transaction->id;
        $transaction->delete();

        Log::info('Transaction deleted', [
            'transaction_id' => $transactionId,
        ]);
    }
}
