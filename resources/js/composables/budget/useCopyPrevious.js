import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';

export function useCopyPrevious(walletId, budget, isBudgetEmpty, prevMonth, fmtMonth, t) {
    const showModal = ref(false);

    const modalMessage = computed(() => {
        const key = isBudgetEmpty.value ? 'budgets.confirmCopy' : 'budgets.confirmCopyAppend';
        return t(key, { month: fmtMonth(prevMonth.value) });
    });

    function open() {
        showModal.value = true;
    }

    function confirm(itemIds) {
        showModal.value = false;
        router.post(`/wallets/${walletId.value}/budget/copy-previous`, {
            month: budget.value.month,
            item_ids: itemIds,
        });
    }

    function cancel() {
        showModal.value = false;
    }

    return { showModal, modalMessage, open, confirm, cancel };
}
