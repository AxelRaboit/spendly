<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    public function create(User $user, array $data): Transaction
    {
        try {
            $transaction = $user->transactions()->create($data);

            Log::info('Transaction created', [
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
            ]);

            return $transaction;
        } catch (\Exception $exception) {
            Log::error('Failed to create transaction', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function update(Transaction $transaction, array $data): Transaction
    {
        try {
            $transaction->update($data);

            Log::info('Transaction updated', [
                'transaction_id' => $transaction->id,
            ]);

            return $transaction;
        } catch (\Exception $exception) {
            Log::error('Failed to update transaction', [
                'transaction_id' => $transaction->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function delete(Transaction $transaction): void
    {
        try {
            $transactionId = $transaction->id;
            $transaction->delete();

            Log::info('Transaction deleted', [
                'transaction_id' => $transactionId,
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to delete transaction', [
                'transaction_id' => $transaction->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
