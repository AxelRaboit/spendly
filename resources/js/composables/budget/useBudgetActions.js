import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

export function useBudgetActions(walletId, budget, sections, prevMonth, fmtMonth, t) {
    const isBudgetEmpty = computed(() => Object.values(sections.value).every((arr) => arr.length === 0));

    function copyFromPrevious() {
        const key = isBudgetEmpty.value ? 'budgets.confirmCopy' : 'budgets.confirmCopyAppend';
        if (!confirm(t(key, { month: fmtMonth(prevMonth.value) }))) return;
        router.post(`/wallets/${walletId.value}/budget/copy-previous`, { month: budget.value.month });
    }

    function copyRecurring() {
        router.post(
            `/wallets/${walletId.value}/budget/copy-recurring`,
            { month: budget.value.month },
            { preserveScroll: true }
        );
    }

    return { isBudgetEmpty, copyFromPrevious, copyRecurring };
}
