import { computed } from 'vue';
import { useTheme } from '@/composables/ui/useTheme';
import { Theme } from '@/enums/Theme';

export function useChartTheme() {
    const { theme } = useTheme();

    const isDark = computed(() => theme.value === Theme.Dark);

    const textColor  = computed(() => (isDark.value ? '#9ca3af' : '#6b7280'));
    const gridColor  = computed(() => (isDark.value ? '#374151' : '#e5e7eb'));
    const labelColor = computed(() => (isDark.value ? '#d1d5db' : '#374151'));

    const baseOptions = computed(() => ({
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { labels: { color: labelColor.value } },
        },
    }));

    const barOptions = computed(() => ({
        ...baseOptions.value,
        scales: {
            x: { ticks: { color: textColor.value }, grid: { color: gridColor.value } },
            y: { ticks: { color: textColor.value }, grid: { color: gridColor.value } },
        },
    }));

    return { baseOptions, barOptions, textColor, gridColor, labelColor };
}
