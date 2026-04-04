import { ref } from 'vue';
import axios from 'axios';

export function useItemTransactions(walletId) {
    const open = ref(false);
    const loading = ref(false);
    const transactions = ref([]);
    const currentItem = ref(null);

    async function openPanel(item) {
        currentItem.value = item;
        transactions.value = [];
        open.value = true;
        loading.value = true;

        try {
            const { data } = await axios.get(`/wallets/${walletId.value}/budget/items/${item.id}/transactions`);
            transactions.value = data;
        } finally {
            loading.value = false;
        }
    }

    function closePanel() {
        open.value = false;
        currentItem.value = null;
        transactions.value = [];
    }

    return { open, loading, transactions, currentItem, openPanel, closePanel };
}
