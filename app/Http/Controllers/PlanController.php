<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function __construct(private readonly PlanService $planService) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Plan/Index');
    }

    public function upgrade(Request $request): RedirectResponse
    {
        $this->planService->upgrade($request->user());

        return redirect()->route('plan.index')->with('success', __('flash.plan.upgraded'));
    }

    public function downgrade(Request $request): RedirectResponse
    {
        $this->planService->downgrade($request->user());

        return redirect()->route('plan.index')->with('success', __('flash.plan.downgraded'));
    }
}
