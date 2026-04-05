import { nextTick, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

export function useAddItem(walletId, sections, budget, flash) {
    const addingType = ref(null);
    const addForm = useForm({ type: '', label: '', planned_amount: '', category_id: null, month: '' });

    function startAdding(type, { cancelEditing }) {
        cancelEditing();
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
                    if (newItem) {
                        flash(newItem.id);
                        const row = document.querySelector(`tr[data-row-id="${newItem.id}"]`);
                        row?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
            },
        });
    }

    function onOutsideClick(e) {
        if (!e.target.closest('[data-adding]')) cancelAdding();
    }

    watch(addingType, (type) => {
        if (type !== null) {
            document.addEventListener('mousedown', onOutsideClick);
        } else {
            document.removeEventListener('mousedown', onOutsideClick);
        }
    });

    return { addingType, addForm, startAdding, cancelAdding, submitAdd };
}
