import { computed, nextTick, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useSectionCategoryFilter } from './useSectionCategoryFilter.js';
import { useAutoCategory } from '@/composables/forms/useAutoCategory.js';

export function useTransactionPanel(walletId, budget, sections, flash, categories) {
    const txPanel = ref(false);
    const txPrefillLabel = ref('');
    const txSection = ref(null);
    const editingTx = ref(null);
    const txForm = useForm({
        wallet_id: walletId.value,
        type: 'expense',
        category_id: null,
        amount: '',
        description: '',
        date: new Date().toISOString().slice(0, 10),
        tags: [],
    });

    const { txFilteredCategories, onTxSectionChange } = useSectionCategoryFilter(
        txSection,
        sections,
        categories,
        txForm
    );

    const descriptionRef = computed(() => txForm.description);
    const {
        suggestedCategoryId,
        markManual: markCategoryManual,
        reset: resetAutoCategory,
    } = useAutoCategory(descriptionRef, (categoryId) => {
        txForm.category_id = categoryId;
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
        txForm.tags = [];
        const today = new Date().toISOString().slice(0, 10);
        txForm.date = budget.value.month === today.slice(0, 7) ? today : budget.value.month + '-01';
        txPrefillLabel.value = label;
        txSection.value = section;
        resetAutoCategory();
        txPanel.value = true;
        nextTick(() => document.getElementById('tx-amount')?.focus());
    }

    function openEditTx(tx) {
        editingTx.value = tx;
        txForm.wallet_id = tx.wallet_id ?? walletId.value;
        txForm.type = tx.type;
        txForm.category_id = tx.category_id;
        txForm.amount = tx.amount;
        txForm.description = tx.description ?? '';
        txForm.date = tx.date?.slice(0, 10) ?? new Date().toISOString().slice(0, 10);
        txForm.tags = tx.tags ?? [];
        txPrefillLabel.value = '';
        txSection.value = null;
        txPanel.value = true;
    }

    function closeTxPanel() {
        txPanel.value = false;
        txPrefillLabel.value = '';
        txSection.value = null;
        editingTx.value = null;
        txForm.reset();
        resetAutoCategory();
    }

    function submitTx() {
        const categoryId = txForm.category_id;
        const url = editingTx.value ? `/transactions/${editingTx.value.id}` : '/transactions';
        const method = editingTx.value ? 'put' : 'post';
        txForm[method](url, {
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
        editingTx,
        suggestedCategoryId,
        markCategoryManual,
        openTxPanel,
        openEditTx,
        closeTxPanel,
        submitTx,
        onTxSectionChange,
    };
}
