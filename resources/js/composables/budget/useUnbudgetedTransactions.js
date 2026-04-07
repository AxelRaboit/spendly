import { ref, computed } from 'vue';
import axios from 'axios';

export function useUnbudgetedTransactions(walletId) {
    const open = ref(false);
    const loading = ref(false);
    const transactions = ref([]);
    const filterType = ref(null);

    async function openPanel(txType) {
        filterType.value = txType;
        transactions.value = [];
        open.value = true;
        loading.value = true;

        try {
            const month = new URLSearchParams(window.location.search).get('month');
            const url = `/wallets/${walletId.value}/budget/unbudgeted-transactions${month ? `?month=${month}` : ''}`;
            const { data } = await axios.get(url);
            transactions.value = data;
        } finally {
            loading.value = false;
        }
    }

    function closePanel() {
        open.value = false;
        filterType.value = null;
        transactions.value = [];
    }

    const filteredTransactions = computed(() =>
        filterType.value ? transactions.value.filter((tx) => tx.type === filterType.value) : transactions.value
    );

    return { open, loading, transactions: filteredTransactions, openPanel, closePanel };
}
