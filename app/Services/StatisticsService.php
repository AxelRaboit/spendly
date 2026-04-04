<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class StatisticsService
{
    public function byCategory(User $user): Collection
    {
        return $user->transactions()
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
    public function byMonth(User $user): array
    {
        $raw = $user->transactions()
            ->selectRaw("TO_CHAR(date, 'YYYY-MM') as month, SUM(amount) as total")
            ->where('date', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        return collect(range(5, 0))
            ->map(function (int $i) use ($raw): array {
                $month = now()->subMonths($i)->format('Y-m');

                return ['month' => $month, 'total' => (float) ($raw[$month] ?? 0)];
            })
            ->all();
    }

    public function currentMonth(User $user): float
    {
        return (float) $user->transactions()
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');
    }

    public function previousMonth(User $user): float
    {
        return (float) $user->transactions()
            ->whereBetween('date', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->sum('amount');
    }
}
