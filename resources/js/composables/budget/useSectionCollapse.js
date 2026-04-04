import { computed, ref, watch } from 'vue';

export function useSectionCollapse(walletId, budgetMonth) {
    const storageKey = computed(() => `spendly:budget-collapsed:${walletId.value}:${budgetMonth.value}`);

    const collapsedSections = ref(JSON.parse(localStorage.getItem(storageKey.value) ?? '{}'));

    watch(
        collapsedSections,
        (val) => {
            localStorage.setItem(storageKey.value, JSON.stringify(val));
        },
        { deep: true }
    );

    function toggleSection(type) {
        collapsedSections.value = {
            ...collapsedSections.value,
            [type]: !collapsedSections.value[type],
        };
    }

    return { collapsedSections, toggleSection };
}
