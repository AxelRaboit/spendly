<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\OverviewService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OverviewController extends Controller
{
    public function index(Request $request, OverviewService $overviewService): Response
    {
        $user = $request->user();
        $month = $request->input('month', now()->format('Y-m'));

        return Inertia::render('Overview/Index', [
            'month' => $month,
            'prev' => now()->createFromFormat('Y-m', $month)->subMonth()->format('Y-m'),
            'next' => now()->createFromFormat('Y-m', $month)->addMonth()->format('Y-m'),
            'wallets' => $overviewService->walletsForMonth($user, $month),
            'totals' => $overviewService->totalsForMonth($user, $month),
        ]);
    }
}
