import { ref, nextTick, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

export function useBudgetItems(walletId, sections, budget, flash) {
    // ─── Edit ─────────────────────────────────────────────────────────────────
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

    function onEditOutsideClick(e) {
        if (!e.target.closest('[data-editing]')) cancelEditing();
    }

    watch(editingId, (id) => {
        if (id !== null) {
            document.addEventListener('mousedown', onEditOutsideClick);
        } else {
            document.removeEventListener('mousedown', onEditOutsideClick);
        }
    });

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

    function onAddOutsideClick(e) {
        if (!e.target.closest('[data-adding]')) cancelAdding();
    }

    watch(addingType, (type) => {
        if (type !== null) {
            document.addEventListener('mousedown', onAddOutsideClick);
        } else {
            document.removeEventListener('mousedown', onAddOutsideClick);
        }
    });

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

    // ─── Delete with undo ─────────────────────────────────────────────────────
    const pendingDeleteItem = ref(null); // waiting for confirm modal
    const deletingItem = ref(null); // confirmed, undo countdown running
    let deleteTimer = null;

    function requestDelete(item) {
        // If another delete is in progress, flush it immediately
        if (deletingItem.value) {
            clearTimeout(deleteTimer);
            _executeDelete(deletingItem.value.id);
        }
        pendingDeleteItem.value = item;
    }

    function confirmDelete() {
        if (!pendingDeleteItem.value) return;
        const item = pendingDeleteItem.value;
        pendingDeleteItem.value = null;
        deletingItem.value = item;
        deleteTimer = setTimeout(() => _executeDelete(item.id), 5000);
    }

    function _executeDelete(id) {
        deletingItem.value = null;
        router.delete(`/wallets/${walletId.value}/budget/items/${id}`, { preserveScroll: true });
    }

    function undoDelete() {
        clearTimeout(deleteTimer);
        deletingItem.value = null;
    }

    function cancelDelete() {
        pendingDeleteItem.value = null;
    }

    // ─── Drag & drop reorder ──────────────────────────────────────────────────
    const draggingId = ref(null);
    const dragOverId = ref(null);

    function onDragStart(e, item) {
        draggingId.value = item.id;
        e.dataTransfer.effectAllowed = 'move';
    }

    function onDragOver(e, item) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        if (item.id !== draggingId.value) dragOverId.value = item.id;
    }

    function onDrop(targetItem) {
        if (!draggingId.value || draggingId.value === targetItem.id) {
            draggingId.value = null;
            dragOverId.value = null;
            return;
        }

        // Prevent cross-section drops
        const type = Object.entries(sections.value).find(([, items]) =>
            items.some((i) => i.id === draggingId.value)
        )?.[0];

        if (!type || type !== targetItem.type) {
            draggingId.value = null;
            dragOverId.value = null;
            return;
        }

        const ids = (sections.value[type] ?? []).map((i) => i.id);
        const srcIdx = ids.indexOf(draggingId.value);
        const tgtIdx = ids.indexOf(targetItem.id);

        if (srcIdx === -1 || tgtIdx === -1) return;

        const [moved] = ids.splice(srcIdx, 1);
        ids.splice(tgtIdx, 0, moved);

        draggingId.value = null;
        dragOverId.value = null;

        router.patch(`/wallets/${walletId.value}/budget/items/reorder`, { ids }, { preserveScroll: true });
    }

    function onDragEnd() {
        draggingId.value = null;
        dragOverId.value = null;
    }

    // ─── Duplicate ────────────────────────────────────────────────────────────
    function duplicateItem(item) {
        router.post(`/wallets/${walletId.value}/budget/items/${item.id}/duplicate`, {}, { preserveScroll: true });
    }

    // ─── Toggle recurring ─────────────────────────────────────────────────────
    function toggleRecurring(item) {
        router.put(
            `/wallets/${walletId.value}/budget/items/${item.id}`,
            {
                label: item.label,
                planned_amount: item.planned_amount,
                category_id: item.category_id,
                notes: item.notes ?? '',
                is_recurring: !item.is_recurring,
            },
            { preserveScroll: true }
        );
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
        pendingDeleteItem,
        deletingItem,
        requestDelete,
        confirmDelete,
        undoDelete,
        cancelDelete,
        draggingId,
        dragOverId,
        onDragStart,
        onDragOver,
        onDrop,
        onDragEnd,
        duplicateItem,
        toggleRecurring,
    };
}
