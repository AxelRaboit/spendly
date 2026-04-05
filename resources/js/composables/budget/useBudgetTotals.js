import { computed } from 'vue';

export function useBudgetTotals(sections, startBalance, budget = null, unbudgeted = null) {
    const totals = computed(() => {
        const result = {};
        for (const [key, items] of Object.entries(sections.value)) {
            result[key] = {
                planned: items.reduce((s, i) => s + i.planned_amount, 0),
                actual: items.reduce((s, i) => s + i.actual_amount, 0),
            };
        }
        return result;
    });

    const totalIncome = computed(() => ({
        planned: totals.value.income?.planned ?? 0,
        actual: (totals.value.income?.actual ?? 0) + (unbudgeted?.value?.income ?? 0),
    }));

    const totalExpenses = computed(() => {
        const keys = ['savings', 'bills', 'expenses', 'debt'];
        return {
            planned: keys.reduce((s, k) => s + (totals.value[k]?.planned ?? 0), 0),
            actual: keys.reduce((s, k) => s + (totals.value[k]?.actual ?? 0), 0) + (unbudgeted?.value?.expenses ?? 0),
        };
    });

    const cashFlow = computed(() => ({
        planned: totalIncome.value.planned - totalExpenses.value.planned,
        actual: totalIncome.value.actual - totalExpenses.value.actual,
    }));

    const leftToSpend = computed(() => ({
        planned: startBalance.value + cashFlow.value.planned,
        actual: startBalance.value + cashFlow.value.actual,
    }));

    const savingsRate = computed(() => {
        const income = totalIncome.value.actual;
        const savings = totals.value.savings?.actual ?? 0;
        if (!income) return null;
        return Math.round((savings / income) * 100);
    });

    const isCurrentMonth = computed(() =>
        budget ? budget.value.month === new Date().toISOString().slice(0, 7) : false
    );

    const projectedExpenses = computed(() => {
        if (!isCurrentMonth.value) return null;
        const today = new Date();
        const daysElapsed = today.getDate();
        const daysInMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0).getDate();
        return (totalExpenses.value.actual / daysElapsed) * daysInMonth;
    });

    return {
        totals,
        totalIncome,
        totalExpenses,
        cashFlow,
        leftToSpend,
        savingsRate,
        isCurrentMonth,
        projectedExpenses,
    };
}
