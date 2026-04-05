<script setup>
import AppTooltip from '@/components/ui/AppTooltip.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import '@/plugins/chartjs';
import { computed } from 'vue';
import { Bar, Doughnut, Line } from 'vue-chartjs';
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
    byCategory:     Array,
    byMonth:        Array,
    currentMonth:   Number,
    previousMonth:  Number,
    savingsHistory: Array,
    budgetVsActual: Array,
    yearProjection: Object,
});

const { fmt, currency } = useCurrency();
const { baseOptions, barOptions: barThemeOptions, textColor, gridColor } = useChartTheme();

const evolution = computed(() => {
    if (props.previousMonth === 0) return null;
    return ((props.currentMonth - props.previousMonth) / props.previousMonth) * 100;
});

const currentYear = new Date().getFullYear();

// ── Donut ──────────────────────────────────────────────────────────────────
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

// ── Bar (evolution) ────────────────────────────────────────────────────────
const barData = computed(() => ({
    labels: props.byMonth.map((m) => fmtMonth(m.month)),
    datasets: [{
        label: t('statistics.chartLabel', { currency: currency.value }),
        data: props.byMonth.map((m) => parseFloat(m.total)),
        backgroundColor: '#6366f1',
        borderRadius: 6,
    }],
}));

// ── Savings rate line ──────────────────────────────────────────────────────
const savingsLineData = computed(() => ({
    labels: props.savingsHistory.map((m) => fmtMonth(m.month)),
    datasets: [{
        label: t('statistics.savingsRate'),
        data: props.savingsHistory.map((m) => m.rate),
        borderColor: '#34d399',
        backgroundColor: 'rgba(52, 211, 153, 0.1)',
        borderWidth: 2,
        pointRadius: 3,
        tension: 0.4,
        fill: true,
        spanGaps: true,
    }],
}));

const savingsLineOptions = computed(() => ({
    ...barThemeOptions.value,
    scales: {
        x: { ticks: { color: textColor.value }, grid: { color: gridColor.value } },
        y: {
            ticks: { color: textColor.value, callback: (v) => v !== null ? v + '%' : '' },
            grid: { color: gridColor.value },
        },
    },
    plugins: {
        ...barThemeOptions.value.plugins,
        tooltip: { callbacks: { label: (ctx) => ctx.raw !== null ? ctx.raw + '%' : 'N/A' } },
    },
}));

// ── Budget vs actual grouped bar ───────────────────────────────────────────
const budgetBarData = computed(() => ({
    labels: props.budgetVsActual.map((m) => fmtMonth(m.month)),
    datasets: [
        {
            label: t('statistics.planned'),
            data: props.budgetVsActual.map((m) => m.planned),
            backgroundColor: '#6366f1',
            borderRadius: 4,
        },
        {
            label: t('statistics.actual'),
            data: props.budgetVsActual.map((m) => m.actual),
            backgroundColor: '#f43f5e',
            borderRadius: 4,
        },
    ],
}));

const chartOptions = baseOptions;
const barOptions   = barThemeOptions;
</script>

<template>
    <Head :title="t('statistics.title')" />

    <AuthenticatedLayout>
        <template #header>
            <AppPageHeader :title="t('statistics.title')" />
        </template>

        <div class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-surface border border-base/60 rounded-xl p-5">
                    <AppTooltip :text="t('statistics.thisMonthTip')"><p class="text-sm text-secondary mb-1 cursor-help">{{ t('statistics.thisMonth') }}</p></AppTooltip>
                    <p class="text-3xl font-bold text-primary font-mono">{{ fmt(currentMonth) }}</p>
                    <AppTooltip v-if="evolution !== null" :text="t('statistics.vsLastTip')">
                        <p class="text-sm mt-1 cursor-help" :class="evolution > 0 ? 'text-rose-400' : 'text-emerald-400'">
                            {{ t('statistics.vsLast', { sign: evolution > 0 ? '+' : '', pct: evolution.toFixed(1) }) }}
                        </p>
                    </AppTooltip>
                </div>
                <div class="bg-surface border border-base/60 rounded-xl p-5">
                    <AppTooltip :text="t('statistics.lastMonthTip')"><p class="text-sm text-secondary mb-1 cursor-help">{{ t('statistics.lastMonth') }}</p></AppTooltip>
                    <p class="text-3xl font-bold text-primary font-mono">{{ fmt(previousMonth) }}</p>
                </div>
            </div>

            <div class="bg-surface border border-base/60 rounded-xl p-5">
                <AppTooltip :text="t('statistics.yearProjectionTip')">
                    <h3 class="text-sm font-semibold text-secondary uppercase tracking-wide mb-4 cursor-help">
                        {{ t('statistics.yearProjection', { year: currentYear }) }}
                    </h3>
                </AppTooltip>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div>
                        <AppTooltip :text="t('statistics.spentSoFarTip')"><p class="text-xs text-muted mb-1 cursor-help">{{ t('statistics.spentSoFar') }}</p></AppTooltip>
                        <p class="text-lg font-bold font-mono text-primary">{{ fmt(yearProjection.spent_so_far) }}</p>
                    </div>
                    <div>
                        <AppTooltip :text="t('statistics.avgPerMonthTip')"><p class="text-xs text-muted mb-1 cursor-help">{{ t('statistics.avgPerMonth') }}</p></AppTooltip>
                        <p class="text-lg font-bold font-mono text-primary">{{ fmt(yearProjection.avg_per_month) }}</p>
                    </div>
                    <div>
                        <AppTooltip :text="t('statistics.projectedTotalTip')"><p class="text-xs text-muted mb-1 cursor-help">{{ t('statistics.projectedTotal') }}</p></AppTooltip>
                        <p class="text-lg font-bold font-mono text-rose-400">{{ fmt(yearProjection.projected) }}</p>
                    </div>
                    <div>
                        <AppTooltip :text="t('statistics.monthsLeftTip')"><p class="text-xs text-muted mb-1 cursor-help">{{ t('statistics.monthsLeft', { n: yearProjection.months_left }) }}</p></AppTooltip>
                        <p class="text-lg font-bold font-mono text-secondary">{{ fmt(yearProjection.remaining) }}</p>
                        <p class="text-xs text-muted">restants estimés</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-surface border border-base/60 rounded-xl p-5">
                    <AppTooltip :text="t('statistics.byCategoryTip')"><h3 class="text-sm font-semibold text-secondary mb-4 cursor-help">{{ t('statistics.byCategory') }}</h3></AppTooltip>
                    <div class="h-48 sm:h-64">
                        <Doughnut :data="donutData" :options="chartOptions" />
                    </div>
                </div>

                <div class="bg-surface border border-base/60 rounded-xl p-5">
                    <AppTooltip :text="t('statistics.evolutionTip')"><h3 class="text-sm font-semibold text-secondary mb-4 cursor-help">{{ t('statistics.evolution') }}</h3></AppTooltip>
                    <div class="h-48 sm:h-64">
                        <Bar :data="barData" :options="barOptions" />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-surface border border-base/60 rounded-xl p-5">
                    <AppTooltip :text="t('statistics.savingsRateTip')"><h3 class="text-sm font-semibold text-secondary mb-4 cursor-help">{{ t('statistics.savingsRate') }}</h3></AppTooltip>
                    <div class="h-48 sm:h-64">
                        <Line :data="savingsLineData" :options="savingsLineOptions" />
                    </div>
                </div>

                <div class="bg-surface border border-base/60 rounded-xl p-5">
                    <AppTooltip :text="t('statistics.budgetVsActualTip')"><h3 class="text-sm font-semibold text-secondary mb-4 cursor-help">{{ t('statistics.budgetVsActual') }}</h3></AppTooltip>
                    <div class="h-48 sm:h-64">
                        <Bar :data="budgetBarData" :options="barOptions" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
