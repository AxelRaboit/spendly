import { router } from '@inertiajs/vue3';

export function useConfirmDelete(message = 'Êtes-vous sûr de vouloir supprimer cet élément ?') {
    const confirmDelete = (url) => {
        if (confirm(message)) {
            router.delete(url);
        }
    };

    return { confirmDelete };
}
