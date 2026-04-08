import { reactive, onScopeDispose } from 'vue';
import { router } from '@inertiajs/vue3';

export function useNoteFilters(initialFilters) {
    const filters = reactive({
        q: initialFilters.q ?? '',
        tag: initialFilters.tag ?? '',
    });

    function clean(obj) {
        return Object.fromEntries(Object.entries(obj).filter(([, v]) => v !== ''));
    }

    let timeout = null;
    function search() {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            router.get(route('notes.index'), clean(filters), {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, 300);
    }

    function filterByTag(tag) {
        filters.tag = filters.tag === tag ? '' : tag;
        search();
    }

    function reset() {
        Object.assign(filters, { q: '', tag: '' });
        router.get(route('notes.index'), {}, { replace: true });
    }

    function hasFilters() {
        return Object.values(filters).some((v) => v !== '');
    }

    onScopeDispose(() => clearTimeout(timeout));

    return { filters, search, filterByTag, reset, hasFilters };
}
