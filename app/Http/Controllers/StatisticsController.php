<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatisticsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $byCategory = $user->transactions()
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->selectRaw('categories.name, SUM(transactions.amount) as total')
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->get();

        $byMonth = $user->transactions()
            ->selectRaw("TO_CHAR(date, 'YYYY-MM') as month, SUM(amount) as total")
            ->where('date', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $currentMonth = (float) $user->transactions()
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        $previousMonth = (float) $user->transactions()
            ->whereBetween('date', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->sum('amount');

        return Inertia::render('Statistics/Index', [
            'byCategory' => $byCategory,
            'byMonth' => $byMonth,
            'currentMonth' => $currentMonth,
            'previousMonth' => $previousMonth,
        ]);
    }
}
