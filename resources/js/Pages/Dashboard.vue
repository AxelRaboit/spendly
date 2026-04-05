<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import '@/plugins/chartjs';
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import { useCurrency } from '@/composables/core/useCurrency';
import { useChartTheme } from '@/composables/ui/useChartTheme';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

function fmtDay(date) {
    if (!date) return '';
    const d = new Date(date);
    return new Intl.DateTimeFormat(locale.value, { day: 'numeric', month: 'long', timeZone: 'UTC' })
        .format(d);
}

const props = defineProps({
    spentThisMonth: Number,
    totalWallets: Number,
    favoriteWallets: Array,
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
            <AppPageHeader :title="t('dashboard.title')" />
        </template>

        <div class="space-y-6">
            <!-- ── Favorite wallets ── -->
            <div v-if="favoriteWallets.length > 0">
                <h3 class="text-xs font-medium text-muted uppercase tracking-wider mb-3">{{ t('wallets.favoriteWallets') }}</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <Link
                        v-for="wallet in favoriteWallets"
                        :key="wallet.id"
                        :href="`/wallets/${wallet.id}/budget`"
                        class="flex items-center justify-between bg-surface border border-base/60 rounded-xl px-4 py-3 hover:border-indigo-500/50 hover:bg-surface-2/60 transition-colors group"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                            <span class="text-sm font-medium text-primary truncate">{{ wallet.name }}</span>
                        </div>
                        <svg class="w-4 h-4 text-muted group-hover:text-indigo-400 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-600 p-6 shadow-lg">
                    <div class="bubble-1 pointer-events-none absolute -top-4 -right-4 h-24 w-24 rounded-full bg-white/10" />
                    <div class="bubble-2 pointer-events-none absolute -bottom-6 -left-6 h-32 w-32 rounded-full bg-white/10" />
                    <p class="text-sm font-medium text-violet-200">{{ t('dashboard.spentThisMonth') }}</p>
                    <p class="mt-2 text-4xl font-bold text-white font-mono">{{ fmt(spentThisMonth) }}</p>
                </div>

                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-6 shadow-lg">
                    <div class="bubble-2 pointer-events-none absolute -top-4 -right-4 h-24 w-24 rounded-full bg-white/10" />
                    <div class="bubble-1 pointer-events-none absolute -bottom-6 -left-6 h-32 w-32 rounded-full bg-white/10" />
                    <p class="text-sm font-medium text-emerald-100">{{ t('dashboard.wallets') }}</p>
                    <p class="mt-2 text-5xl font-bold text-white">{{ totalWallets }}</p>
                    <div class="mt-6">
                        <Link href="/wallets" class="rounded-lg bg-white/20 px-4 py-2 text-sm font-medium text-white hover:bg-white/30 transition">
                            {{ t('common.seeAll') }}
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
                        <p class="text-lg font-semibold text-primary mt-1">{{ fmtDay(bestDay.day) }}</p>
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
                    </div>

                    <template v-if="recentTransactions.length > 0">
                        <!-- Mobile cards -->
                        <div class="sm:hidden divide-y divide-subtle">
                            <Link v-for="transaction in recentTransactions" :key="transaction.id" :href="`/wallets/${transaction.wallet_id}/budget?month=${transaction.date.substring(0, 7)}&flash_category=${transaction.category_id}`" class="flex items-center justify-between px-4 py-3 gap-3 hover:bg-surface-2/50 transition-colors">
                                <div class="flex flex-col gap-1 min-w-0">
                                    <span class="text-sm text-primary font-medium truncate">{{ transaction.description ?? '—' }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-full bg-indigo-900 px-2 py-0.5 text-xs font-medium text-indigo-300">{{ transaction.category.name }}</span>
                                        <span class="text-xs text-muted">{{ fmtDay(transaction.date) }}</span>
                                    </div>
                                </div>
                                <span class="text-sm font-semibold text-primary shrink-0 font-mono">{{ fmt(transaction.amount) }}</span>
                            </Link>
                        </div>

                        <!-- Desktop table -->
                        <div class="hidden sm:block overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-surface-2/50">
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('common.date') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('common.description') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('common.category') }}</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('common.amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-subtle">
                                    <tr v-for="transaction in recentTransactions" :key="transaction.id" class="hover:bg-surface-2/50 transition cursor-pointer" v-on:click="$inertia.visit(`/wallets/${transaction.wallet_id}/budget?month=${transaction.date.substring(0, 7)}&flash_category=${transaction.category_id}`)">
                                        <td class="px-6 py-4 text-sm text-secondary">{{ fmtDay(transaction.date) }}</td>
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
                        </div>
                    </template>

                    <div v-else class="flex flex-col items-center justify-center py-16 text-secondary">
                        <p class="text-sm">{{ t('dashboard.noExpenses') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
