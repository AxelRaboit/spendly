import { useEditItem } from './useEditItem.js';
import { useAddItem } from './useAddItem.js';
import { useDeleteItem } from './useDeleteItem.js';
import { useDragDropReorder } from './useDragDropReorder.js';
import { useItemActions } from './useItemActions.js';

export function useBudgetItems(walletId, sections, budget, flash) {
    const { editingId, editForm, startEditing, cancelEditing, submitEdit } = useEditItem(walletId, flash);

    const { addingType, addForm, startAdding, cancelAdding, submitAdd } = useAddItem(walletId, sections, budget, flash);

    const { pendingDeleteItem, deletingItem, requestDelete, confirmDelete, undoDelete, cancelDelete } =
        useDeleteItem(walletId);

    const { draggingId, dragOverId, onDragStart, onDragOver, onDrop, onDragEnd } = useDragDropReorder(
        walletId,
        sections
    );

    const { duplicateItem, toggleRecurring } = useItemActions(walletId);

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
