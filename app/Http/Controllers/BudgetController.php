<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\HttpStatus;
use App\Http\Requests\ReorderRequest;
use App\Http\Requests\StoreBudgetItemRequest;
use App\Http\Requests\UpdateBudgetItemRequest;
use App\Http\Requests\UpdateBudgetNotesRequest;
use App\Models\BudgetItem;
use App\Models\Wallet;
use App\Services\BudgetService;
use App\Services\GoalService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function __construct(
        private readonly BudgetService $budgetService,
        private readonly GoalService $goalService,
    ) {}

    public function show(Request $request, Wallet $wallet): Response
    {
        $this->authorize('view', $wallet);

        $monthParam = $request->query('month');
        $month = $monthParam ? Carbon::createFromFormat('Y-m', $monthParam) : Carbon::now();

        $budget = $this->budgetService->getOrCreate($wallet, $month);
        ['sections' => $sections, 'unbudgeted' => $unbudgeted] = $this->budgetService->loadWithActuals($budget);

        $prevMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');

        return Inertia::render('Budgets/Show', [
            'wallet' => $wallet,
            'budget' => [
                'id' => $budget->id,
                'month' => $budget->month->format('Y-m'),
                'month_label' => $budget->month->locale(App::getLocale())->translatedFormat('F Y'),
                'notes' => $budget->notes,
            ],
            'sections' => $sections,
            'unbudgeted' => $unbudgeted,
            'categories' => $this->budgetService->userCategories($request->user()),
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'startBalance' => $this->budgetService->computeRollingStartBalance($wallet, $month),
            'flashCategory' => $request->query('flash_category') ? (int) $request->query('flash_category') : null,
            'prevItems' => $this->budgetService->getPreviousMonthItems($wallet, $month),
            'goals' => $this->goalService->listForWallet($request->user(), $wallet),
        ]);
    }

    public function copyFromPrevious(Request $request, Wallet $wallet): RedirectResponse
    {
        $this->authorize('view', $wallet);

        $monthParam = $request->input('month');
        $month = $monthParam ? Carbon::createFromFormat('Y-m', $monthParam) : Carbon::now();

        $budget = $this->budgetService->getOrCreate($wallet, $month);
        $copied = $this->budgetService->copyFromPreviousMonth($budget, $month, $request->input('item_ids', []));

        return redirect()->back()->with(
            'success',
            $copied > 0 ? $copied.' lignes copiées depuis le mois précédent.' : 'Aucune ligne trouvée dans le mois précédent.'
        );
    }

    public function storeItem(StoreBudgetItemRequest $request, Wallet $wallet): RedirectResponse
    {
        $this->authorize('view', $wallet);

        $monthParam = $request->input('month');
        $month = $monthParam ? Carbon::createFromFormat('Y-m', $monthParam) : Carbon::now();

        $budget = $this->budgetService->getOrCreate($wallet, $month);
        $this->budgetService->addItem($budget, $request->validated());

        return redirect()->back()->with('success', 'Ligne ajoutée.');
    }

    public function updateItem(UpdateBudgetItemRequest $request, Wallet $wallet, BudgetItem $item): RedirectResponse
    {
        $this->authorize('view', $wallet);
        abort_if($item->budget()->value('wallet_id') !== $wallet->id, HttpStatus::Forbidden->value);
        abort_if($item->category?->is_system, HttpStatus::Forbidden->value);

        $this->budgetService->updateItem($item, $request->validated());

        return redirect()->back()->with('success', 'Ligne mise à jour.');
    }

    public function destroyItem(Wallet $wallet, BudgetItem $item): RedirectResponse
    {
        $this->authorize('view', $wallet);
        abort_if($item->budget()->value('wallet_id') !== $wallet->id, HttpStatus::Forbidden->value);
        abort_if($item->category?->is_system, HttpStatus::Forbidden->value);

        $item->delete();

        return redirect()->back()->with('success', 'Ligne supprimée.');
    }

    public function reorderItems(ReorderRequest $request, Wallet $wallet): RedirectResponse
    {
        $this->authorize('view', $wallet);

        $this->budgetService->reorderItems($wallet, $request->validated()['ids']);

        return redirect()->back();
    }

    public function duplicateItem(Wallet $wallet, BudgetItem $item): RedirectResponse
    {
        $this->authorize('view', $wallet);
        abort_if($item->budget()->value('wallet_id') !== $wallet->id, HttpStatus::Forbidden->value);
        abort_if($item->category?->is_system, HttpStatus::Forbidden->value);

        $this->budgetService->duplicateItem($item);

        return redirect()->back()->with('success', 'Ligne dupliquée.');
    }

    public function copyRecurring(Request $request, Wallet $wallet): RedirectResponse
    {
        $this->authorize('view', $wallet);

        $monthParam = $request->input('month');
        $month = $monthParam ? Carbon::createFromFormat('Y-m', $monthParam) : Carbon::now();
        $budget = $this->budgetService->getOrCreate($wallet, $month);
        $copied = $this->budgetService->copyRecurringFromPreviousMonth($budget, $month, $request->input('item_ids', []));

        return redirect()->back()->with(
            'success',
            $copied > 0 ? $copied.' lignes récurrentes copiées.' : 'Aucune ligne récurrente dans le mois précédent.'
        );
    }

    public function updateNotes(UpdateBudgetNotesRequest $request, Wallet $wallet): RedirectResponse
    {
        $this->authorize('view', $wallet);

        $validated = $request->validated();
        $month = Carbon::createFromFormat('Y-m', $validated['month']);
        $budget = $this->budgetService->getOrCreate($wallet, $month);
        $budget->update(['notes' => $validated['notes']]);

        return redirect()->back();
    }

    public function yearView(Request $request, Wallet $wallet): Response
    {
        $this->authorize('view', $wallet);

        $year = (int) $request->query('year', Carbon::now()->year);

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
        $this->authorize('view', $wallet);
        abort_if($item->budget()->value('wallet_id') !== $wallet->id, HttpStatus::Forbidden->value);

        return response()->json($this->budgetService->itemTransactions($wallet, $item));
    }
}
