<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Category;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BudgetService
{
    /**
     * Get or create a budget for a wallet and month.
     */
    public function getOrCreate(Wallet $wallet, Carbon $month): Budget
    {
        return Budget::firstOrCreate(
            [
                'wallet_id' => $wallet->id,
                'month' => $month->startOfMonth()->toDateString(),
            ],
            [
                'user_id' => $wallet->user_id,
            ]
        );
    }

    /**
     * Add an item to a budget.
     */
    public function addItem(Budget $budget, array $data): BudgetItem
    {
        $position = BudgetItem::where('budget_id', $budget->id)
            ->where('type', $data['type'])
            ->max('position') ?? -1;

        return BudgetItem::create([
            'budget_id' => $budget->id,
            'type' => $data['type'],
            'label' => $data['label'],
            'planned_amount' => $data['planned_amount'],
            'category_id' => $data['category_id'] ?? null,
            'position' => $position + 1,
        ]);
    }

    /**
     * Update a budget item.
     */
    public function updateItem(BudgetItem $item, array $data): BudgetItem
    {
        $item->update([
            'label' => $data['label'],
            'planned_amount' => $data['planned_amount'],
            'category_id' => $data['category_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return $item;
    }

    /**
     * Copy all items from the previous month's budget into the current one.
     * Returns the number of items copied, or 0 if no previous budget exists.
     */
    public function copyFromPreviousMonth(Budget $current, Carbon $currentMonth): int
    {
        $previousMonth = $currentMonth->copy()->subMonth();

        $previous = Budget::where('wallet_id', $current->wallet_id)
            ->where('month', $previousMonth->startOfMonth()->toDateString())
            ->first();

        if (! $previous) {
            return 0;
        }

        $items = BudgetItem::where('budget_id', $previous->id)->get();

        if ($items->isEmpty()) {
            return 0;
        }

        $now = now();
        $rows = $items->map(fn (BudgetItem $item) => [
            'budget_id' => $current->id,
            'type' => $item->type,
            'label' => $item->label,
            'planned_amount' => $item->planned_amount,
            'category_id' => $item->category_id,
            'position' => $item->position,
            'notes' => $item->notes,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        BudgetItem::insert($rows);

        return count($rows);
    }

    /**
     * Compute the rolling start balance for a given month.
     */
    public function computeRollingStartBalance(Wallet $wallet, Carbon $month): float
    {
        $startOfMonth = $month->copy()->startOfMonth()->toDateString();

        $netFlow = DB::selectOne("
            SELECT COALESCE(SUM(
                CASE WHEN type = 'income' THEN amount ELSE -amount END
            ), 0) AS net
            FROM transactions
            WHERE wallet_id = ?
              AND date < ?
        ", [$wallet->id, $startOfMonth]);

        return (float) $wallet->start_balance + (float) ($netFlow->net ?? 0);
    }

    /**
     * Load a budget with all its items enriched with actual amounts.
     * Actuals are fetched in a single query grouped by category.
     */
    public function loadWithActuals(Budget $budget): array
    {
        $actuals = DB::table('transactions')
            ->where('wallet_id', $budget->wallet_id)
            ->whereYear('date', $budget->month->year)
            ->whereMonth('date', $budget->month->month)
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->selectRaw('category_id, SUM(amount) as total')
            ->pluck('total', 'category_id');

        /** @var Collection<int, BudgetItem> $allItems */
        $allItems = BudgetItem::with('category')
            ->where('budget_id', $budget->id)
            ->orderBy('position')
            ->get();

        $sections = ['income', 'savings', 'bills', 'expenses', 'debt'];
        $result = [];

        foreach ($sections as $section) {
            $result[$section] = $allItems
                ->where('type', $section)
                ->values()
                ->map(fn (BudgetItem $item) => [
                    'id' => $item->id,
                    'type' => $item->type,
                    'label' => $item->label,
                    'planned_amount' => (float) $item->planned_amount,
                    'actual_amount' => $item->category_id ? (float) ($actuals->get($item->category_id) ?? 0) : 0.0,
                    'category_id' => $item->category_id,
                    'notes' => $item->notes,
                    'category' => $item->category instanceof Category
                        ? ['id' => $item->category->id, 'name' => $item->category->name]
                        : null,
                    'position' => $item->position,
                ])
                ->all();
        }

        return $result;
    }
}
