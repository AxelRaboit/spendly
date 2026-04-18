<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PlanType;
use App\Models\Goal;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminStatsService
{
    public function getStats(): array
    {
        $months6 = $this->buildMonthRange(6);
        $months12 = $this->buildMonthRange(12);

        $totalUsers = $this->realUsers()->count();
        $realUserIds = $this->realUsers()->select('id');

        $activeUserCount = Transaction::whereIn('user_id', $realUserIds)
            ->where('created_at', '>=', now()->subDays(30))
            ->distinct('user_id')
            ->count('user_id');

        return [
            'users' => [
                'total' => $totalUsers,
                'pro' => $this->realUsers()->where('plan', PlanType::Pro)->count(),
                'free' => $this->realUsers()->where('plan', PlanType::Free)->count(),
                'newThisMonth' => $this->realUsers()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
            ],
            'wallets' => Wallet::whereIn('user_id', $realUserIds)->count(),
            'transactions' => [
                'total' => Transaction::whereIn('user_id', $realUserIds)->count(),
                'thisMonth' => Transaction::whereIn('user_id', $realUserIds)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
            ],
            'goals' => Goal::whereIn('user_id', $realUserIds)->count(),
            'recurring' => RecurringTransaction::whereIn('user_id', $realUserIds)->where('active', true)->count(),
            'activeUsers' => $activeUserCount,
            'inactiveUsers' => max(0, $totalUsers - $activeUserCount),
            'usersByMonth' => $this->fillMonths($months6, $this->queryUsersByMonth(6)),
            'transactionsByMonth' => $this->fillMonths($months6, $this->queryTransactionsByMonth(6)),
            'cumulativeGrowth' => $this->buildCumulativeGrowth($months12),
            'localeDistribution' => $this->realUsers()
                ->select('locale', DB::raw('COUNT(*) as locale_count'))
                ->groupBy('locale')
                ->orderByDesc(DB::raw('COUNT(*)'))
                ->get()
                ->map(fn ($row) => ['locale' => $row->locale ?? 'fr', 'count' => (int) $row->getAttribute('locale_count')])
                ->all(),
        ];
    }

    private function realUsers(): Builder
    {
        return User::where('is_demo', false);
    }

    /**
     * @return Collection<int, non-falsy-string>
     */
    private function buildMonthRange(int $count): Collection
    {
        return collect(range($count - 1, 0))
            ->map(fn (int $monthOffset) => now()->subMonths($monthOffset)->format('Y-m'));
    }

    /** @return Collection<string, int> */
    private function queryUsersByMonth(int $months): Collection
    {
        return $this->realUsers()
            ->select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subMonths($months - 1)->startOfMonth())
            ->groupBy('month')
            ->pluck('count', 'month');
    }

    /** @return Collection<string, int> */
    private function queryTransactionsByMonth(int $months): Collection
    {
        return Transaction::whereIn('user_id', $this->realUsers()->select('id'))
            ->select(
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
        $newByMonth = $this->queryUsersByMonth(12);

        $usersBeforeRange = $this->realUsers()
            ->where('created_at', '<', now()->subMonths(11)->startOfMonth())
            ->count();

        $cumulative = $usersBeforeRange;

        return $months->map(function (string $monthKey) use ($newByMonth, &$cumulative) {
            $cumulative += $newByMonth->get($monthKey, 0);

            return ['month' => $monthKey, 'count' => $cumulative];
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
            ->map(fn (string $monthKey) => ['month' => $monthKey, 'count' => $data->get($monthKey, 0)])
            ->values()
            ->all();
    }
}
