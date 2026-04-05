import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

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
