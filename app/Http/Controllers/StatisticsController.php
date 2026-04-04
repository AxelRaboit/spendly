<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatisticsController extends Controller
{
    public function index(Request $request, StatisticsService $statisticsService): Response
    {
        $user = $request->user();

        return Inertia::render('Statistics/Index', [
            'byCategory' => $statisticsService->byCategory($user),
            'byMonth' => $statisticsService->byMonth($user),
            'currentMonth' => $statisticsService->currentMonth($user),
            'previousMonth' => $statisticsService->previousMonth($user),
        ]);
    }
}
