<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PolicyAction;
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
    public function __construct(
        private readonly RecurringTransactionService $recurringService,
        private readonly PlanService $planService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Recurring/Index', [
            'recurring' => $this->recurringService->list($user),
            'scheduled' => $this->recurringService->listScheduled($user),
            'wallets' => $user->walletOptions(),
            'categories' => $user->categoryOptions(),
        ]);
    }

    public function store(RecurringTransactionRequest $recurringTransactionRequest): RedirectResponse
    {
        try {
            $this->recurringService->create($recurringTransactionRequest->user(), $recurringTransactionRequest->validated());

            return back()->with('success', __('flash.recurring.created'));
        } catch (PlanLimitException $planLimitException) {
            return back()
                ->with('plan_error', $planLimitException->limitKey->value)
                ->with('plan_error_limit', $this->planService->freeRecurringLimit());
        }
    }

    public function update(RecurringTransactionRequest $recurringTransactionRequest, RecurringTransaction $recurringTransaction): RedirectResponse
    {
        $this->authorize(PolicyAction::Update->value, $recurringTransaction);
        $this->recurringService->update($recurringTransaction, $recurringTransactionRequest->validated());

        return back()->with('success', __('flash.recurring.updated'));
    }

    public function toggle(RecurringTransaction $recurringTransaction): RedirectResponse
    {
        $this->authorize(PolicyAction::Update->value, $recurringTransaction);
        $this->recurringService->toggle($recurringTransaction);

        return back()->with('preserveScroll', true);
    }

    public function destroy(RecurringTransaction $recurringTransaction): RedirectResponse
    {
        $this->authorize(PolicyAction::Delete->value, $recurringTransaction);
        $this->recurringService->delete($recurringTransaction);

        return back()->with('success', __('flash.recurring.deleted'));
    }
}
