<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionType;
use App\Enums\WalletMode;
use App\Models\Goal;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function sparkline(User $user): Collection
    {
        return $this->accessibleExpenses($user)
            ->selectRaw("TO_CHAR(date, 'YYYY-MM-DD') as day, SUM(amount) as total")
            ->where('date', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('day')
            ->orderBy('day')
            ->get();
    }

    public function topCategories(User $user): Collection
    {
        return $this->accessibleExpenses($user)
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->selectRaw('categories.name, SUM(transactions.amount) as total')
            ->whereBetween('transactions.date', [now()->startOfMonth(), now()->endOfMonth()])
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->limit(3)
            ->get();
    }

    public function dailyAverage(User $user): float
    {
        return (float) ($this->accessibleExpenses($user)
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->avg('amount') ?? 0);
    }

    public function bestDay(User $user): ?object
    {
        return $this->accessibleExpenses($user)
            ->selectRaw("TO_CHAR(date, 'YYYY-MM-DD') as day, SUM(amount) as total")
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->groupBy('day')
            ->orderByDesc('total')
            ->first();
    }

    public function spentThisMonth(User $user): float
    {
        return (float) $this->accessibleExpenses($user)
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');
    }

    public function incomeThisMonth(User $user): float
    {
        return (float) $this->accessibleIncome($user)
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');
    }

    public function lastMonthSpent(User $user): float
    {
        return (float) $this->accessibleExpenses($user)
            ->whereBetween('date', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->sum('amount');
    }

    public function totalWallets(User $user): int
    {
        return $user->accessibleWallets()->count();
    }

    public function recentTransactions(User $user, int $limit = 5): Collection
    {
        return $this->accessibleExpenses($user)
            ->with('category', 'wallet')
            ->latest('date')
            ->limit($limit)
            ->get();
    }

    public function pinnedWallets(User $user): Collection
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Wallet> $wallets */
        $wallets = $user->accessibleWallets()
            ->where('show_on_dashboard', true)
            ->withSum(['transactions as income_sum' => fn ($query) => $query->where('type', TransactionType::Income)], 'amount')
            ->withSum(['transactions as expense_sum' => fn ($query) => $query->where('type', TransactionType::Expense)], 'amount')
            ->get();

        return $wallets->map(function (Wallet $wallet) {
            /** @var WalletMode $mode */
            $mode = $wallet->mode;

            return [
                'id' => $wallet->id,
                'name' => $wallet->name,
                'mode' => $mode->value,
                'current_balance' => round((float) $wallet->start_balance + (float) ($wallet->income_sum ?? 0) - (float) ($wallet->expense_sum ?? 0), 2),
            ];
        });
    }

    public function activeGoals(User $user, int $limit = 3): Collection
    {
        return Goal::where('user_id', $user->id)
            ->whereRaw('saved_amount < target_amount')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get(['id', 'name', 'target_amount', 'saved_amount', 'color', 'deadline']);
    }

    public function upcomingRecurring(User $user, int $limit = 5): Collection
    {
        $today = now()->day;

        return RecurringTransaction::where('user_id', $user->id)
            ->where('active', true)
            ->with('category', 'wallet')
            ->orderByRaw('CASE WHEN day_of_month >= ? THEN 0 ELSE 1 END, day_of_month', [$today])
            ->limit($limit)
            ->get();
    }

    public function overBudgetAlerts(User $user): Collection
    {
        $walletIds = $user->accessibleWallets()->select('id');
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $month = now()->format('Y-m-01');

        return DB::table('budget_items')
            ->join('budgets', 'budget_items.budget_id', '=', 'budgets.id')
            ->whereIn('budgets.wallet_id', $walletIds)
            ->where('budgets.month', $month)
            ->where('budget_items.planned_amount', '>', 0)
            ->leftJoin('transactions', function ($joinClause) use ($monthStart, $monthEnd) {
                $joinClause->on('transactions.category_id', '=', 'budget_items.category_id')
                    ->on('transactions.wallet_id', '=', 'budgets.wallet_id')
                    ->where('transactions.type', TransactionType::Expense->value)
                    ->whereBetween('transactions.date', [$monthStart, $monthEnd]);
            })
            ->select('budget_items.label', 'budget_items.planned_amount')
            ->selectRaw('COALESCE(SUM(transactions.amount), 0) as actual')
            ->groupBy('budget_items.id', 'budget_items.label', 'budget_items.planned_amount')
            ->havingRaw('COALESCE(SUM(transactions.amount), 0) > budget_items.planned_amount * 0.8 AND COALESCE(SUM(transactions.amount), 0) != budget_items.planned_amount')
            ->orderByRaw('COALESCE(SUM(transactions.amount), 0) / budget_items.planned_amount DESC')
            ->limit(5)
            ->get();
    }

    private function accessibleExpenses(User $user): Builder
    {
        return Transaction::whereIn('transactions.wallet_id', $user->accessibleWallets()->select('id'))
            ->where('transactions.type', TransactionType::Expense);
    }

    private function accessibleIncome(User $user): Builder
    {
        return Transaction::whereIn('transactions.wallet_id', $user->accessibleWallets()->select('id'))
            ->where('transactions.type', TransactionType::Income);
    }
}
