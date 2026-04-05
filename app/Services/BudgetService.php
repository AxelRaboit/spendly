<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BudgetSection;
use App\Enums\SystemCategoryKey;
use App\Enums\TransactionType;
use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class BudgetService
{
    /** Returns user categories excluding system ones, ordered by name. */
    public function userCategories(User $user): Collection
    {
        return Category::query()
            ->where('user_id', $user->id)
            ->where('is_system', false)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Get or create a budget for a wallet and month.
     */
    public function getOrCreate(Wallet $wallet, Carbon $month): Budget
    {
        $budget = Budget::firstOrCreate(
            [
                'wallet_id' => $wallet->id,
                'month' => $month->copy()->startOfMonth()->toDateString(),
            ],
            [
                'user_id' => $wallet->user_id,
            ]
        );

        if ($budget->wasRecentlyCreated) {
            $this->seedTransferItem($budget, $wallet, $month);
        }

        return $budget;
    }

    /**
     * Auto-create transfer budget items for wallets that have transfer transactions.
     * - Income side  → "Virement reçu"   (BudgetSection::Income)
     * - Expense side → "Virement effectué" (BudgetSection::Savings)
     * Planned amount is taken from the previous month's actuals.
     */
    private function seedTransferItem(Budget $budget, Wallet $wallet, Carbon $month): void
    {
        // Income side — single shared category
        $this->seedTransferItemForCategory(
            $budget, $wallet, $month,
            Category::where('user_id', $wallet->user_id)
                ->where('system_key', SystemCategoryKey::TransferIncome->value)
                ->first(),
            BudgetSection::Income,
        );

        // Expense side — one category per destination wallet
        $expenseCategories = Category::where('user_id', $wallet->user_id)
            ->where('system_key', 'LIKE', 'transfer_expense_%')
            ->whereHas('transactions', fn ($q) => $q->where('wallet_id', $wallet->id))
            ->get();

        foreach ($expenseCategories as $category) {
            $this->seedTransferItemForCategory($budget, $wallet, $month, $category, BudgetSection::Savings);
        }
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
            'is_recurring' => (bool) ($data['is_recurring'] ?? false),
        ]);
    }

    private function seedTransferItemForCategory(Budget $budget, Wallet $wallet, Carbon $month, ?Category $category, BudgetSection $section): void
    {
        if (! $category instanceof Category) {
            return;
        }

        $hasTransfers = Transaction::where('wallet_id', $wallet->id)
            ->where('category_id', $category->id)
            ->exists();

        if (! $hasTransfers) {
            return;
        }

        $prevMonthActual = (float) Transaction::where('wallet_id', $wallet->id)
            ->where('category_id', $category->id)
            ->whereYear('date', $month->copy()->subMonth()->year)
            ->whereMonth('date', $month->copy()->subMonth()->month)
            ->sum('amount');

        BudgetItem::create([
            'budget_id' => $budget->id,
            'type' => $section->value,
            'label' => $category->name,
            'planned_amount' => $prevMonthActual,
            'category_id' => $category->id,
            'position' => 0,
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
            'is_recurring' => isset($data['is_recurring']) ? (bool) $data['is_recurring'] : $item->is_recurring,
            'type' => $data['type'] ?? $item->type,
        ]);

        return $item;
    }

    /**
     * Copy all items from the previous month's budget into the current one.
     * Returns the number of items copied, or 0 if no previous budget exists.
     */
    public function copyFromPreviousMonth(Budget $current, Carbon $currentMonth, array $itemIds = []): int
    {
        $previousMonth = $currentMonth->copy()->subMonth();

        $previous = Budget::where('wallet_id', $current->wallet_id)
            ->where('month', $previousMonth->startOfMonth()->toDateString())
            ->first();

        if (! $previous) {
            return 0;
        }

        $query = BudgetItem::where('budget_id', $previous->id);
        if ($itemIds !== []) {
            $query->whereIn('id', $itemIds);
        }

        $items = $query->get();

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
            'is_recurring' => $item->is_recurring,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        BudgetItem::insert($rows);

        return count($rows);
    }

    /**
     * Copy only recurring items from the previous month's budget into the current one.
     * Returns the number of items copied, or 0 if no previous budget exists.
     */
    public function copyRecurringFromPreviousMonth(Budget $current, Carbon $currentMonth, array $itemIds = []): int
    {
        $previousMonth = $currentMonth->copy()->subMonth();

        $previous = Budget::where('wallet_id', $current->wallet_id)
            ->where('month', $previousMonth->startOfMonth()->toDateString())
            ->first();

        if (! $previous) {
            return 0;
        }

        $query = BudgetItem::where('budget_id', $previous->id)->where('is_recurring', true);
        if ($itemIds !== []) {
            $query->whereIn('id', $itemIds);
        }

        $items = $query->get();

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
            'is_recurring' => $item->is_recurring,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        BudgetItem::insert($rows);

        return count($rows);
    }

    /**
     * Return items from the previous month's budget for display in the copy modal.
     */
    public function getPreviousMonthItems(Wallet $wallet, Carbon $currentMonth): array
    {
        $previousMonth = $currentMonth->copy()->subMonth();

        $previous = Budget::where('wallet_id', $wallet->id)
            ->where('month', $previousMonth->startOfMonth()->toDateString())
            ->first();

        if (! $previous) {
            return [];
        }

        /** @var Collection<int, BudgetItem> $items */
        $items = BudgetItem::where('budget_id', $previous->id)
            ->with('category')
            ->orderBy('position')
            ->get();

        return $items->map(fn (BudgetItem $item) => [
            'id' => $item->id,
            'type' => $item->type,
            'label' => $item->label,
            'planned_amount' => $item->planned_amount,
            'is_recurring' => $item->is_recurring,
            'category' => $item->category instanceof Category
                ? ['name' => $item->category->name]
                : null,
        ])->all();
    }

    /**
     * Duplicate a budget item within the same budget and type section.
     */
    public function duplicateItem(BudgetItem $item): BudgetItem
    {
        $maxPosition = BudgetItem::where('budget_id', $item->budget_id)
            ->where('type', $item->type)
            ->max('position') ?? -1;

        return BudgetItem::create([
            'budget_id' => $item->budget_id,
            'type' => $item->type,
            'label' => $item->label,
            'planned_amount' => $item->planned_amount,
            'category_id' => $item->category_id,
            'notes' => $item->notes,
            'is_recurring' => $item->is_recurring,
            'position' => $maxPosition + 1,
        ]);
    }

    /**
     * Compute the rolling start balance for a given month.
     */
    public function computeRollingStartBalance(Wallet $wallet, Carbon $month): float
    {
        $startOfMonth = $month->copy()->startOfMonth()->toDateString();

        $incomeValue = TransactionType::Income->value;
        $netFlow = DB::selectOne("
            SELECT COALESCE(SUM(
                CASE WHEN type = '{$incomeValue}' THEN amount ELSE -amount END
            ), 0) AS net
            FROM transactions
            WHERE wallet_id = ?
              AND date < ?
        ", [$wallet->id, $startOfMonth]);

        return (float) $wallet->start_balance + (float) ($netFlow->net ?? 0);
    }

    /**
     * Reorder budget items by updating their position.
     *
     * @param  array<int, int>  $ids
     */
    public function reorderItems(Wallet $wallet, array $ids): void
    {
        foreach ($ids as $position => $id) {
            BudgetItem::where('id', (int) $id)
                ->whereHas('budget', fn ($q) => $q->where('wallet_id', $wallet->id))
                ->update(['position' => $position]);
        }
    }

    /**
     * Get transactions for a budget item in its budget month.
     *
     * @return array<int, array{id: int, date: mixed, description: string|null, amount: float, type: string}>
     */
    public function itemTransactions(Wallet $wallet, BudgetItem $item): array
    {
        if (! $item->category_id) {
            return [];
        }

        $month = Carbon::parse($item->budget()->value('month'));

        return Transaction::where('wallet_id', $wallet->id)
            ->where('category_id', $item->category_id)
            ->whereYear('date', $month->year)
            ->whereMonth('date', $month->month)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get(['id', 'date', 'description', 'amount', 'type'])
            ->map(fn (Transaction $tx) => [
                'id' => $tx->id,
                'date' => $tx->date,
                'description' => $tx->description,
                'amount' => (float) $tx->amount,
                'type' => $tx->type,
            ])
            ->all();
    }

    /**
     * Build the year view data for a wallet and year.
     *
     * @return array<string, array{has_budget: bool, label: string, income_planned?: float, income_actual?: float, expenses_planned?: float, expenses_actual?: float, cash_flow_actual?: float}>
     */
    public function yearView(Wallet $wallet, int $year): array
    {
        $months = [];

        for ($m = 1; $m <= 12; $m++) {
            $month = Carbon::create($year, $m, 1);
            $key = $month->format('Y-m');
            $budget = Budget::where('wallet_id', $wallet->id)
                ->where('month', $month->startOfMonth()->toDateString())
                ->first();

            if ($budget) {
                $result = $this->loadWithActuals($budget);
                $sections = $result['sections'];
                $unbudgeted = $result['unbudgeted'];

                $incomePlanned = array_sum(array_column($sections[BudgetSection::Income->value], 'planned_amount'));
                $incomeActual = array_sum(array_column($sections[BudgetSection::Income->value], 'actual_amount')) + $unbudgeted['income'];
                $expPlanned = 0;
                $expActual = 0;

                foreach ([BudgetSection::Savings, BudgetSection::Bills, BudgetSection::Expenses, BudgetSection::Debt] as $type) {
                    $expPlanned += array_sum(array_column($sections[$type->value], 'planned_amount'));
                    $expActual += array_sum(array_column($sections[$type->value], 'actual_amount'));
                }

                $expActual += $unbudgeted['expenses'];

                $months[$key] = [
                    'has_budget' => true,
                    'label' => $month->locale(App::getLocale())->translatedFormat('F'),
                    'income_planned' => $incomePlanned,
                    'income_actual' => $incomeActual,
                    'expenses_planned' => $expPlanned,
                    'expenses_actual' => $expActual,
                    'cash_flow_actual' => $incomeActual - $expActual,
                ];
            } else {
                $months[$key] = [
                    'has_budget' => false,
                    'label' => $month->locale(App::getLocale())->translatedFormat('F'),
                ];
            }
        }

        return $months;
    }

    /**
     * Load a budget with all its items enriched with actual amounts.
     * Also computes unbudgeted amounts (transactions not covered by any budget item).
     *
     * @return array{sections: array<string, array<int, array<string, mixed>>>, unbudgeted: array{income: float, expenses: float}}
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

        $sections = [];

        foreach (BudgetSection::cases() as $section) {
            $sections[$section->value] = $allItems
                ->where('type', $section->value)
                ->values()
                ->map(fn (BudgetItem $item) => [
                    'id' => $item->id,
                    'type' => $item->type,
                    'label' => $item->label,
                    'planned_amount' => (float) $item->planned_amount,
                    'actual_amount' => $item->category_id ? (float) ($actuals->get($item->category_id) ?? 0) : 0.0,
                    'category_id' => $item->category_id,
                    'notes' => $item->notes,
                    'is_recurring' => (bool) $item->is_recurring,
                    'category' => $item->category instanceof Category
                        ? ['id' => $item->category->id, 'name' => $item->category->name, 'is_system' => (bool) $item->category->is_system]
                        : null,
                    'position' => $item->position,
                ])
                ->all();
        }

        // Transactions not covered by any budget item category
        $budgetedCategoryIds = $allItems->whereNotNull('category_id')->pluck('category_id');

        $unbudgetedQuery = DB::table('transactions')
            ->where('wallet_id', $budget->wallet_id)
            ->whereYear('date', $budget->month->year)
            ->whereMonth('date', $budget->month->month);

        if ($budgetedCategoryIds->isNotEmpty()) {
            $unbudgetedQuery->where(fn ($q) => $q
                ->whereNull('category_id')
                ->orWhereNotIn('category_id', $budgetedCategoryIds)
            );
        }

        $unbudgetedRows = $unbudgetedQuery
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        return [
            'sections' => $sections,
            'unbudgeted' => [
                'income' => (float) ($unbudgetedRows->get(TransactionType::Income->value)->total ?? 0),
                'expenses' => (float) ($unbudgetedRows->get(TransactionType::Expense->value)->total ?? 0),
            ],
        ];
    }
}
