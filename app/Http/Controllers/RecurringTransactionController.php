<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\PlanLimitException;
use App\Http\Requests\RecurringTransactionRequest;
use App\Models\RecurringTransaction;
use App\Services\PlanService;
use App\Services\RecurringTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecurringTransactionController extends Controller
{
    public function __construct(private readonly RecurringTransactionService $recurringService) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Recurring/Index', [
            'recurring' => $user->recurringTransactions()->with(['wallet', 'category'])->orderBy('day_of_month')->get(),
            'wallets' => $user->wallets()->orderBy('name')->get(['id', 'name']),
            'categories' => $user->categories()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(RecurringTransactionRequest $request): RedirectResponse
    {
        try {
            $this->recurringService->create($request->user(), $request->validated());

            return back()->with('success', 'Transaction récurrente créée.');
        } catch (PlanLimitException $planLimitException) {
            return back()
                ->with('plan_error', $planLimitException->limitKey->value)
                ->with('plan_error_limit', PlanService::FREE_RECURRING_LIMIT);
        }
    }

    public function update(RecurringTransactionRequest $request, RecurringTransaction $recurringTransaction): RedirectResponse
    {
        $this->authorize('update', $recurringTransaction);
        $recurringTransaction->update($request->validated());

        return back()->with('success', 'Transaction récurrente mise à jour.');
    }

    public function toggle(RecurringTransaction $recurringTransaction): RedirectResponse
    {
        $this->authorize('update', $recurringTransaction);
        $this->recurringService->toggle($recurringTransaction);

        return back()->with('preserveScroll', true);
    }

    public function destroy(RecurringTransaction $recurringTransaction): RedirectResponse
    {
        $this->authorize('delete', $recurringTransaction);
        $recurringTransaction->delete();

        return back()->with('success', 'Transaction récurrente supprimée.');
    }
}
