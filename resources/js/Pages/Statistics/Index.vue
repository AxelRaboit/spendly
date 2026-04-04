<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import '@/plugins/chartjs';
import { computed } from 'vue';
import { Bar, Doughnut } from 'vue-chartjs';
import { useCurrency } from '@/composables/core/useCurrency';
import { useChartTheme } from '@/composables/ui/useChartTheme';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

function fmtMonth(yyyyMm) {
    const [year, month] = yyyyMm.split('-');
    return new Intl.DateTimeFormat(locale.value, { month: 'short', year: 'numeric' })
        .format(new Date(Number(year), Number(month) - 1, 1));
}

const props = defineProps({
    byCategory: Array,
    byMonth: Array,
    currentMonth: Number,
    previousMonth: Number,
});

const { fmt, currency } = useCurrency();
const { baseOptions, barOptions: barThemeOptions } = useChartTheme();

const evolution = computed(() => {
    if (props.previousMonth === 0) return null;
    return ((props.currentMonth - props.previousMonth) / props.previousMonth) * 100;
});

const categoryColors = [
    '#6366f1', '#8b5cf6', '#a78bfa', '#c4b5fd',
    '#818cf8', '#4f46e5', '#7c3aed', '#9333ea',
    '#a855f7', '#d946ef', '#ec4899', '#f43f5e',
];

const donutData = computed(() => ({
    labels: props.byCategory.map((c) => c.name),
    datasets: [{
        data: props.byCategory.map((c) => parseFloat(c.total)),
        backgroundColor: categoryColors.slice(0, props.byCategory.length),
        borderWidth: 0,
    }],
}));

const barData = computed(() => ({
    labels: props.byMonth.map((m) => fmtMonth(m.month)),
    datasets: [{
        label: t('statistics.chartLabel', { currency: currency.value }),
        data: props.byMonth.map((m) => parseFloat(m.total)),
        backgroundColor: '#6366f1',
        borderRadius: 6,
    }],
}));

const chartOptions = baseOptions;
const barOptions   = barThemeOptions;
</script>

<template>
    <Head :title="t('statistics.title')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-primary leading-tight">{{ t('statistics.title') }}</h2>
        </template>

        <div class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="bg-surface rounded-lg p-6">
                    <p class="text-sm text-secondary mb-1">{{ t('statistics.thisMonth') }}</p>
                    <p class="text-3xl font-bold text-primary">{{ fmt(currentMonth) }}</p>
                    <p v-if="evolution !== null" class="text-sm mt-1" :class="evolution > 0 ? 'text-red-400' : 'text-green-400'">
                        {{ t('statistics.vsLast', { sign: evolution > 0 ? '+' : '', pct: evolution.toFixed(1) }) }}
                    </p>
                </div>
                <div class="bg-surface rounded-lg p-6">
                    <p class="text-sm text-secondary mb-1">{{ t('statistics.lastMonth') }}</p>
                    <p class="text-3xl font-bold text-primary">{{ fmt(previousMonth) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-surface rounded-lg p-6">
                    <h3 class="text-primary font-semibold mb-4">{{ t('statistics.byCategory') }}</h3>
                    <div class="h-64">
                        <Doughnut :data="donutData" :options="chartOptions" />
                    </div>
                </div>

                <div class="bg-surface rounded-lg p-6">
                    <h3 class="text-primary font-semibold mb-4">{{ t('statistics.evolution') }}</h3>
                    <div class="h-64">
                        <Bar :data="barData" :options="barOptions" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
