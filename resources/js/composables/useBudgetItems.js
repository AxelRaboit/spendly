import { ref, nextTick } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

export function useBudgetItems(walletId, sections, budget, flash) {
    // ─── Edit ─────────────────────────────────────────────────────────────────
    const editingId = ref(null);
    const editForm = useForm({ label: '', planned_amount: '', category_id: null, notes: '' });

    function startEditing(item, { cancelAdding }) {
        cancelAdding();
        editingId.value = item.id;
        editForm.label = item.label;
        editForm.planned_amount = item.planned_amount;
        editForm.category_id = item.category_id;
        editForm.notes = item.notes ?? '';
        nextTick(() => document.getElementById(`edit-label-${item.id}`)?.focus());
    }

    function cancelEditing() {
        editingId.value = null;
        editForm.reset();
    }

    function submitEdit(item) {
        const savedId = item.id;
        editForm.put(`/wallets/${walletId.value}/budget/items/${savedId}`, {
            onSuccess: () => {
                cancelEditing();
                flash(savedId);
            },
        });
    }

    // ─── Add ──────────────────────────────────────────────────────────────────
    const addingType = ref(null);
    const addForm = useForm({ type: '', label: '', planned_amount: '', category_id: null, month: '' });

    function startAdding(type, { cancelEditing: ce }) {
        ce();
        if (addingType.value === type) return;
        addingType.value = type;
        addForm.type = type;
        addForm.month = budget.value.month;
        addForm.label = '';
        addForm.planned_amount = '';
        addForm.category_id = null;
        nextTick(() => document.getElementById(`add-label-${type}`)?.focus());
    }

    function cancelAdding() {
        addingType.value = null;
        addForm.reset();
    }

    function submitAdd() {
        if (!addForm.label || addForm.planned_amount === '') return;
        const sectionBefore = new Set((sections.value[addingType.value] ?? []).map((i) => i.id));
        const type = addingType.value;
        addForm.post(`/wallets/${walletId.value}/budget/items`, {
            onSuccess: () => {
                addingType.value = null;
                addForm.reset();
                nextTick(() => {
                    const newItem = (sections.value[type] ?? []).find((i) => !sectionBefore.has(i.id));
                    if (newItem) flash(newItem.id);
                });
            },
        });
    }

    function deleteBudgetItem(item) {
        if (confirm(`Supprimer « ${item.label} » ?`)) {
            router.delete(`/wallets/${walletId.value}/budget/items/${item.id}`);
        }
    }

    return {
        editingId,
        editForm,
        startEditing,
        cancelEditing,
        submitEdit,
        addingType,
        addForm,
        startAdding,
        cancelAdding,
        submitAdd,
        deleteBudgetItem,
    };
}
