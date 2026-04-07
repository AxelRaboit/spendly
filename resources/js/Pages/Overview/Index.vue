<script setup>
import '@/plugins/chartjs';
import AppTooltip from '@/components/ui/AppTooltip.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import { Bar, Doughnut } from 'vue-chartjs';
import { ChevronLeft, ChevronRight, Star } from 'lucide-vue-next';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useCurrency } from '@/composables/core/useCurrency';
import { useFmtMonth } from '@/composables/core/useFmtMonth';
import { useChartTheme } from '@/composables/ui/useChartTheme';
import { useI18n } from 'vue-i18n';

const { t }        = useI18n();
const { fmt }      = useCurrency();
const { fmtMonth } = useFmtMonth();
const { barOptions, textColor, gridColor, labelColor } = useChartTheme();

const props = defineProps({
    month:      String,
    prev:       String,
    next:       String,
    wallets:    Array,
    totals:     Object,
    trend:      { type: Array, default: () => [] },
    byCategory: { type: Array, default: () => [] },
});

function cashFlowClass(val) {
    if (val > 0) return 'text-emerald-400';
    if (val < 0) return 'text-rose-400';
    return 'text-secondary';
}

const trendData = computed(() => ({
    labels: props.trend.map((m) => fmtMonth(m.month)),
    datasets: [
        {
            label: t('overview.income'),
            data: props.trend.map((m) => m.income),
            backgroundColor: '#34d399',
            borderRadius: 4,
        },
        {
            label: t('overview.expenses'),
            data: props.trend.map((m) => m.expenses),
            backgroundColor: '#fb7185',
            borderRadius: 4,
        },
    ],
}));

const trendOptions = computed(() => ({
    ...barOptions.value,
    plugins: {
        ...barOptions.value.plugins,
        tooltip: {
            callbacks: { label: (ctx) => `${ctx.dataset.label} : ${fmt(ctx.raw)}` },
        },
    },
    scales: {
        x: { ticks: { color: textColor.value }, grid: { color: gridColor.value } },
        y: { ticks: { color: textColor.value, callback: (v) => fmt(v) }, grid: { color: gridColor.value } },
    },
}));

const CATEGORY_COLORS = [
    '#818cf8', '#34d399', '#fb7185', '#fbbf24', '#38bdf8', '#c084fc', '#f97316', '#2dd4bf',
];

const donutData = computed(() => ({
    labels: props.byCategory.map((c) => c.name),
    datasets: [{
        data: props.byCategory.map((c) => c.total),
        backgroundColor: CATEGORY_COLORS.slice(0, props.byCategory.length),
        borderWidth: 0,
    }],
}));

const donutOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'right', labels: { color: labelColor.value, boxWidth: 12, padding: 12 } },
        tooltip: {
            callbacks: { label: (ctx) => ` ${ctx.label} : ${fmt(ctx.raw)}` },
        },
    },
}));
</script>

<template>
    <Head :title="t('overview.title')" />

    <AuthenticatedLayout>
        <template #header>
            <AppPageHeader :title="t('overview.title')" />
        </template>

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <Link
                    :href="`/overview?month=${prev}`"
                    class="flex items-center gap-1 text-secondary hover:text-primary transition-colors text-sm capitalize"
                >
                    <ChevronLeft class="w-4 h-4 shrink-0" />
                    <span class="hidden sm:inline">{{ fmtMonth(prev) }}</span>
                </Link>
                <h2 class="text-xl font-bold text-primary capitalize">{{ fmtMonth(month) }}</h2>
                <Link
                    :href="`/overview?month=${next}`"
                    class="flex items-center gap-1 text-secondary hover:text-primary transition-colors text-sm capitalize"
                >
                    <span class="hidden sm:inline">{{ fmtMonth(next) }}</span>
                    <ChevronRight class="w-4 h-4 shrink-0" />
                </Link>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <StatCard :label="t('overview.income')" value-class="text-emerald-400">{{ fmt(totals.income) }}</StatCard>
                <StatCard :label="t('overview.expenses')" value-class="text-rose-400">{{ fmt(totals.expenses) }}</StatCard>
                <StatCard :label="t('overview.cashFlow')" :value-class="cashFlowClass(totals.cash_flow)">{{ fmt(totals.cash_flow, true) }}</StatCard>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bg-surface border border-base/60 rounded-xl p-4">
                    <h3 class="text-sm font-semibold text-secondary mb-4">{{ t('overview.trend') }}</h3>
                    <div class="h-52">
                        <Bar :data="trendData" :options="trendOptions" />
                    </div>
                </div>

                <div class="bg-surface border border-base/60 rounded-xl p-4">
                    <h3 class="text-sm font-semibold text-secondary mb-4">{{ t('overview.byCategory') }}</h3>
                    <div v-if="byCategory.length" class="h-52">
                        <Doughnut :data="donutData" :options="donutOptions" />
                    </div>
                    <EmptyState v-else icon="chart" :message="t('overview.noCategoryData')" compact />
                </div>
            </div>

            <div class="bg-surface border border-base/60 rounded-xl overflow-hidden">
                <template v-if="wallets.length > 0">
                    <div class="sm:hidden divide-y divide-base/40">
                        <div v-for="wallet in wallets" :key="wallet.id" class="p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <Star v-if="wallet.is_favorite" class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="currentColor" />
                                    <span class="font-medium text-primary">{{ wallet.name }}</span>
                                </div>
                                <Link
                                    :href="`/wallets/${wallet.id}/budget?month=${month}`"
                                    class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors"
                                >
                                    {{ t('overview.viewBudget') }} →
                                </Link>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-center">
                                <div>
                                    <AppTooltip :text="t('overview.incomeTip')"><p class="text-xs text-muted cursor-help">{{ t('overview.income') }}</p></AppTooltip>
                                    <p class="text-sm font-mono font-semibold text-emerald-400">{{ fmt(wallet.income) }}</p>
                                </div>
                                <div>
                                    <AppTooltip :text="t('overview.expensesTip')"><p class="text-xs text-muted cursor-help">{{ t('overview.expenses') }}</p></AppTooltip>
                                    <p class="text-sm font-mono font-semibold text-rose-400">{{ fmt(wallet.expenses) }}</p>
                                </div>
                                <div>
                                    <AppTooltip :text="t('overview.cashFlowTip')"><p class="text-xs text-muted cursor-help">{{ t('overview.cashFlow') }}</p></AppTooltip>
                                    <p class="text-sm font-mono font-semibold" :class="cashFlowClass(wallet.cash_flow)">{{ fmt(wallet.cash_flow, true) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-surface-2/50 border-b border-base/40">
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('overview.wallet') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted"><AppTooltip :text="t('overview.incomeTip')"><span class="cursor-help">{{ t('overview.income') }}</span></AppTooltip></th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted"><AppTooltip :text="t('overview.expensesTip')"><span class="cursor-help">{{ t('overview.expenses') }}</span></AppTooltip></th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted"><AppTooltip :text="t('overview.cashFlowTip')"><span class="cursor-help">{{ t('overview.cashFlow') }}</span></AppTooltip></th>
                                    <th class="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-base/40">
                                <tr v-for="wallet in wallets" :key="wallet.id" class="hover:bg-surface-2/40 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <Star v-if="wallet.is_favorite" class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="currentColor" />
                                            <span class="font-medium text-primary">{{ wallet.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-emerald-400">{{ fmt(wallet.income) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-rose-400">{{ fmt(wallet.expenses) }}</td>
                                    <td class="px-4 py-3 text-right font-mono font-semibold" :class="cashFlowClass(wallet.cash_flow)">{{ fmt(wallet.cash_flow, true) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <Link
                                            :href="`/wallets/${wallet.id}/budget?month=${month}`"
                                            class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors"
                                        >
                                            {{ t('overview.viewBudget') }} →
                                        </Link>
                                    </td>
                                </tr>

                                <tr class="bg-surface-2/30 font-semibold border-t-2 border-base/60">
                                    <td class="px-4 py-3 text-sm text-primary uppercase tracking-wide">{{ t('overview.total') }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-emerald-400">{{ fmt(totals.income) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-rose-400">{{ fmt(totals.expenses) }}</td>
                                    <td class="px-4 py-3 text-right font-mono" :class="cashFlowClass(totals.cash_flow)">{{ fmt(totals.cash_flow, true) }}</td>
                                    <td />
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>

                <div v-else class="flex items-center justify-center py-16 text-secondary">
                    <p class="text-sm">{{ t('overview.noWallets') }}</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
