<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

class OverviewService
{
    /**
     * Per-wallet income / expenses / cash flow for the given month.
     */
    public function walletsForMonth(User $user, string $month): Collection
    {
        [$start, $end] = $this->monthBounds($month);

        /** @var \Illuminate\Database\Eloquent\Collection<int, Wallet> $wallets */
        $wallets = $user->accessibleWallets()->orderBy('name')->get();

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

        $query = Transaction::whereIn('wallet_id', $user->accessibleWallets()->select('id'));

        $income = (float) (clone $query)
            ->where('type', TransactionType::Income)
            ->whereBetween('date', [$start, $end])
            ->sum('amount');

        $expenses = (float) (clone $query)
            ->where('type', TransactionType::Expense)
            ->whereBetween('date', [$start, $end])
            ->sum('amount');

        return [
            'income' => $income,
            'expenses' => $expenses,
            'cash_flow' => $income - $expenses,
        ];
    }

    /**
     * Income / expenses / cash_flow for the last N months ending at $month.
     *
     * @return array<int, array{month: string, income: float, expenses: float, cash_flow: float}>
     */
    public function trendForMonths(User $user, string $month, int $count = 6): array
    {
        $walletIds = $user->accessibleWallets()->select('id');
        $current = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        $rows = [];
        for ($i = $count - 1; $i >= 0; $i--) {
            $date = $current->copy()->subMonths($i);
            [$start, $end] = [$date->startOfMonth()->toDateString(), $date->copy()->endOfMonth()->toDateString()];

            $base = Transaction::whereIn('wallet_id', $walletIds)->whereBetween('date', [$start, $end]);

            $income = (float) (clone $base)->where('type', TransactionType::Income)->sum('amount');
            $expenses = (float) (clone $base)->where('type', TransactionType::Expense)->sum('amount');

            $rows[] = [
                'month' => $date->format('Y-m'),
                'income' => $income,
                'expenses' => $expenses,
                'cash_flow' => $income - $expenses,
            ];
        }

        return $rows;
    }

    /**
     * Top expense categories for the given month (across all accessible wallets).
     *
     * @return array<int, array{name: string, total: float}>
     */
    public function expensesByCategory(User $user, string $month): array
    {
        [$start, $end] = $this->monthBounds($month);

        $walletIds = $user->accessibleWallets()->select('id');

        return DB::table('transactions')
            ->whereIn('transactions.wallet_id', $walletIds)
            ->where('transactions.type', TransactionType::Expense->value)
            ->whereBetween('transactions.date', [$start, $end])
            ->whereNotNull('transactions.category_id')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('categories.is_system', false)
            ->selectRaw('categories.name, SUM(transactions.amount) as total')
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn (stdClass $row) => ['name' => $row->name, 'total' => (float) $row->total])
            ->all();
    }

    /** @return array{string, string} */
    private function monthBounds(string $month): array
    {
        $date = Carbon::createFromFormat('Y-m', $month);

        return [$date->startOfMonth()->toDateString(), $date->copy()->endOfMonth()->toDateString()];
    }
}
