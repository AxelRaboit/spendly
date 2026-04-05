import { computed } from 'vue';

export function useSectionCategoryFilter(txSection, sections, categories, txForm) {
    const txFilteredCategories = computed(() => {
        if (!txSection.value || !categories) return categories?.value ?? [];
        const sectionItems = sections.value[txSection.value] ?? [];
        const validIds = new Set(sectionItems.map((i) => i.category_id).filter(Boolean));
        if (txForm.category_id) validIds.add(txForm.category_id);
        return (categories.value ?? []).filter((c) => validIds.has(c.id));
    });

    function onTxSectionChange(newSection) {
        txSection.value = newSection;
        if (newSection === 'income') {
            txForm.type = 'income';
        } else if (newSection !== null) {
            txForm.type = 'expense';
        }
        if (newSection && txForm.category_id) {
            const sectionItems = sections.value[newSection] ?? [];
            const validIds = new Set(sectionItems.map((i) => i.category_id).filter(Boolean));
            if (!validIds.has(txForm.category_id)) txForm.category_id = null;
        }
    }

    return { txFilteredCategories, onTxSectionChange };
}
