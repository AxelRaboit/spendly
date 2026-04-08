/**
 * Quick Category Creation
 *
 * Manages the quick category creation UI in budget forms.
 * Allows users to create new categories on-the-fly without leaving the form.
 */

import { ref, nextTick } from 'vue';

/**
 * Composable for quick category creation in forms
 *
 * @param {number} walletId - The wallet ID for the new category
 * @param {Ref<Array>} localCategories - Reference to the local categories list (will be mutated)
 * @returns {Object} State and methods for category creation
 */
export function useQuickCategoryCreate(walletId, localCategories) {
    const creatingCategory = ref(false);
    const newCategoryName = ref('');
    const creatingCategoryLoading = ref(false);
    const categoryTargetForm = ref(null);

    /**
     * Handle category selection change
     * Opens the create form if "__create__" is selected
     */
    function onCategoryChange(form) {
        if (form.category_id === '__create__') {
            form.category_id = null;
            categoryTargetForm.value = form;
            creatingCategory.value = true;
            nextTick(() => document.querySelector('[data-new-category]')?.focus());
            return;
        }
        // Auto-fill label from category name if category was selected
        if (form.category_id && !form.label) {
            const cat = localCategories.value.find(c => c.id === Number(form.category_id));
            if (cat) form.label = cat.name;
        }
    }

    /**
     * Create a new category
     */
    async function createCategory() {
        const name = newCategoryName.value.trim();
        if (!name || creatingCategoryLoading.value) return;

        creatingCategoryLoading.value = true;
        try {
            const { data: cat } = await window.axios.post('/categories/quick', { name, wallet_id: walletId });
            // Add to local list and sort
            localCategories.value = [...localCategories.value, cat].sort((a, b) =>
                a.name.localeCompare(b.name)
            );
            // Update the form that triggered the creation
            if (categoryTargetForm.value) {
                categoryTargetForm.value.category_id = cat.id;
                if (!categoryTargetForm.value.label) categoryTargetForm.value.label = cat.name;
            }
        } finally {
            creatingCategoryLoading.value = false;
            creatingCategory.value = false;
            newCategoryName.value = '';
            categoryTargetForm.value = null;
        }
    }

    /**
     * Cancel category creation
     */
    function cancelCreateCategory() {
        creatingCategory.value = false;
        newCategoryName.value = '';
        categoryTargetForm.value = null;
    }

    return {
        creatingCategory,
        newCategoryName,
        creatingCategoryLoading,
        categoryTargetForm,
        onCategoryChange,
        createCategory,
        cancelCreateCategory,
    };
}
