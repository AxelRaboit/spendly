<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PolicyAction;
use App\Http\Requests\StoreBalanceAdjustmentRequest;
use App\Models\Wallet;
use App\Services\BudgetService;
use Illuminate\Http\RedirectResponse;

class BalanceAdjustmentController extends Controller
{
    public function __construct(
        private readonly BudgetService $budgetService,
    ) {}

    public function store(StoreBalanceAdjustmentRequest $request, Wallet $wallet): RedirectResponse
    {
        $this->authorize(PolicyAction::Update->value, $wallet);

        $newBalance = $this->budgetService->getNewBalance($request->all());
        ['diff' => $diff] = $this->budgetService->calculateAdjustment($wallet, $newBalance);

        if (! $this->budgetService->needsAdjustment($diff)) {
            return back()->withErrors(['new_balance' => __('flash.balance_adjustment.no_diff')]);
        }

        $this->budgetService->createAdjustmentTransaction(
            $request->user(),
            $wallet,
            $diff,
            $request->input('date'),
            $request->input('description')
        );

        return back()->with('success', __('flash.balance_adjustment.created'));
    }
}
