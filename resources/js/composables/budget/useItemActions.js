import { router } from '@inertiajs/vue3';

export function useItemActions(walletId) {
    function duplicateItem(item) {
        router.post(`/wallets/${walletId.value}/budget/items/${item.id}/duplicate`, {}, { preserveScroll: true });
    }

    function toggleRepeat(item) {
        router.put(
            `/wallets/${walletId.value}/budget/items/${item.id}`,
            {
                label: item.label,
                planned_amount: item.planned_amount,
                category_id: item.category_id,
                notes: item.notes ?? '',
                repeat_next_month: !item.repeat_next_month,
            },
            { preserveScroll: true }
        );
    }

    return { duplicateItem, toggleRepeat };
}
