<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Support\Text;
use Illuminate\Support\Facades\Log;

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

    public function delete(Transaction $transaction): void
    {
        $transactionId = $transaction->id;
        $transaction->delete();

        Log::info('Transaction deleted', [
            'transaction_id' => $transactionId,
        ]);
    }
}
