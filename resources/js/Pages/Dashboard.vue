<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import '@/plugins/chartjs';
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import { useCurrency } from '@/composables/core/useCurrency';
import { useChartTheme } from '@/composables/ui/useChartTheme';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    totalTransactions: Number,
    totalCategories: Number,
    recentTransactions: Array,
    sparkline: Array,
    topCategories: Array,
    dailyAverage: Number,
    bestDay: Object,
});

const { fmt } = useCurrency();
const { labelColor } = useChartTheme();

const sparklineData = computed(() => ({
    labels: props.sparkline.map((d) => d.day.slice(5)),
    datasets: [{
        data: props.sparkline.map((d) => parseFloat(d.total)),
        borderColor: '#a78bfa',
        backgroundColor: 'rgba(167, 139, 250, 0.15)',
        borderWidth: 2,
        pointRadius: 0,
        tension: 0.4,
        fill: true,
    }],
}));

const sparklineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
    scales: {
        x: { display: false },
        y: { display: false },
    },
};

const topCategoryMax = computed(() => {
    if (!props.topCategories.length) return 1;
    return Math.max(...props.topCategories.map((c) => parseFloat(c.total)));
});
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary">{{ t('dashboard.title') }}</h2>
        </template>

        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-600 p-6 shadow-lg">
                    <div class="pointer-events-none absolute -top-4 -right-4 h-24 w-24 rounded-full bg-white/10" />
                    <div class="pointer-events-none absolute -bottom-6 -left-6 h-32 w-32 rounded-full bg-white/10" />
                    <p class="text-sm font-medium text-violet-200">{{ t('dashboard.expenses') }}</p>
                    <p class="mt-2 text-5xl font-bold text-white">{{ totalTransactions }}</p>
                    <div class="mt-6 flex gap-3">
                        <Link href="/transactions" class="rounded-lg bg-white/20 px-4 py-2 text-sm font-medium text-white hover:bg-white/30 transition">
                            {{ t('common.seeAll') }}
                        </Link>
                        <Link href="/transactions/create" class="rounded-lg bg-white px-4 py-2 text-sm font-medium text-indigo-600 hover:bg-violet-50 transition">
                            {{ t('dashboard.addExpense') }}
                        </Link>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-6 shadow-lg">
                    <div class="pointer-events-none absolute -top-4 -right-4 h-24 w-24 rounded-full bg-white/10" />
                    <div class="pointer-events-none absolute -bottom-6 -left-6 h-32 w-32 rounded-full bg-white/10" />
                    <p class="text-sm font-medium text-emerald-100">{{ t('dashboard.categories') }}</p>
                    <p class="mt-2 text-5xl font-bold text-white">{{ totalCategories }}</p>
                    <div class="mt-6 flex gap-3">
                        <Link href="/categories" class="rounded-lg bg-white/20 px-4 py-2 text-sm font-medium text-white hover:bg-white/30 transition">
                            {{ t('common.seeAll') }}
                        </Link>
                        <Link href="/categories/create" class="rounded-lg bg-white px-4 py-2 text-sm font-medium text-teal-600 hover:bg-emerald-50 transition">
                            {{ t('dashboard.addExpense') }}
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-surface rounded-2xl p-6 shadow-lg">
                    <h3 class="text-sm font-medium text-secondary mb-1">{{ t('dashboard.chart') }}</h3>
                    <div class="h-32">
                        <Line :data="sparklineData" :options="sparklineOptions" />
                    </div>
                </div>

                <div class="bg-surface rounded-2xl p-6 shadow-lg space-y-4">
                    <div>
                        <p class="text-xs text-muted uppercase tracking-wider">{{ t('dashboard.avgExpense') }}</p>
                        <p class="text-2xl font-bold text-primary mt-1">{{ fmt(dailyAverage) }}</p>
                    </div>
                    <div v-if="bestDay">
                        <p class="text-xs text-muted uppercase tracking-wider">{{ t('dashboard.biggestDay') }}</p>
                        <p class="text-lg font-semibold text-primary mt-1">{{ bestDay.day }}</p>
                        <p class="text-sm text-secondary">{{ fmt(bestDay.total) }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-surface rounded-2xl p-6 shadow-lg">
                    <h3 class="text-sm font-medium text-secondary mb-4">{{ t('dashboard.topCategories') }}</h3>
                    <div v-if="topCategories.length" class="space-y-3">
                        <div v-for="cat in topCategories" :key="cat.name">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-secondary">{{ cat.name }}</span>
                                <span class="text-secondary">{{ fmt(cat.total) }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-surface-3">
                                <div
                                    class="h-2 rounded-full bg-indigo-500 transition-all"
                                    :style="{ width: (parseFloat(cat.total) / topCategoryMax * 100) + '%' }"
                                />
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-muted">{{ t('dashboard.noCatExpenses') }}</p>
                </div>

                <div class="lg:col-span-2 overflow-hidden rounded-2xl bg-surface shadow-lg">
                    <div class="flex items-center justify-between border-b border-subtle px-6 py-4">
                        <h3 class="text-base font-semibold text-primary">{{ t('dashboard.recentExpenses') }}</h3>
                        <Link href="/transactions" class="text-sm text-indigo-400 hover:text-indigo-300">
                            {{ t('dashboard.seeAll') }}
                        </Link>
                    </div>

                    <table v-if="recentTransactions.length > 0" class="min-w-full">
                        <thead>
                            <tr class="bg-surface-2/50">
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('common.date') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('common.description') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('common.category') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('common.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-subtle">
                            <tr v-for="transaction in recentTransactions" :key="transaction.id" class="hover:bg-surface-2/50 transition">
                                <td class="px-6 py-4 text-sm text-secondary">{{ transaction.date }}</td>
                                <td class="px-6 py-4 text-sm text-secondary">{{ transaction.description ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full bg-indigo-900 px-3 py-1 text-xs font-medium text-indigo-300">
                                        {{ transaction.category.name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-semibold text-primary">{{ fmt(transaction.amount) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-else class="flex flex-col items-center justify-center py-16 text-secondary">
                        <p class="text-sm">{{ t('dashboard.noExpenses') }}</p>
                        <Link href="/transactions/create" class="mt-3 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition">
                            {{ t('dashboard.addFirst') }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
