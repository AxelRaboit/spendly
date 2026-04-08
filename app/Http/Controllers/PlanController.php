<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PlanService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PlanController extends Controller
{
    public function __construct(private readonly PlanService $planService) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Plan/Index');
    }

    public function upgrade(Request $request): SymfonyResponse
    {
        $this->planService->upgrade($request->user());
        $request->session()->flash('success', __('flash.plan.upgraded'));

        return Inertia::location(route('plan.index'));
    }

    public function downgrade(Request $request): SymfonyResponse
    {
        $this->planService->downgrade($request->user());
        $request->session()->flash('success', __('flash.plan.downgraded'));

        return Inertia::location(route('plan.index'));
    }
}
