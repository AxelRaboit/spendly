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

        return Inertia::render('Dashboard', [
            'spentThisMonth' => round($dashboardService->spentThisMonth($user), 2),
            'totalWallets' => $user->wallets()->count(),
            'recentTransactions' => $user->transactions()->with('category')->latest('date')->limit(5)->get(),
            'sparkline' => $dashboardService->sparkline($user),
            'topCategories' => $dashboardService->topCategories($user),
            'dailyAverage' => round($dashboardService->dailyAverage($user), 2),
            'bestDay' => $dashboardService->bestDay($user),
        ]);
    }
}
