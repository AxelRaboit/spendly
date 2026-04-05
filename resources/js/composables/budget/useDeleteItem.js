import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

export function useDeleteItem(walletId) {
    const pendingDeleteItem = ref(null);
    const deletingItem = ref(null);
    let deleteTimer = null;

    function requestDelete(item) {
        if (deletingItem.value) {
            clearTimeout(deleteTimer);
            _executeDelete(deletingItem.value.id);
        }
        pendingDeleteItem.value = item;
    }

    function confirmDelete() {
        if (!pendingDeleteItem.value) return;
        const item = pendingDeleteItem.value;
        pendingDeleteItem.value = null;
        deletingItem.value = item;
        deleteTimer = setTimeout(() => _executeDelete(item.id), 5000);
    }

    function _executeDelete(id) {
        deletingItem.value = null;
        router.delete(`/wallets/${walletId.value}/budget/items/${id}`, { preserveScroll: true });
    }

    function undoDelete() {
        clearTimeout(deleteTimer);
        deletingItem.value = null;
    }

    function cancelDelete() {
        pendingDeleteItem.value = null;
    }

    return { pendingDeleteItem, deletingItem, requestDelete, confirmDelete, undoDelete, cancelDelete };
}
