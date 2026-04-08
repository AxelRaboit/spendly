import { computed } from 'vue';
import { BudgetSection } from '@/enums/BudgetSection';

const ALL_SECTIONS     = Object.values(BudgetSection);
const EXPENSE_SECTIONS = [BudgetSection.Savings, BudgetSection.Bills, BudgetSection.Expenses, BudgetSection.Debt];

/**
 * Budget Totals & KPIs Calculation
 *
 * Computes aggregated budget metrics from detailed sections.
 * Calculates totals (planned, actual, carried-over, available) per section,
 * plus derived KPIs like cash flow, savings rate, projected expenses, etc.
 *
 * @param {Ref<Object>} sections - Reactive sections object with keys (income, savings, bills, expenses, debt)
 *                                 Each section contains budget items with planned/actual/carried amounts
 * @param {Ref<number>} startBalance - Account balance at the start of the month
 * @param {Ref<Object>} budget - Budget metadata object (optional)
 * @param {Ref<Object>} unbudgeted - Unbudgeted transactions (income/expenses) (optional)
 *
 * @returns {Object}
 *   - totals: Object with per-section breakdown (planned, actual, carried, available)
 *   - totalCarriedOver: Total amount carried from previous month
 *   - totalIncome: {planned, actual} aggregate
 *   - totalExpenses: {planned, actual} aggregate (savings, bills, expenses, debt)
 *   - cashFlow: Income minus expenses
 *   - leftToSpend: Available budget remaining
 *   - savingsRate: Percentage of income going to savings/investments
 *   - isCurrentMonth: True if budget is for current month
 *   - projectedExpenses: Estimated expenses based on actual-to-planned ratio
 */
export function useBudgetTotals(sections, startBalance, budget = null, unbudgeted = null) {
    const totals = computed(() => {
        const result = {};
        for (const [key, items] of Object.entries(sections.value)) {
            result[key] = {
                planned:   items.reduce((s, i) => s + i.planned_amount, 0),
                carried:   items.reduce((s, i) => s + (i.carried_over ?? 0), 0),
                actual:    items.reduce((s, i) => s + i.actual_amount, 0),
                available: items.reduce((s, i) => s + (i.available ?? 0), 0),
            };
        }
        return result;
    });

    const totalCarriedOver = computed(() =>
        ALL_SECTIONS.reduce((s, k) => s + (totals.value[k]?.carried ?? 0), 0)
    );

    const totalIncome = computed(() => ({
        planned: totals.value[BudgetSection.Income]?.planned ?? 0,
        actual:  (totals.value[BudgetSection.Income]?.actual ?? 0) + (unbudgeted?.value?.income ?? 0),
    }));

    const totalExpenses = computed(() => ({
        planned: EXPENSE_SECTIONS.reduce((s, k) => s + (totals.value[k]?.planned ?? 0), 0),
        actual:  EXPENSE_SECTIONS.reduce((s, k) => s + (totals.value[k]?.actual ?? 0), 0) + (unbudgeted?.value?.expenses ?? 0),
    }));

    const cashFlow = computed(() => ({
        planned: totalIncome.value.planned - totalExpenses.value.planned,
        actual:  totalIncome.value.actual  - totalExpenses.value.actual,
    }));

    const leftToSpend = computed(() => ({
        planned: startBalance.value + cashFlow.value.planned + totalCarriedOver.value,
        actual:  startBalance.value + cashFlow.value.actual  + totalCarriedOver.value,
    }));

    const savingsRate = computed(() => {
        const income  = totalIncome.value.actual;
        const savings = totals.value[BudgetSection.Savings]?.actual ?? 0;
        if (!income) return null;
        return Math.round((savings / income) * 100);
    });

    const isCurrentMonth = computed(() =>
        budget ? budget.value.month === new Date().toISOString().slice(0, 7) : false
    );

    const projectedExpenses = computed(() => {
        if (!isCurrentMonth.value) return null;
        const today        = new Date();
        const daysElapsed  = today.getDate();
        const daysInMonth  = new Date(today.getFullYear(), today.getMonth() + 1, 0).getDate();
        return (totalExpenses.value.actual / daysElapsed) * daysInMonth;
    });

    return {
        totals,
        totalCarriedOver,
        totalIncome,
        totalExpenses,
        cashFlow,
        leftToSpend,
        savingsRate,
        isCurrentMonth,
        projectedExpenses,
    };
}
