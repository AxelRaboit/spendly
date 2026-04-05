<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\GoalDepositRequest;
use App\Http\Requests\GoalRequest;
use App\Models\Goal;
use App\Services\GoalService;
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
            'wallets' => $user->wallets()->orderBy('name')->get(['id', 'name']),
            'categories' => $user->categories()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(GoalRequest $request): RedirectResponse
    {
        $request->user()->goals()->create($request->validated());

        return back()->with('success', 'Objectif créé.');
    }

    public function update(GoalRequest $request, Goal $goal): RedirectResponse
    {
        $this->authorize('update', $goal);
        $goal->update($request->validated());

        return back()->with('success', 'Objectif mis à jour.');
    }

    public function deposit(GoalDepositRequest $request, Goal $goal): RedirectResponse
    {
        $this->authorize('update', $goal);

        $this->goalService->deposit($request->user(), $goal, $request->validated());

        return back()->with('success', 'Dépôt effectué.');
    }

    public function destroy(Goal $goal): RedirectResponse
    {
        $this->authorize('delete', $goal);
        $goal->delete();

        return back()->with('success', 'Objectif supprimé.');
    }
}
