<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $sparkline = $user->transactions()
            ->selectRaw("TO_CHAR(date, 'YYYY-MM-DD') as day, SUM(amount) as total")
            ->where('date', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $topCategories = $user->transactions()
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->selectRaw('categories.name, SUM(transactions.amount) as total')
            ->whereBetween('transactions.date', [now()->startOfMonth(), now()->endOfMonth()])
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->limit(3)
            ->get();

        $dailyAverage = (float) ($user->transactions()
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->avg('amount') ?? 0);

        $bestDay = $user->transactions()
            ->selectRaw("TO_CHAR(date, 'YYYY-MM-DD') as day, SUM(amount) as total")
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->groupBy('day')
            ->orderByDesc('total')
            ->first();

        return Inertia::render('Dashboard', [
            'totalTransactions' => $user->transactions()->count(),
            'totalCategories' => $user->categories()->count(),
            'recentTransactions' => $user->transactions()->with('category')->latest('date')->limit(5)->get(),
            'sparkline' => $sparkline,
            'topCategories' => $topCategories,
            'dailyAverage' => round($dailyAverage, 2),
            'bestDay' => $bestDay,
        ]);
    }
}
