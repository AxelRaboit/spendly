import { computed } from 'vue';
import { SECTION_COLORS } from './sectionColors.js';

export function useDonutSegments(totals, SECTION_META) {
    return computed(() =>
        Object.entries(totals.value)
            .filter(([, sec]) => sec.actual > 0)
            .map(([key, sec]) => ({
                label: SECTION_META.value[key]?.label ?? key,
                value: sec.actual,
                color: SECTION_COLORS[key],
            }))
    );
}
