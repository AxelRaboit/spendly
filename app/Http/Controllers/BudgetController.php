<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreBudgetItemRequest;
use App\Http\Requests\UpdateBudgetItemRequest;
use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Category;
use App\Models\Transaction;
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
                'id'          => $budget->id,
                'month'       => $budget->month->format('Y-m'),
                'month_label' => $budget->month->locale(App::getLocale())->translatedFormat('F Y'),
                'notes'       => $budget->notes,
            ],
            'sections'     => $sections,
            'categories'   => $categories,
            'prevMonth'    => $prevMonth,
            'nextMonth'    => $nextMonth,
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
            $copied > 0 ? $copied . ' lignes copiées depuis le mois précédent.' : 'Aucune ligne trouvée dans le mois précédent.'
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

    public function reorderItems(Request $request, Wallet $wallet): RedirectResponse
    {
        $this->authorize('view', $wallet);

        $ids = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer'])['ids'];

        foreach ($ids as $position => $id) {
            BudgetItem::where('id', (int) $id)
                ->whereHas('budget', fn ($q) => $q->where('wallet_id', $wallet->id))
                ->update(['position' => $position]);
        }

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
        $month      = $monthParam ? Carbon::createFromFormat('Y-m', $monthParam) : Carbon::now();
        $budget     = $budgetService->getOrCreate($wallet, $month);
        $copied     = $budgetService->copyRecurringFromPreviousMonth($budget, $month);

        return redirect()->back()->with(
            'success',
            $copied > 0 ? $copied . ' lignes récurrentes copiées.' : 'Aucune ligne récurrente dans le mois précédent.'
        );
    }

    public function updateNotes(Request $request, Wallet $wallet, BudgetService $budgetService): RedirectResponse
    {
        $this->authorize('view', $wallet);

        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $month  = Carbon::createFromFormat('Y-m', $validated['month']);
        $budget = $budgetService->getOrCreate($wallet, $month);
        $budget->update(['notes' => $validated['notes']]);

        return redirect()->back();
    }

    public function yearView(Request $request, Wallet $wallet, BudgetService $budgetService): Response
    {
        $this->authorize('view', $wallet);

        $year = (int) $request->query('year', Carbon::now()->year);

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $month  = Carbon::create($year, $m, 1);
            $key    = $month->format('Y-m');
            $budget = Budget::where('wallet_id', $wallet->id)
                ->where('month', $month->startOfMonth()->toDateString())
                ->first();

            if ($budget) {
                $sections = $budgetService->loadWithActuals($budget);
                $incomePlanned  = array_sum(array_column($sections['income'],   'planned_amount'));
                $incomeActual   = array_sum(array_column($sections['income'],   'actual_amount'));
                $expPlanned     = 0;
                $expActual      = 0;
                foreach (['savings', 'bills', 'expenses', 'debt'] as $type) {
                    $expPlanned += array_sum(array_column($sections[$type], 'planned_amount'));
                    $expActual  += array_sum(array_column($sections[$type], 'actual_amount'));
                }

                $months[$key] = [
                    'has_budget'       => true,
                    'label'            => $month->locale(App::getLocale())->translatedFormat('F'),
                    'income_planned'   => $incomePlanned,
                    'income_actual'    => $incomeActual,
                    'expenses_planned' => $expPlanned,
                    'expenses_actual'  => $expActual,
                    'cash_flow_actual' => $incomeActual - $expActual,
                ];
            } else {
                $months[$key] = [
                    'has_budget' => false,
                    'label'      => $month->locale(App::getLocale())->translatedFormat('F'),
                ];
            }
        }

        return Inertia::render('Budgets/Year', [
            'wallet'   => $wallet,
            'year'     => $year,
            'prevYear' => $year - 1,
            'nextYear' => $year + 1,
            'months'   => $months,
        ]);
    }

    public function itemTransactions(Request $request, Wallet $wallet, BudgetItem $item): JsonResponse
    {
        $this->authorize('view', $wallet);
        abort_if($item->budget()->value('wallet_id') !== $wallet->id, 403);

        if (! $item->category_id) {
            return response()->json([]);
        }

        $month = Carbon::createFromFormat('Y-m-d', $item->budget()->value('month'));

        $transactions = Transaction::where('wallet_id', $wallet->id)
            ->where('category_id', $item->category_id)
            ->whereYear('date', $month->year)
            ->whereMonth('date', $month->month)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get(['id', 'date', 'description', 'amount', 'type'])
            ->map(fn (Transaction $tx) => [
                'id'          => $tx->id,
                'date'        => $tx->date,
                'description' => $tx->description,
                'amount'      => (float) $tx->amount,
                'type'        => $tx->type,
            ]);

        return response()->json($transactions);
    }
}
