import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';

export function useSearchFilters(initialFilters) {
    const filters = reactive({
        q: initialFilters.q ?? '',
        category_id: initialFilters.category_id ?? '',
        wallet_id: initialFilters.wallet_id ?? '',
        type: initialFilters.type ?? '',
        date_from: initialFilters.date_from ?? '',
        date_to: initialFilters.date_to ?? '',
        tag: initialFilters.tag ?? '',
    });

    function clean(obj) {
        return Object.fromEntries(Object.entries(obj).filter(([, v]) => v !== ''));
    }

    let timeout = null;
    function search() {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            router.get('/search', clean(filters), { preserveState: true, preserveScroll: true, replace: true });
        }, 300);
    }

    function reset() {
        Object.assign(filters, {
            q: '',
            category_id: '',
            wallet_id: '',
            type: '',
            date_from: '',
            date_to: '',
            tag: '',
        });
        router.get('/search', {}, { replace: true });
    }

    function filterByTag(tag) {
        filters.tag = tag;
        search();
    }

    function hasFilters() {
        return Object.values(filters).some((v) => v !== '');
    }

    return { filters, search, reset, filterByTag, hasFilters };
}
