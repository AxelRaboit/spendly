<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PlanType;
use App\Models\Goal;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminStatsService
{
    public function getStats(): array
    {
        $months6 = $this->buildMonthRange(6);
        $months12 = $this->buildMonthRange(12);

        $totalUsers = User::count();
        $activeUserCount = Transaction::where('created_at', '>=', now()->subDays(30))
            ->distinct('user_id')
            ->count('user_id');

        return [
            'users' => [
                'total' => $totalUsers,
                'pro' => User::where('plan', PlanType::Pro)->count(),
                'free' => User::where('plan', PlanType::Free)->count(),
                'newThisMonth' => User::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
            ],
            'wallets' => Wallet::count(),
            'transactions' => [
                'total' => Transaction::count(),
                'thisMonth' => Transaction::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
            ],
            'goals' => Goal::count(),
            'recurring' => RecurringTransaction::where('active', true)->count(),
            'activeUsers' => $activeUserCount,
            'inactiveUsers' => max(0, $totalUsers - $activeUserCount),
            'usersByMonth' => $this->fillMonths($months6, $this->queryByMonth(User::class, 6)),
            'transactionsByMonth' => $this->fillMonths($months6, $this->queryByMonth(Transaction::class, 6)),
            'cumulativeGrowth' => $this->buildCumulativeGrowth($months12),
            'localeDistribution' => User::select('locale', DB::raw('COUNT(*) as locale_count'))
                ->groupBy('locale')
                ->orderByDesc(DB::raw('COUNT(*)'))
                ->get()
                ->map(fn ($r) => ['locale' => $r->locale ?? 'fr', 'count' => (int) $r->getAttribute('locale_count')])
                ->all(),
        ];
    }

    /**
     * @return Collection<int, non-falsy-string>
     */
    private function buildMonthRange(int $count): Collection
    {
        return collect(range($count - 1, 0))
            ->map(fn (int $i) => now()->subMonths($i)->format('Y-m'));
    }

    /**
     * @param  class-string  $model
     * @return Collection<string, int>
     */
    private function queryByMonth(string $model, int $months = 6): Collection
    {
        return $model::select(
            DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', now()->subMonths($months - 1)->startOfMonth())
            ->groupBy('month')
            ->pluck('count', 'month');
    }

    /**
     * @param  Collection<int, non-falsy-string>  $months
     * @return array<int, array{month: string, count: int}>
     */
    private function buildCumulativeGrowth(Collection $months): array
    {
        $newByMonth = $this->queryByMonth(User::class, 12);

        $usersBeforeRange = User::where(
            'created_at', '<', now()->subMonths(11)->startOfMonth()
        )->count();

        $cumulative = $usersBeforeRange;

        return $months->map(function (string $m) use ($newByMonth, &$cumulative) {
            $cumulative += $newByMonth->get($m, 0);

            return ['month' => $m, 'count' => $cumulative];
        })->values()->all();
    }

    /**
     * @param  Collection<int, non-falsy-string>  $months
     * @param  Collection<string, int>  $data
     * @return array<int, array{month: string, count: int}>
     */
    private function fillMonths(Collection $months, Collection $data): array
    {
        return $months
            ->map(fn (string $m) => ['month' => $m, 'count' => $data->get($m, 0)])
            ->values()
            ->all();
    }
}
