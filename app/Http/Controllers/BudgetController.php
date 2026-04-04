<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreBudgetItemRequest;
use App\Http\Requests\UpdateBudgetItemRequest;
use App\Models\BudgetItem;
use App\Models\Category;
use App\Models\Wallet;
use App\Services\BudgetService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function show(Request $request, Wallet $wallet, BudgetService $budgetService): Response
    {
        $this->authorize('view', $wallet);

        $monthParam = $request->query('month');
        $month = $monthParam ? Carbon::createFromFormat('Y-m', $monthParam) : Carbon::now();

        $budget = $budgetService->getOrCreate($wallet, $month);
        $sections = $budgetService->loadWithActuals($budget);

        $categories = Category::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get(['id', 'name']);

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
            'categories' => $categories,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'startBalance' => $budgetService->computeRollingStartBalance($wallet, $month),
        ]);
    }

    public function copyFromPrevious(Request $request, Wallet $wallet, BudgetService $budgetService): RedirectResponse
    {
        $this->authorize('view', $wallet);

        $monthParam = $request->input('month');
        $month = $monthParam ? Carbon::createFromFormat('Y-m', $monthParam) : Carbon::now();

        $budget = $budgetService->getOrCreate($wallet, $month);
        $copied = $budgetService->copyFromPreviousMonth($budget, $month);

        return redirect()->back()->with(
            'success',
            $copied > 0 ? $copied.' lignes copiées depuis le mois précédent.' : 'Aucune ligne trouvée dans le mois précédent.'
        );
    }

    public function storeItem(StoreBudgetItemRequest $request, Wallet $wallet, BudgetService $budgetService): RedirectResponse
    {
        $this->authorize('view', $wallet);

        $monthParam = $request->input('month');
        $month = $monthParam ? Carbon::createFromFormat('Y-m', $monthParam) : Carbon::now();

        $budget = $budgetService->getOrCreate($wallet, $month);
        $budgetService->addItem($budget, $request->validated());

        return redirect()->back()->with('success', 'Ligne ajoutée.');
    }

    public function updateItem(UpdateBudgetItemRequest $request, Wallet $wallet, BudgetItem $item, BudgetService $budgetService): RedirectResponse
    {
        $this->authorize('view', $wallet);
        abort_if($item->budget()->value('wallet_id') !== $wallet->id, 403);

        $budgetService->updateItem($item, $request->validated());

        return redirect()->back()->with('success', 'Ligne mise à jour.');
    }

    public function destroyItem(Wallet $wallet, BudgetItem $item): RedirectResponse
    {
        $this->authorize('view', $wallet);
        abort_if($item->budget()->value('wallet_id') !== $wallet->id, 403);

        $item->delete();

        return redirect()->back()->with('success', 'Ligne supprimée.');
    }

    public function reorderItems(Request $request, Wallet $wallet, BudgetService $budgetService): RedirectResponse
    {
        $this->authorize('view', $wallet);

        $ids = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer'])['ids'];
        $budgetService->reorderItems($wallet, $ids);

        return redirect()->back();
    }

    public function duplicateItem(Wallet $wallet, BudgetItem $item, BudgetService $budgetService): RedirectResponse
    {
        $this->authorize('view', $wallet);
        abort_if($item->budget()->value('wallet_id') !== $wallet->id, 403);

        $budgetService->duplicateItem($item);

        return redirect()->back()->with('success', 'Ligne dupliquée.');
    }

    public function copyRecurring(Request $request, Wallet $wallet, BudgetService $budgetService): RedirectResponse
    {
        $this->authorize('view', $wallet);

        $monthParam = $request->input('month');
        $month = $monthParam ? Carbon::createFromFormat('Y-m', $monthParam) : Carbon::now();
        $budget = $budgetService->getOrCreate($wallet, $month);
        $copied = $budgetService->copyRecurringFromPreviousMonth($budget, $month);

        return redirect()->back()->with(
            'success',
            $copied > 0 ? $copied.' lignes récurrentes copiées.' : 'Aucune ligne récurrente dans le mois précédent.'
        );
    }

    public function updateNotes(Request $request, Wallet $wallet, BudgetService $budgetService): RedirectResponse
    {
        $this->authorize('view', $wallet);

        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $month = Carbon::createFromFormat('Y-m', $validated['month']);
        $budget = $budgetService->getOrCreate($wallet, $month);
        $budget->update(['notes' => $validated['notes']]);

        return redirect()->back();
    }

    public function yearView(Request $request, Wallet $wallet, BudgetService $budgetService): Response
    {
        $this->authorize('view', $wallet);

        $year = (int) $request->query('year', Carbon::now()->year);

        return Inertia::render('Budgets/Year', [
            'wallet' => $wallet,
            'year' => $year,
            'prevYear' => $year - 1,
            'nextYear' => $year + 1,
            'months' => $budgetService->yearView($wallet, $year),
        ]);
    }

    public function itemTransactions(Request $request, Wallet $wallet, BudgetItem $item, BudgetService $budgetService): JsonResponse
    {
        $this->authorize('view', $wallet);
        abort_if($item->budget()->value('wallet_id') !== $wallet->id, 403);

        return response()->json($budgetService->itemTransactions($wallet, $item));
    }
}
