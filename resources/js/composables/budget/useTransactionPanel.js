import { computed, ref, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';

export function useTransactionPanel(walletId, budget, sections, flash, categories) {
    const txPanel = ref(false);
    const txPrefillLabel = ref('');
    const txSection = ref(null);
    const txForm = useForm({
        wallet_id: walletId.value,
        type: 'expense',
        category_id: null,
        amount: '',
        description: '',
        date: new Date().toISOString().slice(0, 10),
    });

    // Categories filtered by selected budget section
    const txFilteredCategories = computed(() => {
        if (!txSection.value || !categories) return categories?.value ?? [];
        const sectionItems = sections.value[txSection.value] ?? [];
        const validIds = new Set(sectionItems.map((i) => i.category_id).filter(Boolean));
        return (categories.value ?? []).filter((c) => validIds.has(c.id));
    });

    function openTxPanel(
        categoryId = null,
        label = '',
        type = 'expense',
        { cancelEditing, cancelAdding } = {},
        section = null
    ) {
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
        txSection.value = section;
        txPanel.value = true;
        nextTick(() => document.getElementById('tx-amount')?.focus());
    }

    function onTxSectionChange(newSection) {
        txSection.value = newSection;
        if (newSection === 'income') {
            txForm.type = 'income';
        } else if (newSection !== null) {
            txForm.type = 'expense';
        }
        if (newSection && txForm.category_id) {
            const sectionItems = sections.value[newSection] ?? [];
            const validIds = new Set(sectionItems.map((i) => i.category_id).filter(Boolean));
            if (!validIds.has(txForm.category_id)) txForm.category_id = null;
        }
    }

    function closeTxPanel() {
        txPanel.value = false;
        txPrefillLabel.value = '';
        txSection.value = null;
        txForm.reset();
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

    return {
        txPanel,
        txPrefillLabel,
        txForm,
        txSection,
        txFilteredCategories,
        openTxPanel,
        closeTxPanel,
        submitTx,
        onTxSectionChange,
    };
}
