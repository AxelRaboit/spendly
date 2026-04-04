<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use Exception;
use Illuminate\Support\Facades\Log;

class WalletService
{
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
