<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\User;
use App\Models\Wallet;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class WalletService
{
    public function getWalletsWithBalances(User $user): Collection
    {
        return Wallet::query()
            ->where('user_id', $user->id)
            ->orderBy('position')
            ->orderBy('name')
            ->withSum(['transactions as income_sum' => fn ($q) => $q->where('type', TransactionType::Income)], 'amount')
            ->withSum(['transactions as expense_sum' => fn ($q) => $q->where('type', TransactionType::Expense)], 'amount')
            ->get()
            ->map(fn ($w) => array_merge($w->toArray(), [
                'current_balance' => round(
                    (float) $w->start_balance + (float) ($w->income_sum ?? 0) - (float) ($w->expense_sum ?? 0),
                    2
                ),
            ]));
    }

    public function toggleFavorite(Wallet $wallet): void
    {
        $wallet->update(['is_favorite' => ! $wallet->is_favorite]);
    }

    public function create(User $user, array $data): Wallet
    {
        try {
            $wallet = Wallet::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'start_balance' => $data['start_balance'],
            ]);

            Log::info('Wallet created', ['user_id' => $user->id, 'wallet_id' => $wallet->id]);

            return $wallet;
        } catch (Exception $exception) {
            Log::error('Failed to create wallet', ['user_id' => $user->id, 'error' => $exception->getMessage()]);
            throw $exception;
        }
    }

    public function update(Wallet $wallet, array $data): Wallet
    {
        try {
            $wallet->update([
                'name' => $data['name'],
                'start_balance' => $data['start_balance'],
            ]);

            Log::info('Wallet updated', ['wallet_id' => $wallet->id]);

            return $wallet;
        } catch (Exception $exception) {
            Log::error('Failed to update wallet', ['wallet_id' => $wallet->id, 'error' => $exception->getMessage()]);
            throw $exception;
        }
    }

    /** @param array<int, int> $ids */
    public function reorder(User $user, array $ids): void
    {
        foreach ($ids as $position => $id) {
            Wallet::where('id', (int) $id)
                ->where('user_id', $user->id)
                ->update(['position' => $position]);
        }
    }

    public function delete(Wallet $wallet): void
    {
        try {
            $walletId = $wallet->id;
            $wallet->delete();

            Log::info('Wallet deleted', ['wallet_id' => $walletId]);
        } catch (Exception $exception) {
            Log::error('Failed to delete wallet', ['wallet_id' => $wallet->id, 'error' => $exception->getMessage()]);
            throw $exception;
        }
    }
}
