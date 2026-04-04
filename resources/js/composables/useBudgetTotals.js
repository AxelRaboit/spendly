import { computed } from 'vue';

export function useBudgetTotals(sections, startBalance) {
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

    const totalIncome = computed(() => totals.value.income ?? { planned: 0, actual: 0 });

    const totalExpenses = computed(() => {
        const keys = ['savings', 'bills', 'expenses', 'debt'];
        return {
            planned: keys.reduce((s, k) => s + (totals.value[k]?.planned ?? 0), 0),
            actual: keys.reduce((s, k) => s + (totals.value[k]?.actual ?? 0), 0),
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

    return { totals, totalIncome, totalExpenses, cashFlow, leftToSpend };
}
