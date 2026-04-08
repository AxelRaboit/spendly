/**
 * Transaction Deletion
 *
 * Manages transaction deletion with special handling for transfers.
 * Provides confirmation UI state and deletion logic.
 */

import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Composable for transaction deletion
 *
 * Handles both regular transaction deletion and transfer deletion.
 * Transfers have a special delete endpoint.
 *
 * @returns {Object} State and methods for transaction deletion
 */
export function useTransactionDelete() {
    const pendingDeleteTx = ref(null);

    /**
     * Initiate deletion of a transaction
     * Shows confirmation modal
     */
    function deleteTx(tx) {
        pendingDeleteTx.value = tx;
    }

    /**
     * Confirm and execute transaction deletion
     * Handles both regular transactions and transfers
     */
    function confirmDeleteTx() {
        if (!pendingDeleteTx.value) return;

        const tx = pendingDeleteTx.value;

        // Transfers have their own delete endpoint
        if (tx.transfer_id) {
            router.delete(route('transfers.destroy', tx.transfer_id), {
                preserveScroll: true,
                onSuccess: () => {
                    pendingDeleteTx.value = null;
                },
            });
        } else {
            // Regular transaction deletion
            router.delete(`/transactions/${tx.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    pendingDeleteTx.value = null;
                },
            });
        }
    }

    /**
     * Cancel deletion
     */
    function cancelDeleteTx() {
        pendingDeleteTx.value = null;
    }

    return {
        pendingDeleteTx,
        deleteTx,
        confirmDeleteTx,
        cancelDeleteTx,
    };
}
