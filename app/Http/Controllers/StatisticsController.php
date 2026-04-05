<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PlanService;
use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatisticsController extends Controller
{
    public function index(Request $request, StatisticsService $statisticsService, PlanService $planService): Response
    {
        $user = $request->user();
        $monthLimit = $planService->statsMonthLimit($user);

        return Inertia::render('Statistics/Index', [
            'byCategory' => $statisticsService->byCategory($user),
            'byMonth' => $statisticsService->byMonth($user, $monthLimit),
            'currentMonth' => $statisticsService->currentMonth($user),
            'previousMonth' => $statisticsService->previousMonth($user),
            'savingsHistory' => $statisticsService->savingsRateHistory($user, $monthLimit),
            'budgetVsActual' => $statisticsService->budgetVsActual($user),
            'yearProjection' => $statisticsService->yearEndProjection($user, $monthLimit),
        ]);
    }
}
