import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';

export function useImportRows(walletId) {
    const rows = ref([]);
    const bulkCategory = ref('');
    const processing = ref(false);

    let nextId = 10000;

    function loadRows(data, defaultCategory) {
        const suggestions = data.suggestions || {};
        rows.value = data.rows.map((r, i) => {
            const pattern = (r.description || '').trim().toLowerCase();
            return {
                ...r,
                _id: i,
                category_id: defaultCategory || suggestions[pattern] || '',
            };
        });
    }

    function applyBulkCategory(catId) {
        rows.value.forEach((r) => {
            r.category_id = catId;
        });
    }

    function addRow() {
        rows.value.push({
            _id: nextId++,
            date: '',
            amount: '',
            type: 'expense',
            description: '',
            tags: '',
            category_id: bulkCategory.value || '',
        });
    }

    function removeRow(id) {
        rows.value = rows.value.filter((r) => r._id !== id);
    }

    const allCategorized = computed(() => rows.value.length > 0 && rows.value.every((r) => r.category_id));

    function submitImport() {
        processing.value = true;
        router.post(
            '/import/process',
            {
                rows: rows.value.map(({ _id, ...r }) => r),
                wallet_id: walletId.value,
            },
            {
                onFinish: () => {
                    processing.value = false;
                },
            }
        );
    }

    return {
        rows,
        bulkCategory,
        processing,
        allCategorized,
        loadRows,
        applyBulkCategory,
        addRow,
        removeRow,
        submitImport,
    };
}
