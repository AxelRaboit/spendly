<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreBudgetItemRequest;
use App\Http\Requests\UpdateBudgetItemRequest;
use App\Models\BudgetItem;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\BudgetService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                'month_label' => $budget->month->locale('fr')->translatedFormat('F Y'),
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

        if (BudgetItem::where('budget_id', $budget->id)->exists()) {
            return redirect()->back()->with('error', 'Le budget contient déjà des lignes.');
        }

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
