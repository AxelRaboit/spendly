import { ref, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';

export function useTransactionPanel(walletId, budget, sections, flash) {
    const txPanel = ref(false);
    const txPrefillLabel = ref('');
    const txForm = useForm({
        wallet_id: walletId.value,
        type: 'expense',
        category_id: null,
        amount: '',
        description: '',
        date: new Date().toISOString().slice(0, 10),
    });

    function openTxPanel(categoryId = null, label = '', type = 'expense', { cancelEditing, cancelAdding } = {}) {
        cancelEditing?.();
        cancelAdding?.();
        txForm.wallet_id = walletId.value;
        txForm.type = type;
        txForm.category_id = categoryId;
        txForm.amount = '';
        txForm.description = '';
        const today = new Date().toISOString().slice(0, 10);
        txForm.date = budget.value.month === today.slice(0, 7) ? today : budget.value.month + '-01';
        txPrefillLabel.value = label;
        txPanel.value = true;
        nextTick(() => document.getElementById('tx-amount')?.focus());
    }

    function closeTxPanel() {
        txPanel.value = false;
        txForm.reset();
        txPrefillLabel.value = '';
    }

    function submitTx() {
        const categoryId = txForm.category_id;
        txForm.post('/transactions', {
            onSuccess: () => {
                const color = txForm.type === 'income' ? 'emerald' : 'rose';
                closeTxPanel();
                if (categoryId) {
                    for (const items of Object.values(sections.value)) {
                        const match = items.find((i) => i.category_id === categoryId);
                        if (match) {
                            flash(match.id, color);
                            break;
                        }
                    }
                }
            },
        });
    }

    return { txPanel, txPrefillLabel, txForm, openTxPanel, closeTxPanel, submitTx };
}
