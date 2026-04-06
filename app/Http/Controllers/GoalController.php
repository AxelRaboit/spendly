<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\PlanLimitException;
use App\Http\Requests\GoalDepositRequest;
use App\Http\Requests\GoalRequest;
use App\Models\Goal;
use App\Services\GoalService;
use App\Services\PlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GoalController extends Controller
{
    public function __construct(
        private readonly GoalService $goalService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Goals/Index', [
            'goals' => $this->goalService->list($user),
            'wallets' => $user->walletOptions(),
            'categories' => $user->categoryOptions(),
        ]);
    }

    public function store(GoalRequest $goalRequest): RedirectResponse
    {
        try {
            $this->goalService->create($goalRequest->user(), $goalRequest->validated());

            return back()->with('success', __('flash.goal.created'));
        } catch (PlanLimitException $planLimitException) {
            return back()
                ->with('plan_error', $planLimitException->limitKey->value)
                ->with('plan_error_limit', PlanService::FREE_GOAL_LIMIT);
        }
    }

    public function update(GoalRequest $goalRequest, Goal $goal): RedirectResponse
    {
        $this->authorize('update', $goal);
        $this->goalService->update($goal, $goalRequest->validated());

        return back()->with('success', __('flash.goal.updated'));
    }

    public function deposit(GoalDepositRequest $goalDepositRequest, Goal $goal): RedirectResponse
    {
        $this->authorize('update', $goal);

        $this->goalService->deposit($goalDepositRequest->user(), $goal, $goalDepositRequest->validated());

        return back()->with('success', __('flash.goal.deposit'));
    }

    public function destroy(Goal $goal): RedirectResponse
    {
        $this->authorize('delete', $goal);
        $this->goalService->delete($goal);

        return back()->with('success', __('flash.goal.deleted'));
    }
}
