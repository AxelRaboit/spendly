import { computed, nextTick, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { useSectionCategoryFilter } from './useSectionCategoryFilter.js';
import { useAutoCategory } from '@/composables/forms/useAutoCategory.js';
import { TransactionType } from '@/enums/TransactionType';
import { BudgetSection } from '@/enums/BudgetSection';

/**
 * Transaction Panel - Add/Edit Transactions
 *
 * Manages the transaction creation and editing UI panel.
 * Handles form state, category filtering, auto-categorization, and split transactions.
 *
 * Composed of:
 * - useSectionCategoryFilter: Filters available categories by budget section
 * - useAutoCategory: Suggests categories based on transaction description
 *
 * @param {Object} walletId - Wallet ID (usually computed ref)
 * @param {Object} budget - Budget metadata
 * @param {Object} sections - Budget sections with items
 * @param {Object} flash - Flash message state
 * @param {Object} categories - Available categories list
 *
 * @returns {Object}
 *   - txPanel: Ref for panel visibility
 *   - txPrefillLabel: Ref for prefill text (e.g., from budget row click)
 *   - txForm: Inertia form object with transaction fields (wallet_id, type, category_id, amount, description, date, tags, attachment)
 *   - txSection: Ref for selected budget section (income/savings/bills/expenses/debt)
 *   - txFilteredCategories: Computed list of available categories for selected section
 *   - editingTx: Ref to transaction being edited
 *   - suggestedCategoryId: Auto-suggested category from description
 *   - markCategoryManual: Function to mark category as manually selected (disables auto-suggestion)
 *   - openTxPanel: Open panel for new transaction (with optional prefill)
 *   - openEditTx: Open panel to edit existing transaction
 *   - closeTxPanel: Close panel and reset form
 *   - submitTx: Submit new/edited transaction
 *   - submitSplit: Submit split transaction
 *   - onTxSectionChange: Handle section change (filters categories)
 */
export function useTransactionPanel(walletId, budget, sections, flash, categories) {
    const txPanel        = ref(false);
    const txPrefillLabel = ref('');
    const txSection      = ref(null);
    const editingTx      = ref(null);
    const txForm = useForm({
        wallet_id:   walletId.value,
        type:        TransactionType.Expense,
        category_id: null,
        amount:      '',
        description: '',
        date:        new Date().toISOString().slice(0, 10),
        tags:        [],
        attachment:  null,
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
        type = TransactionType.Expense,
        { cancelEditing, cancelAdding } = {},
        section = null
    ) {
        cancelEditing?.();
        cancelAdding?.();
        txForm.wallet_id   = walletId.value;
        txForm.type        = type;
        txForm.category_id = categoryId;
        txForm.amount      = '';
        txForm.description = '';
        txForm.tags        = [];
        const today   = new Date().toISOString().slice(0, 10);
        txForm.date   = budget.value.month === today.slice(0, 7) ? today : budget.value.month + '-01';
        txPrefillLabel.value = label;
        txSection.value      = section;
        resetAutoCategory();
        txPanel.value = true;
        nextTick(() => document.getElementById('tx-amount')?.focus());
    }

    function openEditTx(tx) {
        editingTx.value    = tx;
        txForm.wallet_id   = tx.wallet_id ?? walletId.value;
        txForm.type        = tx.type;
        txForm.category_id = tx.category_id;
        txForm.amount      = tx.amount;
        txForm.description = tx.description ?? '';
        txForm.date        = tx.date?.slice(0, 10) ?? new Date().toISOString().slice(0, 10);
        txForm.tags        = tx.tags ?? [];
        txPrefillLabel.value = '';
        txSection.value      = null;
        txPanel.value        = true;
    }

    function closeTxPanel() {
        txPanel.value        = false;
        txPrefillLabel.value = '';
        txSection.value      = null;
        editingTx.value      = null;
        txForm.reset();
        resetAutoCategory();
    }

    function submitTx() {
        const categoryId = txForm.category_id;
        const url    = editingTx.value ? `/transactions/${editingTx.value.id}` : '/transactions';
        const method = editingTx.value ? 'put' : 'post';
        txForm[method](url, {
            onSuccess: () => {
                const color = txForm.type === TransactionType.Income ? 'emerald' : 'rose';
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

    function submitSplit(splits) {
        router.post(
            '/transactions/split',
            {
                wallet_id:   txForm.wallet_id,
                type:        txForm.type,
                description: txForm.description,
                date:        txForm.date,
                tags:        txForm.tags,
                splits:      splits.map((s) => ({ category_id: s.category_id, amount: parseFloat(s.amount) || 0 })),
            },
            {
                preserveScroll: true,
                onSuccess: () => closeTxPanel(),
            }
        );
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
        submitSplit,
        onTxSectionChange,
    };
}
