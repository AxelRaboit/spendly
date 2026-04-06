<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardService
{
    public function sparkline(User $user): Collection
    {
        return $this->accessibleTransactions($user)
            ->selectRaw("TO_CHAR(date, 'YYYY-MM-DD') as day, SUM(amount) as total")
            ->where('date', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('day')
            ->orderBy('day')
            ->get();
    }

    public function topCategories(User $user): Collection
    {
        return $this->accessibleTransactions($user)
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
        return (float) ($this->accessibleTransactions($user)
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->avg('amount') ?? 0);
    }

    public function bestDay(User $user): ?object
    {
        return $this->accessibleTransactions($user)
            ->selectRaw("TO_CHAR(date, 'YYYY-MM-DD') as day, SUM(amount) as total")
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->groupBy('day')
            ->orderByDesc('total')
            ->first();
    }

    public function spentThisMonth(User $user): float
    {
        return (float) $this->accessibleTransactions($user)
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');
    }

    public function totalWallets(User $user): int
    {
        return $user->accessibleWallets()->count();
    }

    public function favoriteWallets(User $user): Collection
    {
        return $user->accessibleWallets()
            ->where('is_favorite', true)
            ->orderBy('name')
            ->get();
    }

    public function recentTransactions(User $user, int $limit = 5): Collection
    {
        return $this->accessibleTransactions($user)
            ->with('category', 'wallet')
            ->latest('date')
            ->limit($limit)
            ->get();
    }

    private function accessibleTransactions(User $user): Builder
    {
        return Transaction::whereIn('transactions.wallet_id', $user->accessibleWallets()->select('id'));
    }
}
