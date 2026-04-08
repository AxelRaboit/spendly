<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardService $dashboardService): Response
    {
        $user = $request->user();

        $spentThisMonth = round($dashboardService->spentThisMonth($user), 2);
        $lastMonthSpent = round($dashboardService->lastMonthSpent($user), 2);

        return Inertia::render('Dashboard', [
            'spentThisMonth' => $spentThisMonth,
            'incomeThisMonth' => round($dashboardService->incomeThisMonth($user), 2),
            'lastMonthSpent' => $lastMonthSpent,
            'lastMonthDiff' => $lastMonthSpent > 0
                ? round(($spentThisMonth - $lastMonthSpent) / $lastMonthSpent * 100, 1)
                : null,
            'totalWallets' => $dashboardService->totalWallets($user),
            'favoriteWallets' => $dashboardService->favoriteWallets($user),
            'recentTransactions' => $dashboardService->recentTransactions($user),
            'sparkline' => $dashboardService->sparkline($user),
            'topCategories' => $dashboardService->topCategories($user),
            'dailyAverage' => round($dashboardService->dailyAverage($user), 2),
            'bestDay' => $dashboardService->bestDay($user),
            'pinnedWallets' => $dashboardService->pinnedWallets($user),
            'activeGoals' => $dashboardService->activeGoals($user),
            'upcomingRecurring' => $dashboardService->upcomingRecurring($user),
            'overBudgetAlerts' => $dashboardService->overBudgetAlerts($user),
        ]);
    }
}
