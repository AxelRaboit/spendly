import { ref, watch } from 'vue';

/**
 * Auto-suggest a category based on transaction description.
 * Debounces the API call and only suggests when the user hasn't manually set a category.
 */
export function useAutoCategory(descriptionRef, onSuggest) {
    const suggestedCategoryId = ref(null);
    const categoryManuallySet = ref(false);

    let timeout = null;

    watch(descriptionRef, (value) => {
        clearTimeout(timeout);
        suggestedCategoryId.value = null;

        if (!value || value.length < 2 || categoryManuallySet.value) return;

        timeout = setTimeout(async () => {
            try {
                const { data } = await window.axios.get('/categorization-rules/suggest', {
                    params: { description: value },
                });
                if (data.category_id && !categoryManuallySet.value) {
                    suggestedCategoryId.value = data.category_id;
                    onSuggest(data.category_id);
                }
            } catch {
                // silently ignore
            }
        }, 300);
    });

    function markManual() {
        categoryManuallySet.value = true;
    }

    function reset() {
        categoryManuallySet.value = false;
        suggestedCategoryId.value = null;
    }

    return { suggestedCategoryId, categoryManuallySet, markManual, reset };
}
