<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\WalletRole;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;

class TransactionPolicy
{
    public function view(User $user, Transaction $transaction): bool
    {
        return $user->id === $transaction->user_id
            || $this->hasWalletAccess($user, $transaction->wallet_id);
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return $user->id === $transaction->user_id
            || ($this->walletRole($user, $transaction->wallet_id)?->canEdit() ?? false);
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return $user->id === $transaction->user_id
            || ($this->walletRole($user, $transaction->wallet_id)?->canEdit() ?? false);
    }

    private function hasWalletAccess(User $user, ?int $walletId): bool
    {
        if (! $walletId) {
            return false;
        }

        return Wallet::find($walletId)?->roleFor($user) !== null;
    }

    private function walletRole(User $user, ?int $walletId): ?WalletRole
    {
        if (! $walletId) {
            return null;
        }

        return Wallet::find($walletId)?->roleFor($user);
    }
}
