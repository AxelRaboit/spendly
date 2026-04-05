<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PlanLimitKey;
use App\Enums\TransactionType;
use App\Exceptions\PlanLimitException;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class WalletService
{
    public function __construct(
        private readonly PlanService $planService,
    ) {}

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
        if (! $this->planService->canCreateWallet($user)) {
            throw new PlanLimitException(PlanLimitKey::Wallet);
        }

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'start_balance' => $data['start_balance'],
        ]);

        Log::info('Wallet created', ['user_id' => $user->id, 'wallet_id' => $wallet->id]);

        return $wallet;
    }

    public function update(Wallet $wallet, array $data): Wallet
    {
        $wallet->update([
            'name' => $data['name'],
            'start_balance' => $data['start_balance'],
        ]);

        Log::info('Wallet updated', ['wallet_id' => $wallet->id]);

        return $wallet;
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
        $walletId = $wallet->id;
        $wallet->delete();

        Log::info('Wallet deleted', ['wallet_id' => $walletId]);
    }
}
