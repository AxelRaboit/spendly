import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

export function useCopyRepeat(walletId, budget) {
    const showModal = ref(false);

    function open() {
        showModal.value = true;
    }

    function confirm(itemIds) {
        showModal.value = false;
        router.post(
            `/wallets/${walletId.value}/budget/copy-repeat`,
            { month: budget.value.month, item_ids: itemIds },
            { preserveScroll: true }
        );
    }

    function cancel() {
        showModal.value = false;
    }

    return { showModal, open, confirm, cancel };
}
