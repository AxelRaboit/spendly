<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    public function create(User $user, array $data): Transaction
    {
        $transaction = Transaction::create([
            'user_id' => $user->id,
            ...$data,
        ]);

        Log::info('Transaction created', [
            'user_id' => $user->id,
            'transaction_id' => $transaction->id,
        ]);

        return $transaction;
    }

    public function update(Transaction $transaction, array $data): Transaction
    {
        $transaction->update($data);

        Log::info('Transaction updated', [
            'transaction_id' => $transaction->id,
        ]);

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
