<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BudgetSection;
use App\Enums\TransactionType;
use App\Models\BudgetItem;
use App\Models\User;
use Illuminate\Support\Collection;

class StatisticsService
{
    public function byCategory(User $user): Collection
    {
        return $user->transactions()
            ->whereNull('transfer_id')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->selectRaw('categories.name, SUM(transactions.amount) as total')
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * Returns the last 6 months of spending, filling gaps with 0.
     *
     * @return array<int, array{month: string, total: float}>
     */
    public function byMonth(User $user, int $monthLimit = 6): array
    {
        $monthsBack = $monthLimit - 1;
        $raw = $user->transactions()
            ->whereNull('transfer_id')
            ->selectRaw("TO_CHAR(date, 'YYYY-MM') as month, SUM(amount) as total")
            ->where('date', '>=', now()->subMonths($monthsBack)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        return collect(range($monthsBack, 0))
            ->map(function (int $i) use ($raw): array {
                $month = now()->subMonths($i)->format('Y-m');

                return ['month' => $month, 'total' => (float) ($raw[$month] ?? 0)];
            })
            ->all();
    }

    /**
     * Spending per category per month for the last N months.
     *
     * @return array{months: string[], categories: array<int, array{id: int, name: string, data: float[]}>}
     */
    public function byCategoryPerMonth(User $user, int $monthLimit = 6): array
    {
        $monthsBack = $monthLimit - 1;
        $startDate = now()->subMonths($monthsBack)->startOfMonth();

        $months = collect(range($monthsBack, 0))
            ->map(fn (int $i) => now()->subMonths($i)->format('Y-m'))
            ->all();

        $raw = $user->transactions()
            ->whereNull('transfer_id')
            ->where('type', TransactionType::Expense)
            ->where('date', '>=', $startDate)
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->selectRaw("categories.id as cat_id, categories.name as cat_name, TO_CHAR(date, 'YYYY-MM') as month, SUM(transactions.amount) as total")
            ->groupBy('cat_id', 'cat_name', 'month')
            ->get();

        $categories = $raw->groupBy('cat_id')
            ->map(function (Collection $rows, int|string $catId) use ($months): array {
                $monthTotals = $rows->pluck('total', 'month');

                /** @var object{cat_name: string} $first */
                $first = $rows->first();

                return [
                    'id' => (int) $catId,
                    'name' => $first->cat_name,
                    'data' => array_map(fn (string $m) => (float) ($monthTotals[$m] ?? 0), $months),
                ];
            })
            ->sortByDesc(fn (array $c): float => (float) array_sum($c['data']))
            ->values()
            ->all();

        return ['months' => $months, 'categories' => $categories];
    }

    public function currentMonth(User $user): float
    {
        return (float) $user->transactions()
            ->whereNull('transfer_id')
            ->where('type', TransactionType::Expense)
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');
    }

    public function previousMonth(User $user): float
    {
        return (float) $user->transactions()
            ->whereNull('transfer_id')
            ->where('type', TransactionType::Expense)
            ->whereBetween('date', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->sum('amount');
    }

    /**
     * Savings rate per month for the last 6 months.
     * rate = (income - expenses) / income * 100
     *
     * @return array<int, array{month: string, income: float, expenses: float, rate: float|null}>
     */
    public function savingsRateHistory(User $user, int $monthLimit = 6): array
    {
        $monthsBack = $monthLimit - 1;
        $incomeRaw = $user->transactions()
            ->whereNull('transfer_id')
            ->selectRaw("TO_CHAR(date, 'YYYY-MM') as month, SUM(amount) as total")
            ->where('type', TransactionType::Income)
            ->where('date', '>=', now()->subMonths($monthsBack)->startOfMonth())
            ->groupBy('month')
            ->pluck('total', 'month');

        $expenseRaw = $user->transactions()
            ->whereNull('transfer_id')
            ->selectRaw("TO_CHAR(date, 'YYYY-MM') as month, SUM(amount) as total")
            ->where('type', TransactionType::Expense)
            ->where('date', '>=', now()->subMonths($monthsBack)->startOfMonth())
            ->groupBy('month')
            ->pluck('total', 'month');

        return collect(range($monthsBack, 0))
            ->map(function (int $i) use ($incomeRaw, $expenseRaw): array {
                $month = now()->subMonths($i)->format('Y-m');
                $income = (float) ($incomeRaw[$month] ?? 0);
                $expenses = (float) ($expenseRaw[$month] ?? 0);
                $rate = $income > 0 ? round(($income - $expenses) / $income * 100, 1) : null;

                return ['month' => $month, 'income' => $income, 'expenses' => $expenses, 'rate' => $rate];
            })
            ->all();
    }

    /**
     * Budget (planned) vs actual expenses per month for the last 6 months.
     *
     * @return array<int, array{month: string, planned: float, actual: float}>
     */
    public function budgetVsActual(User $user): array
    {
        $plannedRaw = BudgetItem::query()
            ->join('budgets', 'budgets.id', '=', 'budget_items.budget_id')
            ->where('budgets.user_id', $user->id)
            ->whereIn('budget_items.type', [BudgetSection::Expenses->value, BudgetSection::Bills->value, BudgetSection::Debt->value])
            ->where('budgets.month', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw("TO_CHAR(budgets.month, 'YYYY-MM') as month, SUM(budget_items.planned_amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $actualRaw = $user->transactions()
            ->whereNull('transfer_id')
            ->selectRaw("TO_CHAR(date, 'YYYY-MM') as month, SUM(amount) as total")
            ->where('type', TransactionType::Expense)
            ->where('date', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('month')
            ->pluck('total', 'month');

        return collect(range(5, 0))
            ->map(function (int $i) use ($plannedRaw, $actualRaw): array {
                $month = now()->subMonths($i)->format('Y-m');
                $planned = (float) ($plannedRaw[$month] ?? 0);
                $actual = (float) ($actualRaw[$month] ?? 0);

                return ['month' => $month, 'planned' => $planned, 'actual' => $actual];
            })
            ->all();
    }

    /**
     * Year-end projection based on average monthly expenses so far this year.
     */
    public function yearEndProjection(User $user, int $monthLimit = 6): array
    {
        if ($monthLimit < 6) {
            // FREE plan: don't show year projection
            return [
                'spent_so_far' => 0,
                'avg_per_month' => 0,
                'projected' => 0,
                'remaining' => 0,
                'months_left' => 0,
                'restricted' => true,
            ];
        }

        $currentMonth = (int) now()->format('n');

        $monthlyTotals = $user->transactions()
            ->whereNull('transfer_id')
            ->selectRaw("TO_CHAR(date, 'YYYY-MM') as month, SUM(amount) as total")
            ->where('type', TransactionType::Expense)
            ->whereYear('date', now()->year)
            ->groupBy('month')
            ->pluck('total', 'month');

        $spentSoFar = (float) $monthlyTotals->sum();
        $avgPerMonth = $spentSoFar / $currentMonth;
        $projected = $avgPerMonth * 12;
        $remaining = max(0, $projected - $spentSoFar);

        return [
            'spent_so_far' => round($spentSoFar, 2),
            'avg_per_month' => round($avgPerMonth, 2),
            'projected' => round($projected, 2),
            'remaining' => round($remaining, 2),
            'months_left' => 12 - $currentMonth,
        ];
    }
}
