import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Drag & Drop Reordering
 *
 * Manages budget item reordering via drag-and-drop.
 * Maintains local state and syncs changes to backend.
 *
 * @param {Object} walletId - Wallet ID (usually a computed ref)
 * @param {Object} sections - Reactive sections object with budget items
 *
 * @returns {Object}
 *   - draggingId: Ref to ID of item being dragged
 *   - dragOverId: Ref to ID of item under drag cursor
 *   - onDragStart: Handler for drag start event
 *   - onDragOver: Handler for drag over event
 *   - onDrop: Handler for drop event (performs reorder)
 *   - onDragEnd: Handler for drag end event
 */
export function useDragDropReorder(walletId, sections) {
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

    return { draggingId, dragOverId, onDragStart, onDragOver, onDrop, onDragEnd };
}
