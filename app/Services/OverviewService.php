<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class OverviewService
{
    /**
     * Per-wallet income / expenses / cash flow for the given month.
     */
    public function walletsForMonth(User $user, string $month): Collection
    {
        [$start, $end] = $this->monthBounds($month);

        /** @var \Illuminate\Database\Eloquent\Collection<int, Wallet> $wallets */
        $wallets = $user->wallets()->orderBy('name')->get();

        return $wallets->map(function (Wallet $wallet) use ($start, $end) {
            $income = (float) $wallet->transactions()
                ->where('type', TransactionType::Income)
                ->whereBetween('date', [$start, $end])
                ->sum('amount');

            $expenses = (float) $wallet->transactions()
                ->where('type', TransactionType::Expense)
                ->whereBetween('date', [$start, $end])
                ->sum('amount');

            return [
                'id' => $wallet->id,
                'name' => $wallet->name,
                'is_favorite' => $wallet->is_favorite,
                'start_balance' => (float) $wallet->start_balance,
                'income' => $income,
                'expenses' => $expenses,
                'cash_flow' => $income - $expenses,
            ];
        });
    }

    /**
     * Aggregated totals across all wallets for the given month.
     */
    public function totalsForMonth(User $user, string $month): array
    {
        [$start, $end] = $this->monthBounds($month);

        $income = (float) $user->transactions()
            ->where('type', TransactionType::Income)
            ->whereBetween('date', [$start, $end])
            ->sum('amount');

        $expenses = (float) $user->transactions()
            ->where('type', TransactionType::Expense)
            ->whereBetween('date', [$start, $end])
            ->sum('amount');

        return [
            'income' => $income,
            'expenses' => $expenses,
            'cash_flow' => $income - $expenses,
        ];
    }

    /** @return array{string, string} */
    private function monthBounds(string $month): array
    {
        $date = Carbon::createFromFormat('Y-m', $month);

        return [$date->startOfMonth()->toDateString(), $date->copy()->endOfMonth()->toDateString()];
    }
}
