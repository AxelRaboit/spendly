import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

export function useBudgetNotes(walletId, budget) {
    const budgetNotesOpen = ref(!!budget.value.notes);
    const budgetNotesText = ref(budget.value.notes ?? '');

    function saveBudgetNotes() {
        if (budgetNotesText.value === (budget.value.notes ?? '')) return;
        router.patch(
            `/wallets/${walletId.value}/budget/notes`,
            { month: budget.value.month, notes: budgetNotesText.value },
            { preserveScroll: true }
        );
    }

    return { budgetNotesOpen, budgetNotesText, saveBudgetNotes };
}
