import { isRef, ref } from 'vue';
import { router } from '@inertiajs/vue3';

export function useConfirmDelete(messageOrRef = 'Êtes-vous sûr de vouloir supprimer cet élément ?') {
    const isOpen = ref(false);
    const pendingUrl = ref(null);
    const message = isRef(messageOrRef) ? messageOrRef : ref(messageOrRef);

    const confirmDelete = (url) => {
        pendingUrl.value = url;
        isOpen.value = true;
    };

    const onConfirm = () => {
        router.delete(pendingUrl.value);
        isOpen.value = false;
        pendingUrl.value = null;
    };

    const onCancel = () => {
        isOpen.value = false;
        pendingUrl.value = null;
    };

    return { isOpen, message, confirmDelete, onConfirm, onCancel };
}
