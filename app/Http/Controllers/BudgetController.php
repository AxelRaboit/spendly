<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\HttpStatus;
use App\Enums\PolicyAction;
use App\Http\Requests\ClearBudgetItemsRequest;
use App\Http\Requests\QuickStartBudgetRequest;
use App\Http\Requests\ReorderRequest;
use App\Http\Requests\StoreBudgetItemRequest;
use App\Http\Requests\UpdateBudgetItemRequest;
use App\Http\Requests\UpdateBudgetNotesRequest;
use App\Models\BudgetItem;
use App\Models\Wallet;
use App\Services\BudgetPresetService;
use App\Services\BudgetService;
use App\Services\GoalService;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class BudgetController extends Controller
{
    public function __construct(
        private readonly BudgetService $budgetService,
        private readonly BudgetPresetService $presetService,
        private readonly GoalService $goalService,
        private readonly PlanService $planService,
    ) {}

    public function show(Request $request, Wallet $wallet): Response
    {
        $this->authorize(PolicyAction::View->value, $wallet);
        abort_if($wallet->isSimpleMode(), HttpStatus::NotFound->value);

        $month = $this->budgetService->getMonthFromRequest(['month' => $request->query('month')]);

        $budget = $this->budgetService->getOrCreate($wallet, $month);
        $this->budgetService->computeCarryOver($budget, $month);
        ['sections' => $sections, 'unbudgeted' => $unbudgeted] = $this->budgetService->loadWithActuals($budget);

        $prevMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');

        $user = $request->user();
        $currentBalance = $this->budgetService->getMonthBalance($wallet, $month);

        return Inertia::render('Budgets/Show', [
            'wallet' => array_merge($wallet->toArray(), [
                'is_shared' => $wallet->isShared(),
                'user_role' => $wallet->roleFor($user)?->value,
                'current_balance' => $currentBalance,
            ]),
            'budget' => [
                'id' => $budget->id,
                'month' => $budget->month->format('Y-m'),
                'month_label' => $budget->month->locale(App::getLocale())->translatedFormat('F Y'),
                'notes' => $budget->notes,
            ],
            'sections' => $sections,
            'unbudgeted' => $unbudgeted,
            'categories' => $this->budgetService->userCategories($user, $wallet),
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'startBalance' => $this->budgetService->computeRollingStartBalance($wallet, $month),
            'flashCategory' => $request->query('flash_category') ? (int) $request->query('flash_category') : null,
            'prevItems' => $this->budgetService->getPreviousMonthItems($wallet, $month),
            'goals' => $this->goalService->listForWallet($user, $wallet),
            'budgetPresets' => $this->presetService->list($user),
        ]);
    }

    public function copyFromPrevious(Request $request, Wallet $wallet): RedirectResponse
    {
        $this->authorize(PolicyAction::View->value, $wallet);
        abort_if(! $this->planService->canEditBudget($request->user()), HttpStatus::Forbidden->value);

        $month = $this->budgetService->getMonthFromRequest($request->all());
        $budget = $this->budgetService->getOrCreate($wallet, $month);
        $copied = $this->budgetService->copyFromPreviousMonth($budget, $month, $request->input('item_ids', []));

        return redirect()->back()->with(
            'success',
            $copied > 0 ? __('flash.budget.copied', ['count' => $copied]) : __('flash.budget.copied_none')
        );
    }

    public function storeItem(StoreBudgetItemRequest $storeBudgetItemRequest, Wallet $wallet): RedirectResponse
    {
        $this->authorize(PolicyAction::View->value, $wallet);

        $month = $this->budgetService->getMonthFromRequest($storeBudgetItemRequest->all());
        $budget = $this->budgetService->getOrCreate($wallet, $month);
        $data = $this->budgetService->filterPlanRestrictedData($storeBudgetItemRequest->user(), $storeBudgetItemRequest->validated());

        try {
            $this->budgetService->addItem($budget, $data);
        } catch (InvalidArgumentException) {
            return redirect()->back()->withErrors(['category_id' => __('budgets.errors.categoryUsed')]);
        }

        return redirect()->back()->with('success', __('flash.budget.item_added'));
    }

    public function updateItem(UpdateBudgetItemRequest $updateBudgetItemRequest, Wallet $wallet, BudgetItem $item): RedirectResponse
    {
        $this->authorize(PolicyAction::View->value, $wallet);
        abort_if($item->budget()->value('wallet_id') !== $wallet->id, HttpStatus::Forbidden->value);
        abort_if($item->category?->is_system, HttpStatus::Forbidden->value);

        $data = $this->budgetService->filterPlanRestrictedData($updateBudgetItemRequest->user(), $updateBudgetItemRequest->validated());

        try {
            $this->budgetService->updateItem($item, $data);
        } catch (InvalidArgumentException) {
            return redirect()->back()->withErrors(['category_id' => __('budgets.errors.categoryUsed')]);
        }

        return redirect()->back()->with('success', __('flash.budget.item_updated'));
    }

    public function destroyItem(Request $request, Wallet $wallet, BudgetItem $item): RedirectResponse
    {
        $this->authorize(PolicyAction::View->value, $wallet);
        abort_if($item->budget()->value('wallet_id') !== $wallet->id, HttpStatus::Forbidden->value);
        abort_if($item->category?->is_system, HttpStatus::Forbidden->value);

        $this->budgetService->deleteItem($item);

        return redirect()->back()->with('success', __('flash.budget.item_deleted'));
    }

    public function reorderItems(ReorderRequest $reorderRequest, Wallet $wallet): RedirectResponse
    {
        $this->authorize(PolicyAction::View->value, $wallet);
        abort_if(! $this->planService->canEditBudget($reorderRequest->user()), HttpStatus::Forbidden->value);

        $this->budgetService->reorderItems($wallet, $reorderRequest->validated()['ids']);

        return redirect()->back();
    }

    public function duplicateItem(Request $request, Wallet $wallet, BudgetItem $item): RedirectResponse
    {
        $this->authorize(PolicyAction::View->value, $wallet);
        abort_if(! $this->planService->canEditBudget($request->user()), HttpStatus::Forbidden->value);
        abort_if($item->budget()->value('wallet_id') !== $wallet->id, HttpStatus::Forbidden->value);
        abort_if($item->category?->is_system, HttpStatus::Forbidden->value);

        $this->budgetService->duplicateItem($item);

        return redirect()->back()->with('success', __('flash.budget.item_duplicated'));
    }

    public function copyRepeat(Request $request, Wallet $wallet): RedirectResponse
    {
        $this->authorize(PolicyAction::View->value, $wallet);
        abort_if(! $this->planService->canEditBudget($request->user()), HttpStatus::Forbidden->value);

        $month = $this->budgetService->getMonthFromRequest($request->all());
        $budget = $this->budgetService->getOrCreate($wallet, $month);
        $copied = $this->budgetService->copyRepeatFromPreviousMonth($budget, $month, $request->input('item_ids', []));

        return redirect()->back()->with(
            'success',
            $copied > 0 ? __('flash.budget.repeat_copied', ['count' => $copied]) : __('flash.budget.repeat_copied_none')
        );
    }

    public function updateNotes(UpdateBudgetNotesRequest $updateBudgetNotesRequest, Wallet $wallet): RedirectResponse
    {
        $this->authorize(PolicyAction::View->value, $wallet);

        $validated = $updateBudgetNotesRequest->validated();
        $month = $this->budgetService->parseMonth($validated['month']);
        $budget = $this->budgetService->getOrCreate($wallet, $month);
        $this->budgetService->updateNotes($budget, $validated['notes']);

        return redirect()->back();
    }

    public function quickStart(QuickStartBudgetRequest $quickStartBudgetRequest, Wallet $wallet): RedirectResponse
    {
        $this->authorize(PolicyAction::View->value, $wallet);

        $validated = $quickStartBudgetRequest->validated();
        $month = $this->budgetService->parseMonth($validated['month']);
        $budget = $this->budgetService->getOrCreate($wallet, $month);
        $count = $this->budgetService->quickStart($budget, $quickStartBudgetRequest->user(), $validated['suggestions'], $wallet);

        return redirect()->back()->with(
            'success',
            $count === 1 ? __('flash.budget.item_added') : __('flash.budget.items_added', ['count' => $count])
        );
    }

    public function clearItems(ClearBudgetItemsRequest $clearBudgetItemsRequest, Wallet $wallet): RedirectResponse
    {
        $this->authorize(PolicyAction::View->value, $wallet);

        $validated = $clearBudgetItemsRequest->validated();
        $month = $this->budgetService->parseMonth($validated['month']);
        $budget = $this->budgetService->getOrCreate($wallet, $month);
        $count = $this->budgetService->clearItems($budget);

        return redirect()->back()->with('success', __('flash.budget.cleared', ['count' => $count]));
    }

    public function yearView(Request $request, Wallet $wallet): Response
    {
        $this->authorize(PolicyAction::View->value, $wallet);

        $year = $this->budgetService->getYearFromRequest(['year' => $request->query('year')]);

        return Inertia::render('Budgets/Year', [
            'wallet' => $wallet,
            'year' => $year,
            'prevYear' => $year - 1,
            'nextYear' => $year + 1,
            'months' => $this->budgetService->yearView($wallet, $year),
        ]);
    }

    public function itemTransactions(Wallet $wallet, BudgetItem $item): JsonResponse
    {
        $this->authorize(PolicyAction::View->value, $wallet);
        abort_if($item->budget()->value('wallet_id') !== $wallet->id, HttpStatus::Forbidden->value);

        return response()->json($this->budgetService->itemTransactions($wallet, $item));
    }

    public function unbudgetedTransactions(Request $request, Wallet $wallet): JsonResponse
    {
        $this->authorize(PolicyAction::View->value, $wallet);

        $month = $this->budgetService->getMonthFromRequest(['month' => $request->query('month')]);
        $budget = $this->budgetService->getOrCreate($wallet, $month);
        $transactions = $this->budgetService->getUnbudgetedTransactions($budget);

        return response()->json($transactions);
    }
}
