import { nextTick, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

export function useEditItem(walletId, flash) {
    const editingId = ref(null);
    const editForm = useForm({ label: '', planned_amount: '', category_id: null, notes: '', type: '' });

    function startEditing(item, { cancelAdding }) {
        cancelAdding();
        editingId.value = item.id;
        editForm.label = item.label;
        editForm.planned_amount = item.planned_amount;
        editForm.category_id = item.category_id;
        editForm.notes = item.notes ?? '';
        editForm.type = item.type;
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

    function onOutsideClick(e) {
        if (!e.target.closest('[data-editing]')) cancelEditing();
    }

    watch(editingId, (id) => {
        if (id !== null) {
            document.addEventListener('mousedown', onOutsideClick);
        } else {
            document.removeEventListener('mousedown', onOutsideClick);
        }
    });

    return { editingId, editForm, startEditing, cancelEditing, submitEdit };
}
